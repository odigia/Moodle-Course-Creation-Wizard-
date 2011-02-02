<?php
    require_once(dirname(dirname(__FILE__)).'/config.php');
    require_once($CFG->dirroot.'/enrol/enrol.class.php');
    require_once($CFG->libdir.'/blocklib.php');
    require_once(dirname(dirname(__FILE__)).'/course/lib.php');
    require_once(dirname(dirname(__FILE__)).'/course/edit_form.php');

class course_edit_form_wizard extends course_edit_form {
    function definition() {
        global $USER, $CFG;

        $courseconfig = get_config('moodlecourse');
        $mform    =& $this->_form;

        $course   = $this->_customdata['course'];
        $category = $this->_customdata['category'];

        $systemcontext = get_context_instance(CONTEXT_SYSTEM);
        $categorycontext = get_context_instance(CONTEXT_COURSECAT, $category->id);

        $disable_meta = false; // basic meta course state protection; server-side security checks not needed

        if (!empty($course)) {
            $coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);
            $context = $coursecontext;

            if (course_in_meta($course)) {
                $disable_meta = get_string('metaalreadyinmeta');

            } else if ($course->metacourse) {
                if (count_records('course_meta', 'parent_course', $course->id) > 0) {
                    $disable_meta = get_string('metaalreadyhascourses');
                }

            } else {
                // if users already enrolled directly into coures, do not allow switching to meta,
                // users with metacourse manage permission are exception
                // please note that we do not need exact results - anything unexpected here prevents metacourse
                $managers = get_users_by_capability($coursecontext, 'moodle/course:managemetacourse', 'u.id');
                $enrolroles = get_roles_with_capability('moodle/course:view', CAP_ALLOW, $coursecontext);
                if ($users = get_role_users(array_keys($enrolroles), $coursecontext, false, 'u.id', 'u.id ASC')) {
                    foreach($users as $user) {
                        if (!isset($managers[$user->id])) {
                            $disable_meta = get_string('metaalreadyhasenrolments');
                            break;
                        }
                    }
                }
                unset($managers);
                unset($users);
                unset($enrolroles);
            }
        } else {
            $coursecontext = null;
            $context = $categorycontext;
        }

/// form definition with new course defaults
//--------------------------------------------------------------------------------
        $mform->addElement('header','general', get_string('general', 'form'));

        // Must have create course capability in both categories in order to move course
//        if (has_capability('moodle/course:create', $categorycontext)) {
            $displaylist = array();
            $parentlist = array();
            make_categories_list($displaylist, $parentlist, 'moodle/course:create');
            $mform->addElement('select', 'category', get_string('category'), $displaylist);
//        } else {
//            $mform->addElement('hidden', 'category', null);
//            $mform->setType('category', PARAM_INT);
//        }
        $mform->setHelpButton('category', array('coursecategory', get_string('category')));
        $mform->setDefault('category', $category->id);
        $mform->setType('category', PARAM_INT);

        if ($course and !has_capability('moodle/course:changecategory', $coursecontext)) {
            $mform->hardFreeze('category');
            $mform->setConstant('category', $category->id);
        }

        $mform->addElement('text','fullname', get_string('fullname'),'maxlength="254" size="50"');
        $mform->setHelpButton('fullname', array('coursefullname', get_string('fullname')), true);
        $mform->setDefault('fullname', get_string('defaultcoursefullname'));
        $mform->addRule('fullname', get_string('missingfullname'), 'required', null, 'client');
        $mform->setType('fullname', PARAM_MULTILANG);
        if ($course and !has_capability('moodle/course:changefullname', $coursecontext)) {
            $mform->hardFreeze('fullname');
            $mform->setConstant('fullname', $course->fullname);
        }

