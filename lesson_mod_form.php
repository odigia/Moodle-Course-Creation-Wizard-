<?php
require_once ($CFG->dirroot.'/course/moodleform_mod.php');

class mod_resource_lesson_mod_form extends moodleform_mod {
    var $_resinstance;

    function definition() {
        global $CFG;
        $mform =& $this->_form;

        // this hack is needed for different settings of each subtype
        if (!empty($this->_instance)) {
            if($res = get_record('resource', 'id', (int)$this->_instance)) {
                $type = $res->type;
            } else {
                error('incorrect assignment');
            }
        } else {
            $type = required_param('type', PARAM_ALPHA);
        }
        $mform->addElement('hidden', 'type', $type);
        $mform->setType('type', PARAM_ALPHA);
        $mform->setDefault('type', $type);

        require($CFG->dirroot.'/mod/resource/type/'.$type.'/resource.class.php');
        $resclass = 'resource_'.$type;
        $this->_resinstance = new $resclass();

//-------------------------------------------------------------------------------
        $mform->addElement('header', 'general', "Lesson");

//        $mform->addElement('static', 'statictype', get_string('assignmenttype', 'assignment'), get_string('type'.$type,'assignment'));

        $mform->addElement('text', 'name', get_string('name'), array('size'=>'48'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEAN);
        }
        $mform->addRule('name', null, 'required', null, 'client');

        //$mform->addElement('htmleditor', 'summary', get_string('summary'));
        //$mform->setType('summary', PARAM_RAW);
        //$mform->setHelpButton('summary', array('summary', get_string('summary'), 'resource'));
        // summary should be optional again MDL-9485
        //$mform->addRule('summary', get_string('required'), 'required', null, 'client');

        //$mform->addElement('header', 'typedesc', resource_get_name($type));
        //$this->_resinstance->setup_elements($mform);
        
        $mform->addElement('htmleditor', 'alltext', get_string('fulltext', 'resource'), array('cols'=>85, 'rows'=>30));
	$mform->setType('alltext', PARAM_RAW);
    	$mform->setHelpButton('alltext', array('reading', 'writing', 'richtext'), false, 'editorhelpbutton');
    	$mform->addRule('alltext', get_string('required'), 'required', null, 'client');

    	//$mform->addElement('header', 'displaysettings', get_string('display', 'resource'));


        //$this->standard_coursemodule_elements(array('groups'=>false, 'groupmembersonly'=>true, 'gradecat'=>false));

        //$this->add_action_buttons(false, true, false);
        $buttonarray = array();
	$buttonarray[0] = $mform->createElement('submit', 'submitbutton', "Submit");
	$buttonarray[1] = &$mform->createElement('cancel');
	
	$courseid = optional_param('courseid', 0, PARAM_INT); 
        $sectionid = optional_param('sectionid', 0, PARAM_INT); 
	$buttonarray[1]->updateAttributes(array("onClick"=>"location.href = 'step7.php?courseid=" .$courseid ."&sectionid=".$sectionid."'; return false;"));
	
	
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->setType('buttonar', PARAM_RAW);
        $mform->addElement('static', 'fake',null);
        
        $mform->closeHeaderBefore('fake');
        
        $this->standard_hidden_coursemodule_elements();
        
        $mform->addElement('hidden', 'visible');
        $mform->setType('visible',PARAM_INT);
        $mform->setDefault('visible',0);

        $mform->addElement('hidden', 'windowpopup');
        $mform->setType('windowpopup',PARAM_INT);
        $mform->setDefault('windowpopup',1);

    }

    function data_preprocessing(&$default_values){
        $this->_resinstance->setup_preprocessing($default_values);
    }

}
?>