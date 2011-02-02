<?php
        require_once('../config.php');
        
        $courseid = optional_param('courseid', 0, PARAM_INT); 
        $sectionid = optional_param('sectionid', 0, PARAM_INT); 
        $summary = optional_param('summary', '', PARAM_TEXT); 
        $add = optional_param('add', '', PARAM_TEXT);         
        $showfirstmessage = optional_param('showfirstmessage', 0, PARAM_INT); 
                
        
        if (empty($courseid)) {
                error("Must specify course id");
        }

        if (! ($course = get_record('course', 'id', $courseid)) ) {
            error('Invalid course id');
        }

        require_login();
        
        $setsection = isset($_POST["setsection"])? $_POST["setsection"]:0;
        if ($setsection == 1)
        {
                if (! set_field("course_sections", "summary", $summary, "id", $sectionid)) {
                        error("Could not update the summary!");
                }       
                redirect("step8.php?courseid=$courseid&sectionid=$sectionid");
                exit;
        }               
                
        if ($course->format == "topics" || $course->format == "weeks") // from course/format/topics/format.php
        {           
                for ($sectionnumber = 1;  $sectionnumber <= $course->numsections; $sectionnumber++) 
                {
                        if (! $section = get_record('course_sections', 'course', $course->id, 'section', $sectionnumber)) 
                        {
                                $thissection->course = $course->id;   // Create a new section structure
                                $thissection->section = $sectionnumber;
                                $thissection->summary = '';
                                $thissection->visible = 1;
                                if (!$thissection->id = insert_record('course_sections', $thissection)) {
                                        notify('Error inserting new section!');
                                }
                        }                               
                }                       
        }
        
        //$usehtmleditor = can_use_html_editor();
        $usehtmleditor = true;
        $sections = get_all_sections($course->id);

        if ($sectionid > 0)
                $selectedsection =  get_record("course_sections", "id", $sectionid);
        else
                $selectedsection =  get_record("course_sections", "id", $sections[0]->id);              
        
        print_header("$course->fullname: $fullname", $course->fullname, "", "", "", true, "&nbsp;");
        $text = "Outline Topics/Weeks";
        $output .= '<h2 class="main">'.stripslashes_safe($text).'</h2>';
?>
        
        <form id="coursecontentform" method="post" action="step7.php?courseid=<?php echo $courseid ?>">
          <table summary="" style="margin-left:auto;margin-right:auto" border="0" cellpadding="5" cellspacing="0">
                <tr>
                        <td valign="top"><label for="section">Select a Topic/Week</label></td>                                  
                        <td valign="top">
                                <select name="sectionid" id="sectionid" size="1" onchange="changeTopic(this);"> 
                                <?php           
                                        $thissection = $sections[0];
                                        $selected = ($sectionid == 0) ? "selected"  : "";
                                        echo "<option value=\"$thissection->id\" $selected >". "Introduction" ."</option>\n";
                                        
                                        if ($course->format == "weeks")
                                        {
                                                $weekdate = $course->startdate;    // this should be 0:00 Monday of that week
                                                $weekofseconds = 604800;
                                                $course->enddate = $course->startdate + ($weekofseconds * $course->numsections);
                                                $strftimedateshort = ' '.get_string('strftimedateshort');
                                        }
                                        
                                        for ($sectionnumber = 1;  $sectionnumber <= $course->numsections; $sectionnumber++) 
                                        {
                                                $thissection = $sections[$sectionnumber];
                                                $selected = ($sectionid != 0 && $thissection->id == $sectionid) ? "selected"  : "";
                                                if ($course->format == "topics")
                                                        echo "<option value=\"$thissection->id\" $selected>". "Topic". $sectionnumber . "</option>\n";
                                                if ($course->format == "weeks")
                                                {
                                                        $weekday = userdate($weekdate, $strftimedateshort);
                                                        $endweekdate = $weekdate + $weekofseconds;
                                                        $endweekday = userdate($endweekdate, $strftimedateshort);
                                                        $weekperiod = $weekday.' - '.$endweekday;
                                                        echo "<option value=\"$thissection->id\" $selected>". $weekperiod . "</option>\n";
                                                        $weekdate += $weekofseconds + 86400;
                                                }                                                       
                                        }                                               
                                ?>                                      
                                </select>
                        </td>                           
                </tr>

                <tr valign="top">
                        <td align="right"><?php print_string("summary") ?></td>
                        <td> 
                                <?php 
                                        //$thissection = $sections[];
                                        print_textarea(true, 25, 60, 660, 200, "summary", $selectedsection->summary, $courseid); 
                                        if ($usehtmleditor) {
                                                use_html_editor("summary");
                                        }
                                ?>
                        </td>
                </tr>
                
                <tr>
                        <td>&nbsp;</td>                         
                        <td>
                                <input type="submit" value="Save"/>
                                <input type="submit" value="Cancel" onclick="location.href='step8.php?courseid=<?php echo $courseid; ?>'; return false;"/>
                                <input type="hidden" value="1" name="setsection"/>
                        </td>
                </tr>
        </table>                                                        
        </form>
        <script>
                function changeTopic(listbox)
                {
                        location.href="step7.php?courseid=<?php echo $courseid ?>&sectionid=" + listbox.options[listbox.options.selectedIndex].value;
                }                                               
        </script>
        
        <div  align="center">
                <form action="" method="get"  id="ressection2" class="popupform">
                        <select id="ressection2_jump" name="jump" onchange="self.location=document.getElementById('ressection2').jump.options[document.getElementById('ressection2').jump.selectedIndex].value;">
                                <option value="step7.php?courseid=<?php echo $courseid ?>&sectionid=<?php echo $sectionid ?>">Add a resource...</option>
                                <option value="step7.php?courseid=<?php echo $courseid ?>&sectionid=<?php echo $sectionid ?>&add=lesson&type=html" <?php if ($add=='lesson') echo 'selected' ?>>Create a Lesson</option>
                        </select>
                </form>
        </div><br>
        
<?php if ($showfirstmessage==1){ ?>     
    <script type="text/javascript">
        $(function() {
        $.nyroModalManual({
        url: 'step8_2.php',
        bgColor: '#FFFFFF'
        });
        });
    </script>
<?php } ?>      
        
<?
        if ($add=='lesson')     
                require_once("modedit.php");
                
        print_footer(); 
 ?>