        $mform->addElement('text','shortname', get_string('shortname'),'maxlength="100" size="20"');
        $mform->setHelpButton('shortname', array('courseshortname', get_string('shortname')), true);
        $mform->setDefault('shortname', get_string('defaultcourseshortname'));
        $mform->addRule('shortname', get_string('missingshortname'), 'required', null, 'client');
        $mform->setType('shortname', PARAM_MULTILANG);
        if ($course and !has_capability('moodle/course:changeshortname', $coursecontext)) {
            $mform->hardFreeze('shortname');
            $mform->setConstant('shortname', $course->shortname);
        }

//        $mform->addElement('text','idnumber', get_string('idnumbercourse'),'maxlength="100"  size="10"');
        $mform->addElement('hidden','idnumber', get_string('idnumbercourse'),'maxlength="100"  size="10"');
        $mform->setHelpButton('idnumber', array('courseidnumber', get_string('idnumbercourse')), true);
        $mform->setType('idnumber', PARAM_RAW);
        if ($course and !has_capability('moodle/course:changeidnumber', $coursecontext)) {
            $mform->hardFreeze('idnumber');
            $mform->setConstants('idnumber', $course->idnumber);
        }

        $mform->addElement('htmleditor','summary', get_string('summary'), array('rows'=> '10', 'cols'=>'65'));
        $mform->setHelpButton('summary', array('text', get_string('helptext')), true);
        $mform->setType('summary', PARAM_RAW);

        if ($course and !has_capability('moodle/course:changesummary', $coursecontext)) {
            $mform->hardFreeze('summary');
        }

        $courseformats = get_list_of_plugins('course/format');
        $formcourseformats = array();
        foreach ($courseformats as $courseformat) {
	    if ($courseformat != "topics" && $courseformat != "weeks") continue;
            $formcourseformats["$courseformat"] = get_string("format$courseformat","format_$courseformat");
            if($formcourseformats["$courseformat"]=="[[format$courseformat]]") {
                $formcourseformats["$courseformat"] = get_string("format$courseformat");
            }
        }
        $mform->addElement('select', 'format', get_string('format'), $formcourseformats);
        $mform->setHelpButton('format', array('courseformats', get_string('courseformats')), true);
        $mform->setDefault('format', $courseconfig->format);

        for ($i=1; $i<=52; $i++) {
          $sectionmenu[$i] = "$i";
        }
        $mform->addElement('select', 'numsections', get_string('numberweeks'), $sectionmenu);
        $mform->setDefault('numsections', $courseconfig->numsections);

        $mform->addElement('date_selector', 'startdate', get_string('startdate'));
        $mform->setHelpButton('startdate', array('coursestartdate', get_string('startdate')), true);
        $mform->setDefault('startdate', time() + 3600 * 24);

        $choices = array();
        $choices['0'] = get_string('hiddensectionscollapsed');
        $choices['1'] = get_string('hiddensectionsinvisible');
//        $mform->addElement('select', 'hiddensections', get_string('hiddensections'), $choices);
        $mform->addElement('hidden', 'hiddensections', get_string('hiddensections'));
        $mform->setHelpButton('hiddensections', array('coursehiddensections', get_string('hiddensections')), true);
        $mform->setDefault('hiddensections', $courseconfig->hiddensections);

        $options = range(0, 10);
//        $mform->addElement('select', 'newsitems', get_string('newsitemsnumber'), $options);
        $mform->addElement('hidden', 'newsitems', get_string('newsitemsnumber'));
        $mform->setHelpButton('newsitems', array('coursenewsitems', get_string('newsitemsnumber')), true);
        $mform->setDefault('newsitems', $courseconfig->newsitems);

//        $mform->addElement('selectyesno', 'showgrades', get_string('showgrades'));
        $mform->addElement('hidden', 'showgrades', get_string('showgrades'));
        $mform->setHelpButton('showgrades', array('coursegrades', get_string('grades')), true);
        $mform->setDefault('showgrades', $courseconfig->showgrades);

//        $mform->addElement('selectyesno', 'showreports', get_string('showreports'));
        $mform->addElement('hidden', 'showreports', get_string('showreports'));
        $mform->setHelpButton('showreports', array('coursereports', get_string('activityreport')), true);
        $mform->setDefault('showreports', $courseconfig->showreports);

