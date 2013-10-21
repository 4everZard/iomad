<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Library of functions and constants for module trainingevent
 *
 * @package    mod
 * @subpackage trainingevent
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/** COURSECLASSROOM_MAX_NAME_LENGTH = 50 */
define("COURSECLASSROOM_MAX_NAME_LENGTH", 50);

/**
 * @uses COURSECLASSROOM_MAX_NAME_LENGTH
 * @param object $trainingevent
 * @return string
 */
function get_trainingevent_name($trainingevent) {
    
    $name = strip_tags(format_string($trainingevent->intro,true));
    if (textlib::strlen($name) > COURSECLASSROOM_MAX_NAME_LENGTH) {
        $name = textlib::substr($name, 0, COURSECLASSROOM_MAX_NAME_LENGTH)."...";
    }

    if (empty($name)) {
        // arbitrary name
        $name = get_string('modulename','trainingevent');
    }

    return $name;
}
/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @global object
 * @param object $trainingevent
 * @return bool|int
 */
function trainingevent_add_instance($trainingevent) {
    global $DB;

    $trainingevent->name = get_trainingevent_name($trainingevent);
    $trainingevent->timemodified = time();
    $trainingevent->id =  $DB->insert_record("trainingevent", $trainingevent);
    grade_update('mod/trainingevent', $trainingevent->course, 'mod', 'trainingevent', $trainingevent->id, 0, NULL, array('itemname'=>$trainingevent->name));
    return $trainingevent->id;
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @global object
 * @param object $trainingevent
 * @return bool
 */
function trainingevent_update_instance($trainingevent) {
    global $DB, $CFG;

    if (!function_exists('grade_update')) { //workaround for buggy PHP versions
        require_once($CFG->libdir.'/gradelib.php');
    }
    $trainingevent->name = get_trainingevent_name($trainingevent);
    $trainingevent->timemodified = time();
    $trainingevent->id = $trainingevent->instance;
    
    grade_update('mod/trainingevent', $trainingevent->course, 'mod', 'trainingevent', $trainingevent->id, 0, NULL, array('itemname'=>$trainingevent->name));

    return $DB->update_record("trainingevent", $trainingevent);
}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @global object
 * @param int $id
 * @return bool
 */
function trainingevent_delete_instance($id) {
    global $DB, $CFG;

    if (!function_exists('grade_update')) { //workaround for buggy PHP versions
        require_once($CFG->libdir.'/gradelib.php');
    }
    if (! $trainingevent = $DB->get_record("trainingevent", array("id"=>$id))) {
        return false;
    }

    $result = true;

    if (! $DB->delete_records("trainingevent", array("id"=>$trainingevent->id))) {
        $result = false;
    } else {
        grade_update('mod/trainingevent', $trainingevent->course, 'mod', 'trainingevent', $trainingevent->id, 0, NULL, array('deleted'=>1));
    }

    return $result;
}

/**
 * Returns the users with data in one resource
 * (NONE, but must exist on EVERY mod !!)
 *
 * @param int $trainingeventid
 */
function trainingevent_get_participants($trainingeventid) {

    return false;
}

/**
 * Given a course_module object, this function returns any
 * "extra" information that may be needed when printing
 * this activity in a course listing.
 * See get_array_of_activities() in course/lib.php
 *
 * @global object
 * @param object $coursemodule
 * @return object|null
 */
function trainingevent_get_coursemodule_info($coursemodule) {
    global $DB, $CFG;

    if ($trainingevent = $DB->get_record('trainingevent', array('id'=>$coursemodule->instance), '*')) {
        if (empty($trainingevent->name)) {
            // trainingevent name missing, fix it
            $trainingevent->name = "trainingevent{$trainingevent->id}";
            $DB->set_field('trainingevent', 'name', $trainingevent->name, array('id'=>$trainingevent->id));
        }
        $info = new cached_cm_info();

        // no filtering here because this info is cached and filtered later

        if ($trainingevent->classroomid) {
            $extra = "";
            if ($classroom = $DB->get_record('classroom', array('id' => $trainingevent->classroomid), '*')) {
                $extra .= get_string('location','trainingevent') . ": " . $classroom->name . " - ";
            }
        }
        $dateformat = "d F Y, g:ia";
        
        $extra .= get_string('startdatetime','trainingevent') . ": " . date($dateformat,$trainingevent->startdatetime);
        $extra .= "<a href='$CFG->wwwroot/mod/trainingevent/manageclass.php?id=$trainingevent->id'>".get_string('details','trainingevent')."</a></br>";
        
        // sneakily prepend the extra info to the intro value (only for the remainder of this function)
        $trainingevent->intro = "<div>" . $extra . "</div>";

        $info->content = format_module_intro('trainingevent', $trainingevent, $coursemodule->id, false);
        $info->name  = $trainingevent->name;

        return $info;

    } else {
        return null;
    }
}

/**
 * @return array
 */
function trainingevent_get_view_actions() {
    return array();
}

/**
 * @return array
 */
function trainingevent_get_post_actions() {
    return array();
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 *
 * @param object $data the data submitted from the reset course.
 * @return array status array
 */
function trainingevent_reset_userdata($data) {
    return array();
}

/**
 * Returns all other caps used in module
 *
 * @return array
 */
function trainingevent_get_extra_capabilities() {
    return array('moodle/site:accessallgroups');
}

/**
 * @uses FEATURE_IDNUMBER
 * @uses FEATURE_GROUPS
 * @uses FEATURE_GROUPINGS
 * @uses FEATURE_GROUPMEMBERSONLY
 * @uses FEATURE_MOD_INTRO
 * @uses FEATURE_COMPLETION_TRACKS_VIEWS
 * @uses FEATURE_GRADE_HAS_GRADE
 * @uses FEATURE_GRADE_OUTCOMES
 * @param string $feature FEATURE_xx constant for requested feature
 * @return bool|null True if module supports feature, false if not, null if doesn't know
 */
function trainingevent_supports($feature) {
    switch($feature) {
        case FEATURE_IDNUMBER:                return false;
        case FEATURE_GROUPS:                  return true;
        case FEATURE_GROUPINGS:               return true;
        case FEATURE_GROUPMEMBERSONLY:        return true;
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_GRADE_HAS_GRADE:         return true;
        case FEATURE_GRADE_OUTCOMES:          return false;
        case FEATURE_MOD_ARCHETYPE:           return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_BACKUP_MOODLE2:          return true;
        case FEATURE_NO_VIEW_LINK:            return true;

        default: return null;
    }
}

