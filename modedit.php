<?php 
    $add           = optional_param('add', 0, PARAM_ALPHA);
    $update        = optional_param('update', 0, PARAM_INT);

    if (!empty($add)) 
    {
	$modulename = "resource";
	$moduletype = "html";
	
        if (! $module = get_record("modules", "name", $modulename)) {
            error("This module type doesn't exist");
        }
        $cw = $selectedsection;
        
        $cm = null;

        $form->section          = $cw->section;  // The section number itself - relative!!! (section column in course_sections)
        $form->visible          = $cw->visible;
        $form->course           = $course->id;
        $form->module           = $module->id;
        $form->modulename       = $module->name;
        $form->groupmode        = $course->groupmode;
        $form->groupingid       = $course->defaultgroupingid;
        $form->groupmembersonly = 0;
        $form->instance         = '';
        $form->coursemodule     = '';
        $form->add              = $add;
        $form->return           = 0; //must be false if this is an add, go back to course view on cancel
        
        // Turn off default grouping for modules that don't provide group mode
	$form->groupingid=0;
	$form->type = $moduletype;

        $sectionname = get_section_name($course->format);
        $fullmodulename = get_string("modulename", $module->name);

        if ($form->section && $course->format != 'site') {
            $heading->what = $fullmodulename;
            $heading->to   = "$sectionname $form->section";
            $pageheading = get_string("addinganewto", "moodle", $heading);
        } else {
            $pageheading = get_string("addinganew", "moodle", $fullmodulename);
        }
        
        $CFG->pagepath = 'mod/'.$module->name;
        if (!empty($type)) {
            $CFG->pagepath .= '/'.$type;
        } else {
            $CFG->pagepath .= '/mod';
        }
        $navlinksinstancename = '';
    } 

    $modmoodleform = "$CFG->dirroot/coursewizard/lesson_mod_form.php";
    if (file_exists($modmoodleform)) {
        require_once($modmoodleform);

    } else {
        error('No formslib form description file found for this activity.');
    }

    $modlib = "$CFG->dirroot/mod/$module->name/lib.php";
    if (file_exists($modlib)) {
        include_once($modlib);
    } else {
        error("This module is missing important code! ($modlib)");
    }

    $mformclassname = 'mod_resource_lesson_mod_form';
    $mform =& new $mformclassname($form->instance, $cw->section, $cm, "step7.php?courseid=$courseid&sectionid=$sectionid&add=lesson&type=html");
    $mform->set_data($form);

 if ($fromform = $mform->get_data()) 
 {
        $fromform->course = $course->id;
        $fromform->modulename = clean_param($fromform->modulename, PARAM_SAFEDIR);  // For safety

        $addinstancefunction    = $fromform->modulename."_add_instance";
        $updateinstancefunction = $fromform->modulename."_update_instance";

        if (!isset($fromform->groupingid)) {
            $fromform->groupingid = 0;
        }

        if (!isset($fromform->groupmembersonly)) {
            $fromform->groupmembersonly = 0;
        }

        if (!isset($fromform->name)) { //label
            $fromform->name = $fromform->modulename;
        }

	if (!empty($fromform->add))
	{
	    $fromform->groupmode = 0; // do not set groupmode

            $returnfromfunc = $addinstancefunction($fromform);
            if (!$returnfromfunc) {
                error("Could not add a new instance of $fromform->modulename", "view.php?id=$course->id");
            }
            if (is_string($returnfromfunc)) {
                error($returnfromfunc, "view.php?id=$course->id");
            }

            $fromform->instance = $returnfromfunc;

            // course_modules and course_sections each contain a reference
            // to each other, so we have to update one of them twice.
            if (! $fromform->coursemodule = add_course_module($fromform) ) {
                error("Could not add a new course module");
            }
            if (! $sectionid = add_mod_to_section($fromform) ) {
                error("Could not add the new course module to that section");
            }

            if (! set_field("course_modules", "section", $sectionid, "id", $fromform->coursemodule)) {
                error("Could not update the course module with the correct section");
            }

            // make sure visibility is set correctly (in particular in calendar)
            set_coursemodule_visible($fromform->coursemodule, $fromform->visible);

            if (isset($fromform->cmidnumber)) { //label
                // set cm idnumber
                set_coursemodule_idnumber($fromform->coursemodule, $fromform->cmidnumber);
            }
	    redirect("step8.php?courseid=$courseid&sectionid=$sectionid");
	    exit;
        }
  }	        
    
    $mform->display();
    
?>