        $choices = get_max_upload_sizes($CFG->maxbytes);
//        $mform->addElement('select', 'maxbytes', get_string('maximumupload'), $choices);
        $mform->addElement('hidden', 'maxbytes', get_string('maximumupload'));
        $mform->setHelpButton('maxbytes', array('courseuploadsize', get_string('maximumupload')), true);
        $mform->setDefault('maxbytes', $courseconfig->maxbytes);

        if (!empty($CFG->allowcoursethemes)) {
            $themes=array();
            $themes[''] = get_string('forceno');
            $themes += get_list_of_themes();
//            $mform->addElement('select', 'theme', get_string('forcetheme'), $themes);
            $mform->addElement('hidden', 'theme','');
        }

        $meta=array();
        $meta[0] = get_string('no');
        $meta[1] = get_string('yes');
        if ($disable_meta === false) {
//            $mform->addElement('select', 'metacourse', get_string('managemeta'), $meta);
            $mform->addElement('hidden', 'metacourse', get_string('managemeta'), $meta);
            $mform->setHelpButton('metacourse', array('metacourse', get_string('metacourse')), true);
            $mform->setDefault('metacourse', $courseconfig->metacourse);
        } else {
            // no metacourse element - we do not want to change it anyway!
//            $mform->addElement('static', 'nometacourse', get_string('managemeta'),
            $mform->addElement('hidden', 'nometacourse', get_string('managemeta'),
                ((empty($course->metacourse)) ? $meta[0] : $meta[1]) . " - $disable_meta ");
            $mform->setHelpButton('nometacourse', array('metacourse', get_string('metacourse')), true);
        }

//--------------------------------------------------------------------------------
//        $mform->addElement('header','enrolhdr', get_string('enrolments'));
        $mform->addElement('hidden','enrolhdr', get_string('enrolments'));

        $choices = array();
        $modules = explode(',', $CFG->enrol_plugins_enabled);
        foreach ($modules as $module) {
            $name = get_string('enrolname', "enrol_$module");
            $plugin = enrolment_factory::factory($module);
            if (method_exists($plugin, 'print_entry')) {
                $choices[$name] = $module;
            }
        }
        asort($choices);
        $choices = array_flip($choices);
        $choices = array_merge(array('' => get_string('sitedefault').' ('.get_string('enrolname', "enrol_$CFG->enrol").')'), $choices);
//        $mform->addElement('select', 'enrol', get_string('enrolmentplugins'), $choices);
        $mform->addElement('hidden', 'enrol', get_string('enrolmentplugins'));
        $mform->setDefault('enrol', 'manual');
        $mform->setHelpButton('enrol', array('courseenrolmentplugins', get_string('enrolmentplugins')), true);


        $roles = get_assignable_roles($context);
        if (!empty($course)) {
            // add current default role, so that it is selectable even when user can not assign it
            if ($current_role = get_record('role', 'id', $course->defaultrole)) {
                $roles[$current_role->id] = strip_tags(format_string($current_role->name, true));
            }
        }
        $choices = array();
        if ($sitedefaultrole = get_record('role', 'id', $CFG->defaultcourseroleid)) {
            $choices[0] = get_string('sitedefault').' ('.$sitedefaultrole->name.')';
        } else {
            $choices[0] = get_string('sitedefault');
        }
        $choices = $choices + $roles;

        // fix for MDL-9197
        foreach ($choices as $choiceid => $choice) {
            $choices[$choiceid] = format_string($choice);
        }

//        $mform->addElement('select', 'defaultrole', get_string('defaultrole', 'role'), $choices);
        $mform->addElement('hidden', 'defaultrole', get_string('defaultrole', 'role'));
        $mform->setDefault('defaultrole', 0);


        $radio = array();
        $radio[] = &MoodleQuickForm::createElement('radio', 'enrollable', null, get_string('no'), 0);
        $radio[] = &MoodleQuickForm::createElement('radio', 'enrollable', null, get_string('yes'), 1);
        $radio[] = &MoodleQuickForm::createElement('radio', 'enrollable', null, get_string('enroldate'), 2);
//        $mform->addGroup($radio, 'enrollable', get_string('enrollable'), ' ', false);
        $mform->addElement('hidden', 'enrollable', get_string('enrollable'));
        $mform->setHelpButton('enrollable', array('courseenrollable2', get_string('enrollable')), true);
        $mform->setDefault('enrollable', 0);

