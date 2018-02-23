<?php
/**
 * Last access report
 *
 * Allow user to select a course and see a list of users with their last access times
 *
 * @package   report-lastaccess
 * @author    Priya Ramakrishnan, Pukunui {@link http://pukunui.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require($CFG->dirroot.'/report/lastaccess/index_form.php');


require_login();

// Get passed parameters.
$export = optional_param('export', 0, PARAM_INT);
$cid = optional_param('cid', 0, PARAM_INT);
$cd = optional_param('cd',0,PARAM_INT);
$ld = optional_param('ld',0,PARAM_INT);

// To export the output as CSV file.
if ($export) {

    // Query to get the data from the database.
    $sql = "SELECT l.id,c.fullname,u.firstname, u.lastname,MAX(l.time)as time,y.data as agency,x.finalgrade
            FROM {log} l
            INNER JOIN {course} c ON c.id=l.course
            INNER JOIN {user} u ON u.id=l.userid
            INNER JOIN (
                SELECT d.userid,d.data FROM {user_info_data} d,{user_info_field} f
                WHERE f.shortname='AGY' AND f.id=d.fieldid 
            ) AS y ON y.userid=u.id
            LEFT JOIN (
                SELECT i.id,i.courseid,g.userid,g.finalgrade FROM {grade_items} i,{grade_grades} g
                WHERE i.itemtype='course' AND i.id=g.itemid 
            ) AS x ON (x.courseid=c.id AND x.userid=u.id)
            WHERE l.time > ? AND l.time < ?
            AND c.id=?
            GROUP BY u.firstname,u.lastname
            ORDER BY u.firstname, u.lastname ASC";

    $params = array($ld, $cd, $cid);
    
    // Has the query returned any records? 
    if ($users = $DB->get_records_sql($sql,$params)) {
        // CSV file creation and Data Export to CSV File
        $filename = 'csvexport_'.date("Ymd").'.csv';
        @header('Content-Disposition: attachment; filename='.$filename);
        @header('Content-Type: text/csv');
         
        $csvhead = array(get_string('course'), get_string('lastaccess', 'report_lastaccess'), get_string('currentdate', 'report_lastaccess'),  get_string('name', 'report_lastaccess'), get_string('lastaccess', 'report_lastaccess'), get_string('agency', 'report_lastaccess'), get_string('grade', 'report_lastaccess'));
        $csvheading = implode(',', $csvhead);
        echo $csvheading ;
        echo "\n";

        
        // Looping through query output to write into CSV file.
        foreach ($users as $u) {
            // If the finalgrade is returned, round it to the nearest value.
            if ($u->finalgrade) {
                $finalgrade = round($u->finalgrade, $CFG->grade_decimalpoints);
            } else {
                $finalgrade = 0;
            }
        //Added to change the date format
        $printarray = array(str_replace(',',' ',$u->fullname), str_replace(',', ' ', userdate("$ld")), str_replace(',', ' ', userdate("$cd")), str_replace(',',' ',fullname($u)), str_replace(',', ' ', date("Y-m-d", $u->time)), str_replace(',',' ',$u->agency), $finalgrade);
            $line = implode(',', $printarray);
            echo $line;
            echo "\n";
        }

    }
    // Kill the process once export is completed.
    exit;
}

// Get the system context.
$systemcontext = get_system_context();
$url = new moodle_url('/report/lastaccess/index.php');

// Check basic permission.
require_capability('report/lastaccess:view',$systemcontext);

// Get the language strings from language file.
$strtitle = get_string('title', 'report_lastaccess');
$strcourse = get_string('course');
$strname = get_string ('name', 'report_lastaccess');
$strlastaccess = get_string('lastaccess', 'report_lastaccess');
$stragency = get_string('agency', 'report_lastaccess');
$strgrade = get_string('grade', 'report_lastaccess');


// Set up page object.
$PAGE->set_url($url);
$PAGE->set_context($systemcontext);
$PAGE->set_title($strtitle);
$PAGE->set_pagelayout('report');
$PAGE->set_heading($strtitle);

// Get the courses.
$sql = "SELECT id, fullname
	FROM {course}
	WHERE visible = :visible
	AND id != :siteid
    ORDER BY fullname";
$courses = $DB->get_records_sql_menu($sql, array('visible' => 1, 'siteid' => SITEID));

// Load up the form.
$mform = new lastaccess_form('', array('courses' => $courses));


// Has any data been submitted?
if ($data = $mform->get_data()) {
    $ctd = ($data->currentdate+(24*3600));
    $sql = "SELECT l.id, c.fullname, u.firstname, u.lastname, MAX(l.time)as time, y.data as agency, x.finalgrade
            FROM {log} l
            INNER JOIN {course} c ON c.id=l.course
            INNER JOIN {user} u ON u.id=l.userid
            INNER JOIN (
                SELECT d.userid,d.data FROM {user_info_data} d,{user_info_field} f
                WHERE f.shortname='AGY' AND f.id=d.fieldid 
            ) AS y ON y.userid=u.id
            LEFT JOIN (
                SELECT i.id,i.courseid,g.userid,g.finalgrade FROM {grade_items} i,{grade_grades} g
                WHERE i.itemtype='course' AND i.id=g.itemid 
            ) AS x ON (x.courseid=c.id AND x.userid=u.id)
            WHERE l.time > ? AND l.time < ?
            AND c.id=?
            GROUP BY u.firstname,u.lastname 
            ORDER BY u.firstname, u.lastname ASC";
 
    $params = array($data->lastaccesseddate, $ctd, $data->course);
    
    // Has the query returned any Records? 
    if ($users = $DB->get_records_sql($sql,$params)) {
        // Table set up.
        $table = new html_table();
        $table->head = array($strcourse, $strname, $strlastaccess, $stragency, $strgrade);
        foreach ($users as $u) {
            // If the finalgrade is returned, round it to the nearest value else assign finalgrade = 0.
            if ($u->finalgrade) {
                $finalgrade = round($u->finalgrade, $CFG->grade_decimalpoints);
            } else {
                $finalgrade = 0;
            }
            // Displays the table data.
            $table->data[] = array($u->fullname, fullname($u), userdate($u->time), $u->agency, $finalgrade);
        } 
    } else {
        echo $OUTPUT->header();
        $mform->display();
        echo $OUTPUT->box(get_string('nodatareturned', 'report_lastaccess'));
        echo $OUTPUT->footer();
        exit();
    }
}
       

// Output the page and the form.
echo $OUTPUT->header();
if($courses) {
	$mform->display();
} else {
	echo $OUTPUT->box(get_string ('nocourse', 'report_lastaccess'));
}

// Output the table if it has the data else display a "No Data Returned"  message.
if(!empty($table->data)){
    echo html_writer::table($table);
    echo $OUTPUT->single_button("index.php?export=1&cid=$data->course&cd=$data->currentdate&ld=$data->lastaccesseddate", get_string('exportcsv','report_lastaccess'));
}

echo $OUTPUT->footer();
