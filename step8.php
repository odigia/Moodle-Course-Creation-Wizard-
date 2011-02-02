<?php

    require_once('../config.php');
    require_once($CFG->libdir.'/ajax/ajaxlib.php');
    require_once($CFG->dirroot.'/mod/forum/lib.php');
    
    $courseid = optional_param('courseid', 0, PARAM_INT); 
    $sectionid = optional_param('sectionid', 0, PARAM_INT); 
    $publish = optional_param('publish', 0, PARAM_INT); 
    $showfirstmessage = optional_param('showfirstmessage', 0, PARAM_INT); 
    
    $sesskey = sesskey();
	if (empty($courseid)) {
        	error("Must specify course id, short name or idnumber");
	}

	if (! ($course = get_record('course', 'id', $courseid)) ) {
            error('Invalid course id');
	}

    preload_course_contexts($course->id);
    if (!$context = get_context_instance(CONTEXT_COURSE, $course->id)) {
        print_error('nocontext');
    }
    
    require_login($course);

    //If course is hosted on an external server, redirect to corresponding
    //url with appropriate authentication attached as parameter 
    if (file_exists($CFG->dirroot .'/course/externservercourse.php')) {
        include $CFG->dirroot .'/course/externservercourse.php';
        if (function_exists('extern_server_course')) {
            if ($extern_url = extern_server_course($course)) {
                redirect($extern_url);
            }
        }
    }

    require_once($CFG->dirroot.'/calendar/lib.php');    /// This is after login because it needs $USER

    add_to_log($course->id, 'course', 'view', "view.php?id=$course->id", "$course->id");

    $course->format = clean_param($course->format, PARAM_ALPHA);
    if (!file_exists($CFG->dirroot.'/course/format/'.$course->format.'/format.php')) {
        $course->format = 'weeks';  // Default format is weeks
    }

    $PAGE = page_create_object(PAGE_COURSE_VIEW, $course->id);
    //$pageblocks = blocks_setup($PAGE, BLOCKS_PINNED_BOTH);

    if ($reset_user_allowed_editing) {
        // ugly hack
        unset($PAGE->_user_allowed_editing);
    }

	$USER->editing = 0;


    $SESSION->fromdiscussion = $CFG->wwwroot .'/course/view.php?id='. $course->id;

    if ($course->id == SITEID) {
        // This course is not a real course.
        redirect($CFG->wwwroot .'/');
    }

    // AJAX-capable course format?
    $useajax = false; 
    $ajaxformatfile = $CFG->dirroot.'/course/format/'.$course->format.'/ajax.php';
    $bodytags = '';

    if (empty($CFG->disablecourseajax) and file_exists($ajaxformatfile)) {      // Needs to exist otherwise no AJAX by default

        // TODO: stop abusing CFG global here
        $CFG->ajaxcapable = false;           // May be overridden later by ajaxformatfile
        $CFG->ajaxtestedbrowsers = array();  // May be overridden later by ajaxformatfile

        require_once($ajaxformatfile);

        if (!empty($USER->editing) && $CFG->ajaxcapable && has_capability('moodle/course:manageactivities', $context)) {
                                                             // Course-based switches

            if (ajaxenabled($CFG->ajaxtestedbrowsers)) {     // Browser, user and site-based switches
                
                require_js(array('yui_yahoo',
                                 'yui_dom',
                                 'yui_event',
                                 'yui_dragdrop',
                                 'yui_connection',
                                 'yui_selector',
                                 'yui_element',
                                 'ajaxcourse_blocks',
                                 'ajaxcourse_sections'));
                
                if (debugging('', DEBUG_DEVELOPER)) {
                    require_js(array('yui_logger'));

                    $bodytags = 'onload = "javascript:
                    show_logger = function() {
                        var logreader = new YAHOO.widget.LogReader();
                        logreader.newestOnTop = false;
                        logreader.setTitle(\'Moodle Debug: YUI Log Console\');
                    };
                    show_logger();
                    "';
                }

                // Okay, global variable alert. VERY UGLY. We need to create
                // this object here before the <blockname>_print_block()
                // function is called, since that function needs to set some
                // stuff in the javascriptportal object.
                $COURSE->javascriptportal = new jsportal();
                $useajax = true;
            }
        }
    }

    $CFG->blocksdrag = $useajax;   // this will add a new class to the header so we can style differently
    
    print_header("$course->fullname: $fullname", $course->fullname, "", "", "", true, "&nbsp;");

    if ($publish == 1)
    {
      set_field("course", "enrollable", 1, "id", $courseid);
      $course -> enrollable = 1;
      //echo "<div align=center>";
      //echo "<font color=blue size='+1'>Course is published. Students have access to it now.</font>";
      //echo "</div>";
?>      
    <script type="text/javascript">
        $(function() {
        $.nyroModalManual({
        url: 'step8_0.php',
        bgColor: '#FFFFFF'
        });
        });
    </script>
<?php
    }	    	
    // Course wrapper start.
    echo '<div class="course-content">';
    
    $modinfo =& get_fast_modinfo($COURSE);
    get_all_mods($course->id, $mods, $modnames, $modnamesplural, $modnamesused);
    foreach($mods as $modid=>$unused) {
        if (!isset($modinfo->cms[$modid])) {
            rebuild_course_cache($course->id);
            $modinfo =& get_fast_modinfo($COURSE);
            debugging('Rebuilding course cache', DEBUG_DEVELOPER);
            break;
        }
    }

    $sections = get_all_sections($course->id);
    if (! $sections = get_all_sections($course->id)) {   // No sections found
        // Double-check to be extra sure
        if (! $section = get_record('course_sections', 'course', $course->id, 'section', 0)) {
            $section->course = $course->id;   // Create a default section.
            $section->section = 0;
            $section->visible = 1;
            $section->id = insert_record('course_sections', $section);
        }
        if (! $sections = get_all_sections($course->id) ) {      // Try again
            error('Error finding or creating section structures for this course');
        }
	    
    }


	$USER->ignoresesskey = true;
    // Include the actual course format.
    require($CFG->dirroot .'/course/format/'. $course->format .'/format.php');
    
    

// Content wrapper end.
?>
<br>
<table align="center">
<tr>
     <td>
	<form id="backform" method="post" action="step7.php?courseid=<?php echo $courseid ?>&sectionid=<?php echo $sectionid ?>&showfirstmessage=<?php echo $showfirstmessage ?>">
    		<input type="submit" value="Add/Edit Content and Resources to Your Course"/>
	</form>
     </td>
     <?php   if ($course->enrollable == 0) { ?>
     <td>
	<form id="publishform" method="post" action="step8.php?courseid=<?php echo $courseid ?>&publish=1">
    		<input type="submit" value="Publish Course"/>
	</form>
     </td>		
    <?php 				      }  ?>
     <td>
	<form id="draftform" method="post" action="step8_1.php" class="nyroModal">
    		<input type="submit" value="Save Course As Draft" />
	</form>
     </td>
</tr>
</table>
<br>
<?php
    echo "</div>\n\n";

    // Use AJAX?
    if ($useajax && has_capability('moodle/course:manageactivities', $context)) {
        // At the bottom because we want to process sections and activities
        // after the relevant html has been generated. We're forced to do this
        // because of the way in which lib/ajax/ajaxcourse.js is written.
        echo '<script type="text/javascript" ';
        echo "src=\"{$CFG->wwwroot}/lib/ajax/ajaxcourse.js\"></script>\n";

        $COURSE->javascriptportal->print_javascript($course->id);
    }
    print_footer(NULL, $course);
?>