        $enroldatestartgrp = array();
//        $enroldatestartgrp[] = &MoodleQuickForm::createElement('date_selector', 'enrolstartdate');
//        $enroldatestartgrp[] = &MoodleQuickForm::createElement('checkbox', 'enrolstartdisabled', null, get_string('disable'));
//        $mform->addGroup($enroldatestartgrp, 'enrolstartdategrp', get_string('enrolstartdate'), ' ', false);
        $enroldatestartgrp[] = &MoodleQuickForm::createElement('hidden', 'enrolstartdate');
        $enroldatestartgrp[] = &MoodleQuickForm::createElement('hidden', 'enrolstartdisabled', null, get_string('disable'));
        $mform->addElement('hidden', 'enrolstartdategrp', get_string('enrolstartdate'));
        $mform->setDefault('enrolstartdate', 0);
        $mform->setDefault('enrolstartdisabled', 1);
        $mform->disabledIf('enrolstartdategrp', 'enrolstartdisabled', 'checked');

        $enroldateendgrp = array();
//        $enroldateendgrp[] = &MoodleQuickForm::createElement('date_selector', 'enrolenddate');
//        $enroldateendgrp[] = &MoodleQuickForm::createElement('checkbox', 'enrolenddisabled', null, get_string('disable'));
//        $mform->addGroup($enroldateendgrp, 'enroldateendgrp', get_string('enrolenddate'), ' ', false);
        $enroldateendgrp[] = &MoodleQuickForm::createElement('date_selector', 'enrolenddate');
        $enroldateendgrp[] = &MoodleQuickForm::createElement('checkbox', 'enrolenddisabled', null, 1);
        $mform->addElement('hidden', 'enroldateendgrp', get_string('enrolenddate'));
        $mform->setDefault('enrolenddate', 0);
        $mform->setDefault('enrolenddisabled', 1);
        $mform->disabledIf('enroldateendgrp', 'enrolenddisabled', 'checked');

        $periodmenu=array();
        $periodmenu[0] = get_string('unlimited');
        for ($i=1; $i<=365; $i++) {
            $seconds = $i * 86400;
            $periodmenu[$seconds] = get_string('numdays', '', $i);
        }
//        $mform->addElement('select', 'enrolperiod', get_string('enrolperiod'), $periodmenu);
        $mform->addElement('hidden', 'enrolperiod', get_string('enrolperiod'));
        $mform->setDefault('enrolperiod', 0);


//--------------------------------------------------------------------------------
//        $mform->addElement('header','expirynotifyhdr', get_string('expirynotify'));
        $mform->addElement('hidden','expirynotifyhdr', get_string('expirynotify'));

        $choices = array();
        $choices['0'] = get_string('no');
        $choices['1'] = get_string('yes');
//        $mform->addElement('select', 'expirynotify', get_string('notify'), $choices);
        $mform->addElement('hidden', 'expirynotify', get_string('notify'));
        $mform->setHelpButton('expirynotify', array('expirynotify', get_string('expirynotify')), true);
        $mform->setDefault('expirynotify', 0);

//        $mform->addElement('select', 'notifystudents', get_string('expirynotifystudents'), $choices);
        $mform->addElement('hidden', 'notifystudents', get_string('expirynotifystudents'));
        $mform->setHelpButton('notifystudents', array('expirynotifystudents', get_string('expirynotifystudents')), true);
        $mform->setDefault('notifystudents', 0);

        $thresholdmenu=array();
        for ($i=1; $i<=30; $i++) {
            $seconds = $i * 86400;
            $thresholdmenu[$seconds] = get_string('numdays', '', $i);
        }
//        $mform->addElement('select', 'expirythreshold', get_string('expirythreshold'), $thresholdmenu);
        $mform->addElement('hidden', 'expirythreshold', get_string('expirythreshold'));
        $mform->setHelpButton('expirythreshold', array('expirythreshold', get_string('expirythreshold')), true);
        $mform->setDefault('expirythreshold', 10 * 86400);

//--------------------------------------------------------------------------------
//        $mform->addElement('header','', get_string('groups', 'group'));
        $mform->addElement('hidden','', get_string('groups', 'group'));

