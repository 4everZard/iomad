<?php

require_once(dirname(__FILE__) . '/../../config.php'); // Creates $PAGE
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/user/filters/lib.php');

$sort         = optional_param('sort', 'firstname', PARAM_ALPHA);
$dir          = optional_param('dir', 'ASC', PARAM_ALPHA);
$page         = optional_param('page', 0, PARAM_INT);
$perpage      = optional_param('perpage', 30, PARAM_INT);        // how many per page
$acl          = optional_param('acl', '0', PARAM_INT);           // id of user to tweak mnet ACL (requires $access)
$search      = optional_param('search', '', PARAM_CLEAN);// search string
$departmentid = optional_param('departmentid', 0, PARAM_INTEGER);
$firstname       = optional_param('firstname', 0, PARAM_CLEAN);
$lastname      = optional_param('lastname', '', PARAM_CLEAN);   //md5 confirmation hash
$email  = optional_param('email', 0, PARAM_CLEAN);
$eventid = required_param('eventid',0,PARAM_INTEGER);

//admin_externalpage_setup('editusers');
$params = array();

if ($sort) {
    $params['sort'] = $sort;
}
if ($dir) {
    $params['dir'] = $dir;
}
if ($page) {
    $params['page'] = $page;
}
if ($perpage) {
    $params['perpage'] = $perpage;
}
if ($search) {
    $params['search'] = $search;
}
if ($firstname) {
    $params['firstname'] = $firstname;
}
if ($lastname) {
    $params['lastname'] = $lastname;
}
if ($email) {
    $params['email'] = $email;
}

if (!$event=$DB->get_record('trainingevent', array('id'=>$eventid))) {
	print_error('invalid event ID');
}

//page stuff:
$url = new moodle_url('/course/view.php', array('id'=>$event->course));
$context=get_context_instance(CONTEXT_COURSE, $event->course);
require_login($event->course); // Adds to $PAGE, creates $OUTPUT
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');
$PAGE->set_title($event->name);
$PAGE->set_heading($SITE->fullname);
$baseurl  = new moodle_url('searchusers.php', array('eventid'=>$eventid));
echo $OUTPUT->header();

// get the location information
$location = $DB->get_record('classroom',array('id'=>$event->classroomid));

// How many are already attending?
$attending = $DB->count_records('trainingevent_users', array('trainingeventid' => $event->id));

// get the associated department id
$company = new company($location->companyid);
$parentlevel = company::get_company_parentnode($company->id);
$companydepartment = $parentlevel->id;

if (has_capability('block/eldms_company_admin:edit_all_departments', get_context_instance(CONTEXT_SYSTEM))) {
    $userhierarchylevel = $parentlevel->id;
} else {
    $userlevel = company::get_userlevel($USER);
    $userhierarchylevel = $userlevel->id;
}
if ($departmentid == 0 ) {
    $departmentid = $userhierarchylevel;
}

// get the appropriate list of departments
$subhierarchieslist = company::get_all_subdepartments($userhierarchylevel);
$select = new single_select($baseurl, 'departmentid', $subhierarchieslist, $departmentid);
$select->label = get_string('department', 'block_eldms_company_admin');
$select->formid = 'choosedepartment';
echo html_writer::tag('div', $OUTPUT->render($select), array('id'=>'perficio_department_selector'));
$fwselectoutput = html_writer::tag('div', $OUTPUT->render($select), array('id'=>'perficio_company_selector'));

// Set up the filter form..
$mform = new iomad_user_filter_form(null, array('companyid' => $company->id));
$mform->set_data(array('departmentid' => $departmentid, 'eventid' => $eventid));
$mform->set_data($params);

// Display the user filter form
$mform->display();
// Deal with the user optional profile search
$idlist = array();
if (!empty($fieldnames)) {
    $fieldids = array();
    foreach ($fieldnames as $id => $fieldname) {
        if ($fields[$id]->datatype == "menu" ) {
            $paramarray = explode("\n", $fields[$id]->param1);
            ${$fieldname} = $paramarray[${$fieldname}];
        }
        if (!empty(${$fieldname}) ) {
            $idlist[0] = "We found no one";
            $fieldsql = $DB->sql_compare_text('data')."='".${$fieldname}."' AND fieldid = $id"; 
            if ($idfields = $DB->get_records_sql("SELECT userid from {user_info_data} WHERE $fieldsql")) {
                $fieldids[] = $idfields;
            }
        }
   }

    if (!empty($fieldids)) {
        $idlist = array_pop($fieldids);
        if (!empty($fieldids)) {
            foreach ($fieldids as $fieldid) {
                $idlist = array_intersect_key($idlist, $fieldid);
                if (empty($idlist)) { break; }
            }
        }
 
    }
}


$returnurl = "manageclass.php?eventid=$eventid";

// Carry on with the user listing

$columns = array("firstname", "lastname", "email", "city", "country");

foreach ($columns as $column) {
    $string[$column] = get_string("$column");
}

// get all or company users depending on capability

