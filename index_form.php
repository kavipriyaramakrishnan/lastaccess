<?php
/**
 * Form - Last access report
 *
 * Design the form elements
 *
 * @package   report-lastaccess
 * @author    Priya Ramakrishnan, Pukunui {@link http://pukunui.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once('../../config.php');
require_once("$CFG->libdir/formslib.php");

/**
 * Class custom_report extends moodleform
 */
class lastaccess_form extends moodleform {

    /**
     * Function definition to define From elements
     */
    public function definition() {
  
        // global $DB;

    	$mform =& $this->_form;
	
	    // Get the courses passed to the form.
	    $options = array();
    	$options[0] = get_string('choose');
	    $options += $this->_customdata['courses'];
        $mform->addElement('select', 'course', get_string('course'), $options, 'align="center"');
        $mform->setType('course', PARAM_ALPHANUMEXT);
        
        $mform->addElement('date_selector', 'lastaccesseddate', get_string('from'), 'align="center"');
        $mform->setType('lastaccesseddate', PARAM_INT); 
    
        $mform->addElement('date_selector', 'currentdate', get_string('to'), 'align="center"');
        $mform->setType('currentdate', PARAM_INT);
        
        $mform->addElement('submit', 'save', get_string('display', 'report_lastaccess'), 'align="right"');
    }

    /**
     * Function validation to validate the input from the form
     *
     * @param object $data holds the data submitted form the form
     * @param object $files, files submitted as part of the form
     * @return array error messages
     */ 
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Added to check whether the course option selected is valid.
        if ($data['course'] == '0' ){
            $errors['course'] = get_string('error_invalidcourse','report_lastaccess');
        }
        // Added to compare lastaccessesdate and currentdate.
    	if ($data['lastaccesseddate'] > $data['currentdate']){
            $errors['lastaccesseddate'] = get_string('error_invaliddate','report_lastaccess');
    	}
        // Added to compare currentdate with the systemdate.
        if ($data['currentdate'] > time(date("d-m-Y"))){
            $errors['currentdate'] = get_string('error_invalidcurrentdate','report_lastaccess');
        }
        // Added to check the lastaccessed date is not equal to null/zero.
        if ($data['lastaccesseddate'] == 0 ){
            $errors['lastaccesseddate'] = get_string('error_nolastaccessdate','report_lastaccess');
        }
        
	return $errors;
    }
           
}