        $choices = array();
        $choices[NOGROUPS] = get_string('groupsnone', 'group');
        $choices[SEPARATEGROUPS] = get_string('groupsseparate', 'group');
        $choices[VISIBLEGROUPS] = get_string('groupsvisible', 'group');
//        $mform->addElement('select', 'groupmode', get_string('groupmode'), $choices);
        $mform->addElement('hidden', 'groupmode', get_string('groupmode'));
        $mform->setHelpButton('groupmode', array('groupmode', get_string('groupmode')), true);
        $mform->setDefault('groupmode', 0);

        $choices = array();
        $choices['0'] = get_string('no');
        $choices['1'] = get_string('yes');
//        $mform->addElement('select', 'groupmodeforce', get_string('force'), $choices);
        $mform->addElement('hidden', 'groupmodeforce', get_string('force'));
        $mform->setHelpButton('groupmodeforce', array('groupmodeforce', get_string('groupmodeforce')), true);
        $mform->setDefault('groupmodeforce', 0);

        if (!empty($CFG->enablegroupings)) {
            //default groupings selector
            $options = array();
            $options[0] = get_string('none');
//            $mform->addElement('select', 'defaultgroupingid', get_string('defaultgrouping', 'group'), $options);
            $mform->addElement('hidden', 'defaultgroupingid', get_string('defaultgrouping', 'group'));
        }

//--------------------------------------------------------------------------------
//        $mform->addElement('header','', get_string('availability'));
        $mform->addElement('hidden','', get_string('availability'));

        $choices = array();
        $choices['0'] = get_string('courseavailablenot');
        $choices['1'] = get_string('courseavailable');
//        $mform->addElement('select', 'visible', get_string('availability'), $choices);
        $mform->addElement('hidden', 'visible', get_string('availability'));
        $mform->setHelpButton('visible', array('courseavailability', get_string('availability')), true);
        $mform->setDefault('visible', 1);
        if ($course and !has_capability('moodle/course:visibility', $coursecontext)) {
            $mform->hardFreeze('visible');
            $mform->setConstant('visible', $course->visible);
        }

//        $mform->addElement('passwordunmask', 'enrolpassword', get_string('enrolmentkey'), 'size="25"');
        $mform->addElement('hidden', 'enrolpassword', get_string('enrolmentkey'), 'size="25"');
        $mform->setHelpButton('enrolpassword', array('enrolmentkey', get_string('enrolmentkey')), true);
        $mform->setDefault('enrolpassword', '');
        $mform->setType('enrolpassword', PARAM_RAW);

        if (empty($course) or ($course->password !== '' and $course->id != SITEID)) {
            // do not require password in existing courses that do not have password yet - backwards compatibility ;-)
            if (!empty($CFG->enrol_manual_requirekey)) {
                $mform->addRule('enrolpassword', get_string('required'), 'required', null, 'client');
            }
        }

        $choices = array();
        $choices['0'] = get_string('guestsno');
        $choices['1'] = get_string('guestsyes');
        $choices['2'] = get_string('guestskey');
//        $mform->addElement('select', 'guest', get_string('opentoguests'), $choices);
        $mform->addElement('hidden', 'guest', get_string('opentoguests'), $choices);
        $mform->setHelpButton('guest', array('guestaccess', get_string('opentoguests')), true);
        $mform->setDefault('guest', 0);