//  Check if has capability edit all users
// get department users
$departmentusers = company::get_recursive_department_users($departmentid);
if ( count($departmentusers) > 0 ) {
    $departmentids = "";
    foreach($departmentusers as $departmentuser) {
        if (!empty($departmentids)) {
            $departmentids .= ",".$departmentuser->userid;
        } else {
            $departmentids .= $departmentuser->userid;
        }
    }
    $SQLSEARCH = " id in ($departmentids) ";
} else {
    $SQLSEARCH = "1 = 0";http://leia.e-learndesign.co.uk/~deneka
}

// deal with search strings.
if (!empty($idlist)) {
    $SQLSEARCH .= "AND id in (".implode(',',array_keys($idlist)).") ";
}
if (!empty($params['firstname'])) {
    $SQLSEARCH .= " AND firstname like '%".$params['firstname']."%' ";
}
    
if (!empty($params['lastname'])) {
    $SQLSEARCH .= " AND lastname like '%".$params['lastname']."%' ";
}
    
if (!empty($params['email'])) {
    $SQLSEARCH .= " AND email like '%".$params['email']."%' ";
}
// deal with users already assigned.
if ($assignedusers = $DB->get_records('trainingevent_users', array('trainingeventid'=>$event->id), null, 'userid')) {
	$SQLSEARCH .= " AND id not in (".implode(',', array_keys($assignedusers)).") ";
}    

// Strip out no course users.
$SQLSEARCH .= " AND id IN ( SELECT userid from {course_completions} WHERE course = " . $event->course . ") ";

// Get the user records
$userrecords = $DB->get_fieldset_select('user', 'id', $SQLSEARCH);
$userlist="";
foreach ($userrecords as $userrecord ) {
    if ( !empty($userlist)) {
        $userlist .= " OR id=$userrecord ";
    } else {
        $userlist .= " id=$userrecord ";
    }
}
if (!empty($userlist)) {
    $users = get_users_listing($sort, $dir, $page*$perpage, $perpage, '', '', '', $userlist);
} else {
    $users=array();
}
$usercount = count($userrecords);

echo $OUTPUT->heading("$usercount ".get_string('users'));

$alphabet = explode(',', get_string('alphabet', 'block_eldms_company_admin'));
$strall = get_string('all');

$baseurl = new moodle_url('editusers.php', array('sort' => $sort, 'dir' => $dir, 'perpage' => $perpage));
echo $OUTPUT->paging_bar($usercount, $page, $perpage, $baseurl);

flush();


if (!$users) {
    $match = array();
    echo $OUTPUT->heading(get_string('nousersfound'));

    $table = NULL;

} else {

    $countries = get_string_manager()->get_list_of_countries();
    if (empty($mnethosts)) {
        $mnethosts = $DB->get_records('mnet_host', null, 'id', 'id,wwwroot,name');
    }

    foreach ($users as $key => $user) {
        if (!empty($user->country)) {
            $users[$key]->country = $countries[$user->country];
        }
    }
    if ($sort == "country") {  // Need to resort by full country name, not code
        foreach ($users as $user) {
            $susers[$user->id] = $user->country;
        }
        asort($susers);
        foreach ($susers as $key => $value) {
            $nusers[] = $users[$key];
        }
        $users = $nusers;
    }

    $mainadmin = get_admin();

    $override = new object();
    $override->firstname = 'firstname';
    $override->lastname = 'lastname';
    $fullnamelanguage = get_string('fullnamedisplay', '', $override);
    if (($CFG->fullnamedisplay == 'firstname lastname') or
        ($CFG->fullnamedisplay == 'firstname') or
        ($CFG->fullnamedisplay == 'language' and $fullnamelanguage == 'firstname lastname' )) {
        $fullnamedisplay = "$firstname / $lastname";
    } else { // ($CFG->fullnamedisplay == 'language' and $fullnamelanguage == 'lastname firstname')
        $fullnamedisplay = "$lastname / $firstname";
    }

    $table = new html_table();
    $table->head = array (get_string('fullname'), get_string('email'),get_string('city'), get_string('country'), "");
    $table->align = array ("left", "left", "left", "left", "center");
    $table->width = "95%";
    foreach ($users as $user) {
        if ($user->username == 'guest') {
            continue; // do not dispaly dummy new user and guest here
        }

        if (has_capability('mod/trainingevent:add', $context) && $attending < $location->capacity) {
            $enrolmentbutton = $OUTPUT->single_button(new moodle_url("/mod/trainingevent/manageclass.php", array('id'=>$event->id, 'chosenevent'=>$event->id, 'userid'=>$user->id, 'view'=>1, 'action'=>'add')), get_string('bookuser', 'trainingevent'));
        } else {
            $enrolmentbutton ="";
        }
        $fullname = fullname($user, true);

        $table->data[] = array ("$fullname",
                            "$user->email",
                            "$user->city",
                            "$user->country",
                            $enrolmentbutton);
    }
}

if (!empty($table)) {
    echo html_writer::table($table);
    echo $OUTPUT->paging_bar($usercount, $page, $perpage, $baseurl);
}

echo $OUTPUT->footer();