        // If we are creating a course, its enrol method isn't yet chosen, BUT the site has a default enrol method which we can use here
        $enrol_object = $CFG;
        if (!empty($course)) {
            $enrol_object = $course;
        }
        // If the print_entry method exists and the course enrol method isn't manual (both set or inherited from site), show cost
//        if (method_exists(enrolment_factory::factory($enrol_object->enrol), 'print_entry') && !($enrol_object->enrol == 'manual' || (empty($enrol_object->enrol) && $CFG->enrol == 'manual'))) {
//            $costgroup=array();
//            $currencies = get_list_of_currencies();
//            $costgroup[]= &MoodleQuickForm::createElement('text','cost', '', 'maxlength="6" size="6"');
//            $costgroup[]= &MoodleQuickForm::createElement('select', 'currency', '', $currencies);
//            $mform->addGroup($costgroup, 'costgrp', get_string('cost'), '&nbsp;', false);
//            //defining a rule for a form element within a group :
//            $costgrprules=array();
//            //set the message to null to tell Moodle to use a default message
//            //available for most rules, fetched from language pack (err_{rulename}).
//            $costgrprules['cost'][]=array(null, 'numeric', null, 'client');
//            $mform->addGroupRule('costgrp',$costgrprules);
//            $mform->setHelpButton('costgrp', array('cost', get_string('cost')), true);
//            $mform->setDefault('cost', '');
//            $mform->setDefault('currency', empty($CFG->enrol_currency) ? 'USD' : $CFG->enrol_currency);
//
//        }

//--------------------------------------------------------------------------------
//        $mform->addElement('header','', get_string('language'));
        $mform->addElement('hidden','', get_string('language'));

        $languages=array();
        $languages[''] = get_string('forceno');
        $languages += get_list_of_languages();
//        $mform->addElement('select', 'lang', get_string('forcelanguage'), $languages);
        $mform->addElement('hidden', 'lang', get_string('forcelanguage'), $languages);

//--------------------------------------------------------------------------------
        if (has_capability('moodle/site:config', $systemcontext) && ((!empty($course->requested) && $CFG->restrictmodulesfor == 'requested') || $CFG->restrictmodulesfor == 'all')) {
//            $mform->addElement('header', '', get_string('restrictmodules'));
            $mform->addElement('hidden', '', get_string('restrictmodules'));

            $options = array();
            $options['0'] = get_string('no');
            $options['1'] = get_string('yes');
            $mform->addElement('select', 'restrictmodules', get_string('restrictmodules'), $options);
            $mods = array(0=>get_string('allownone'));
            $mods += get_records_menu('modules', '','','','id, name');


            $mform->addElement('select', 'allowedmods', get_string('to'), $mods,
                            array('multiple'=>'multiple', 'size'=>'10'));
            $mform->disabledIf('allowedmods', 'restrictmodules', 'eq', 0);
        } else {
            $mform->addElement('hidden', 'restrictmodules', null);
            $mform->setType('restrictmodules', PARAM_INT);
        }
        if ($CFG->restrictmodulesfor == 'all') {
            $mform->setDefault('allowedmods', explode(',',$CFG->defaultallowedmodules));
            if (!empty($CFG->restrictbydefault)) {
                $mform->setDefault('restrictmodules', 1);
            }
        }
        $mform->setType('restrictmodules', PARAM_INT);

/// customizable role names in this course
//--------------------------------------------------------------------------------
//        $mform->addElement('header','rolerenaming', get_string('rolerenaming'));
        $mform->addElement('hidden','rolerenaming', get_string('rolerenaming'));
        $mform->setHelpButton('rolerenaming', array('rolerenaming', get_string('rolerenaming')), true);

        if ($roles = get_records('role')) {
            foreach ($roles as $role) {
//                $mform->addElement('text', 'role_'.$role->id, $role->name);
                $mform->addElement('hidden', 'role_'.$role->id, $role->name);
                $mform->setType('role_'.$role->id, PARAM_TEXT);
                if ($coursecontext) {
                    if ($rolename = get_record('role_names', 'roleid', $role->id, 'contextid', $coursecontext->id)) {
                        $mform->setDefault('role_'.$role->id, $rolename->name);
                    }
                }
            }
        }

//--------------------------------------------------------------------------------
        $this->add_action_buttons();
//--------------------------------------------------------------------------------
        $mform->addElement('hidden', 'id', null);
        $mform->setType('id', PARAM_INT);

        // fill in default teacher and student names to keep backwards compatibility for a while
        $mform->addElement('hidden', 'teacher', get_string('defaultcourseteacher'));
        $mform->setType('teacher', PARAM_RAW);
        $mform->addElement('hidden', 'teachers', get_string('defaultcourseteachers'));
        $mform->setType('teachers', PARAM_RAW);
        $mform->addElement('hidden', 'student', get_string('defaultcoursestudent'));
        $mform->setType('student', PARAM_RAW);
        $mform->addElement('hidden', 'students', get_string('defaultcoursestudents'));
        $mform->setType('students', PARAM_RAW);
    }

/// perform some extra moodle validation
    function validation($data, $files) {
        global $CFG;

//        $errors = parent::validation($data, $files);
        if ($foundcourses = get_records('course', 'shortname', $data['shortname'])) {
            if (!empty($data['id'])) {
                unset($foundcourses[$data['id']]);
            }
            if (!empty($foundcourses)) {
                foreach ($foundcourses as $foundcourse) {
                    $foundcoursenames[] = $foundcourse->fullname;
                }
                $foundcoursenamestring = implode(',', $foundcoursenames);
                $errors['shortname']= get_string('shortnametaken', '', $foundcoursenamestring);
            }
        }

//        if (empty($data['enrolenddisabled'])){
//            if ($data['enrolenddate'] <= $data['enrolstartdate']){
//                $errors['enroldateendgrp'] = get_string('enrolenddaterror');
//            }
//        }

        if (!empty($CFG->enrol_manual_usepasswordpolicy) and isset($data['enrolpassword']) and $data['enrolpassword'] != '') {
            $course = $this->_customdata['course'];
            if ($course->password !== $data['enrolpassword']) {
                // enforce password policy only if changing password - backwards compatibility
                $errmsg = '';
                if (!check_password_policy($data['enrolpassword'], $errmsg)) {
                    $errors['enrolpassword'] = $errmsg;
                }
            }
        }

        return $errors;
    }

    function add_action_buttons($cancel = true, $submitlabel=null){
        if (is_null($submitlabel)){
            $submitlabel = get_string('savechanges');
        }
        $mform =& $this->_form;
        if ($cancel){
            //when two elements we need a group
            $buttonarray=array();
            $buttonarray[0] = &$mform->createElement('submit', 'submitbutton', $submitlabel);
            $buttonarray[1] = &$mform->createElement('cancel');
            $buttonarray[1]->updateAttributes(array('onClick'=>'window.close();return false;'));
            $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
            $mform->closeHeaderBefore('buttonar');
        } else {
            //no group needed
            $mform->addElement('submit', 'submitbutton', $submitlabel);
            $mform->closeHeaderBefore('submitbutton');
        }
        
    }

}

    $id         = optional_param('id', 0, PARAM_INT);       // course id
    $categoryid = optional_param('category', 0, PARAM_INT); // course category - can be changed in edit form


/// basic access control checks
    if ($id) { // editing course

        if($id == SITEID){
            // don't allow editing of  'site course' using this from
            error('You cannot edit the site course using this form');
        }

        if (!$course = get_record('course', 'id', $id)) {
            error('Course ID was incorrect');
        }
//        print_r($course);
        require_login($course->id);
        $category = get_record('course_categories', 'id', $course->category);
        require_capability('moodle/course:update', get_context_instance(CONTEXT_COURSE, $course->id));

    } else if ($categoryid) { // creating new course in this category
        $course = null;
        require_login();
        if (!$category = get_record('course_categories', 'id', $categoryid)) {
            error('Category ID was incorrect');
        }
        require_capability('moodle/course:create', get_context_instance(CONTEXT_COURSECAT, $category->id));
    } else {
        require_login();
//        error('Either course id or category must be specified');
    }

/// prepare course
    if (!empty($course)) {
        $allowedmods = array();
        if (!empty($course)) {
            if ($am = get_records('course_allowed_modules','course',$course->id)) {
                foreach ($am as $m) {
                    $allowedmods[] = $m->module;
                }
            } else {
                if (empty($course->restrictmodules)) {
                    $allowedmods = explode(',',$CFG->defaultallowedmodules);
                } // it'll be greyed out but we want these by default anyway.
            }
            $course->allowedmods = $allowedmods;

            if ($course->enrolstartdate){
                $course->enrolstartdisabled = 0;
            }

            if ($course->enrolenddate) {
                $course->enrolenddisabled = 0;
            }
        }
    }


/// first create the form
    $editform = new course_edit_form_wizard('step3.php', compact('course', 'category'));
//$data = $editform->get_data();
//echo "<pre>";print_r($editform);die();
    // now override defaults if course already exists
//    echo "<pre>";print_r($editform);echo "</pre>";
    if (!empty($course)) {
        $course->enrolpassword = $course->password; // we need some other name for password field MDL-9929
        $editform->set_data($course);
    }
    if ($editform->is_cancelled()){
        if (empty($course)) {
            redirect($CFG->wwwroot);
        } else {
//            redirect($CFG->wwwroot.'/course/view.php?id='.$course->id);
            $fredirect = true;
        }

    } else if ($data = $editform->get_data()) {
        $data->password = $data->enrolpassword;  // we need some other name for password field MDL-9929
/// process data if submitted

        //preprocess data
        if ($data->enrolstartdisabled){
            $data->enrolstartdate = 0;
        }

        if ($data->enrolenddisabled) {
            $data->enrolenddate = 0;
        }

        $data->timemodified = time();

        if (empty($course)) {
            if (!$course = create_course($data)) {
                print_error('coursenotcreated');
            }

            $context = get_context_instance(CONTEXT_COURSE, $course->id);
//
//            // assign default role to creator if not already having permission to manage course assignments
//            if (!has_capability('moodle/course:view', $context) or !has_capability('moodle/role:assign', $context)) {
//                role_assign($CFG->creatornewroleid, $USER->id, 0, $context->id);
//            }
//
//            // ensure we can use the course right after creating it
//            // this means trigger a reload of accessinfo...
//            mark_context_dirty($context->path);

            if ($data->metacourse and has_capability('moodle/course:managemetacourse', $context)) {
                // Redirect users with metacourse capability to student import
                redirect($CFG->wwwroot."/course/importstudents.php?id=$course->id");
            } else {
                // Redirect to roles assignment
//                redirect($CFG->wwwroot."/coursewizard/step4.php?contextid=$context->id");
                $fredirect = true;
            }

        } else {
            if (!update_course($data)) {
                print_error('coursenotupdated');
            }
            $fredirect = true;
//            redirect($CFG->wwwroot."/course/view.php?id=$course->id");
        }
    }


/// Print the form

    $site = get_site();

    $streditcoursesettings = get_string("editcoursesettings");
    $straddnewcourse = get_string("addnewcourse");
    $stradministration = get_string("administration");
    $strcategories = get_string("categories");
    $navlinks = array();

    if (!empty($course)) {
        $navlinks[] = array('name' => $streditcoursesettings,
                            'link' => null,
                            'type' => 'misc');
        $title = $streditcoursesettings;
        $fullname = $course->fullname;
    } else {
        $navlinks[] = array('name' => $stradministration,
                            'link' => "$CFG->wwwroot/$CFG->admin/index.php",
                            'type' => 'misc');
        $navlinks[] = array('name' => $strcategories,
                            'link' => 'index.php',
                            'type' => 'misc');
        $navlinks[] = array('name' => $straddnewcourse,
                            'link' => null,
                            'type' => 'misc');
        $title = "$site->shortname: $straddnewcourse";
        $fullname = $site->fullname;
    }

//    $navigation = build_navigation($navlinks);
    print_header($title, $fullname, $navigation, $editform->focus());
//    echo "<pre>";print_r($editform);echo "</pre>";
//    print_heading($streditcoursesettings);
if(isset($fredirect)){
    $context = get_context_instance(CONTEXT_COURSE, $course->id);
    ?>
    <script type="text/javascript">
        $(function() {
        $.nyroModalManual({
        url: 'step4.php?contextid=<?php echo $context->id; ?>&courseid=<?php echo $course->id; ?>',
        bgColor: '#FFFFFF'
        });
        });
    </script>
    <?php
}
//echo "<pre>";print_r($course);echo "</pre>";
    $editform->display();

//    print_footer($course);

?>
