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
 * The homework block helper functions and callbacks
 *
 * @package   block_homework
 * @copyright 2014 Justin Hunt <poodllsupport@google.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

	/**
     * Fetch all live, visible and not complete homework activities as cm's
     * @param integer $courseid
	 * @param integer $groupid
     * @return array array of homeworkdata (startdata + cm) objects
     */
	  function block_homework_fetch_homework_activities($course, $groupid, $todoonly, $userid=0) {
        global $CFG, $DB, $OUTPUT;

        require_once($CFG->dirroot.'/course/lib.php');

		//get info on all mods in course
        $modinfo = get_fast_modinfo($course);
        
		//init our return array
		$homeworks = array();

		//get all the live homeworks
		//if todoonly is false, it will return ALL the homework activities
		$livehomeworks = block_homework_get_live_homeworks($course->id,$groupid,!$todoonly);
		if(!$livehomeworks){
			return $homeworks;
		}

        foreach($modinfo->cms as $cm) {
			$onehomework = new stdClass();
            // Exclude activities which are not visible or have no link (=label)
            if (!$cm->uservisible or !$cm->has_view()) {
                continue;
            }
			
			//match cms with our homework cmid
			//if it is not a homework activity, we ignore it
			$cm_is_livehomework=false;
			foreach($livehomeworks as $livehomework){
				if ($livehomework->cmid == $cm->id){
					$cm_is_livehomework=true;
				 	$onehomework->startdate = $livehomework->startdate;
					break;
				}
			}
			if(!$cm_is_livehomework){continue;}
			
			//Exclude completed activities
			//If user has completed this, we can unshow it. ie continue
			//we will need to configure completion on SCORM object
			// if userid is 0 it will use logged in user
			//if userid is set, it will check that user's activity completion
			if($todoonly && block_homework_activity_is_complete($cm,$course,$userid)){
				continue;
			}
			
			//add this activity to our return data (CM + startdate)
			$onehomework->cm = $cm;
			$homeworks[] =  $onehomework;  
        }

		//sort by start date
        core_collator::asort_objects_by_property($homeworks,'startdate',core_collator::SORT_NUMERIC);
		
		return $homeworks;
    }
	
	/**
     * Return all current homeworks for a group in a given course, that are after the start date
     * @param integer $courseid
	 * @param integer $groupid
     * @return array of course
     */
    function block_homework_get_live_homeworks($courseid,$groupid,$ignorestartdate=false) {
        global $DB;
		if($ignorestartdate){
			$select = "groupid = $groupid AND courseid = $courseid"; //where clause
		}else{
			$select = "groupid = $groupid AND courseid = $courseid AND startdate <= " . time(); //where clause		
		}
		$table = 'block_homework';
		return $DB->get_records_select($table,$select);
    }
	
	/**
     * Check if an activity has been completed
     * @param object $cm The course module
	 * @param integer $userid pass in to check X user, blank to use current user 
     * @return boolean true:complete false:incomplete
     */
	function block_homework_activity_is_complete($cm, $course, $userid = 0){
        global $USER,$DB;
		if($userid==0){
			$userid=$USER->id;
		}
		
		// Get current completion state
        $completion = new completion_info($course);
        $data = $completion->get_data($cm, false, $userid);

        // Is the activity already complete
       //$completed= $data->viewed == COMPLETION_VIEWED; 
	   $completed = $data->completionstate == COMPLETION_COMPLETE;
        return $completed;
    }

	/**
     * Fetch user's group
     * @param integer $userid
     * @return integer $groupid (0 if unknown)
     */
	function block_homework_fetch_group_by_user($userid,$courseid) {
		$groups = groups_get_user_groups($courseid, $userid);
		if($groups && count($groups[0])>0 ){
			$groupid = array_pop($groups[0]);
		}else{
			$groupid=0;
		}
	}	  
	
	/**
    * Fetch all activities in course
	* just calls core function get_array_of_activities
	* For a given course, returns an array of course activity objects
	* Each item in the array contains he following properties:
	* cm - course module id
	* mod - name of the module (eg forum)
	* section - the number of the section (eg week or topic)
	* name - the name of the instance
	* visible - is the instance visible or not
	* groupingid - grouping id
	* groupmembersonly - is this instance visible to group members only
	* extra - contains extra string to include in any link
	*
    * @param integer $courseid
    * @return array() All the activities 
    */
	function block_homework_fetch_all_activities($courseid){
		return get_array_of_activities($courseid);
	}
	

/**
 * Purge any junk from homework database. Can't get events to do this for us.
 *
 */
function block_homework_purge_old_activities() {
	global $DB;
	$where = "cmid NOT IN (SELECT id FROM {course_modules})";
	$DB->delete_records_select('block_homework', $where);
}
	
	/**
 * Returns list of courses passedin user is enrolled in and can access
 *
 *
 * @param string $userid
 * @param int $limit max number of courses
 * @return array
 */
function block_homework_fetch_user_courses($userid, $limit=1) {
		global $DB;

	$sort = 'visible DESC,sortorder ASC';
	$user = $DB->get_record('user', array('id'=>$userid));

    // Guest account does not have any courses
    if (isguestuser() or !isloggedin()) {
        return(array());
    }

    $basefields = array('id', 'category', 'sortorder',
                        'shortname', 'fullname', 'idnumber',
                        'startdate', 'visible',
                        'groupmode', 'groupmodeforce', 'cacherev');

   
    $fields = $basefields;
    if (in_array('*', $fields)) {
        $fields = array('*');
    }

    $orderby = "";
    $sort    = trim($sort);
    if (!empty($sort)) {
        $rawsorts = explode(',', $sort);
        $sorts = array();
        foreach ($rawsorts as $rawsort) {
            $rawsort = trim($rawsort);
            if (strpos($rawsort, 'c.') === 0) {
                $rawsort = substr($rawsort, 2);
            }
            $sorts[] = trim($rawsort);
        }
        $sort = 'c.'.implode(',c.', $sorts);
        $orderby = "ORDER BY $sort";
    }

    $wheres = array("c.id <> :siteid");
    $params = array('siteid'=>SITEID);

    if (isset($user->loginascontext) and $user->loginascontext->contextlevel == CONTEXT_COURSE) {
        // list _only_ this course - anything else is asking for trouble...
        $wheres[] = "courseid = :loginas";
        $params['loginas'] = $user->loginascontext->instanceid;
    }

    $coursefields = 'c.' .join(',c.', $fields);
    $ccselect = ', ' . context_helper::get_preload_record_columns_sql('ctx');
    $ccjoin = "LEFT JOIN {context} ctx ON (ctx.instanceid = c.id AND ctx.contextlevel = :contextlevel)";
    $params['contextlevel'] = CONTEXT_COURSE;
    $wheres = implode(" AND ", $wheres);

    //note: we can not use DISTINCT + text fields due to Oracle and MS limitations, that is why we have the subselect there
    $sql = "SELECT $coursefields $ccselect
              FROM {course} c
              JOIN (SELECT DISTINCT e.courseid
                      FROM {enrol} e
                      JOIN {user_enrolments} ue ON (ue.enrolid = e.id AND ue.userid = :userid)
                     WHERE ue.status = :active AND e.status = :enabled AND ue.timestart < :now1 AND (ue.timeend = 0 OR ue.timeend > :now2)
                   ) en ON (en.courseid = c.id)
           $ccjoin
             WHERE $wheres
          $orderby";
    $params['userid']  = $user->id;
    $params['active']  = ENROL_USER_ACTIVE;
    $params['enabled'] = ENROL_INSTANCE_ENABLED;
    $params['now1']    = round(time(), -2); // improves db caching
    $params['now2']    = $params['now1'];

    $courses = $DB->get_records_sql($sql, $params, 0, $limit);

    // preload contexts and check visibility
    foreach ($courses as $id=>$course) {
        context_helper::preload_from_record($course);
        if (!$course->visible) {
            if (!$context = context_course::instance($id, IGNORE_MISSING)) {
                unset($courses[$id]);
                continue;
            }
            if (!has_capability('moodle/course:viewhiddencourses', $context)) {
                unset($courses[$id]);
                continue;
            }
        }
        $courses[$id] = $course;
    }


	//return the courses
    return $courses;


  }//end of function
 
	
	/**
	 * course_content_deleted event handler
	 *
	 * @param \core\event\course_content_deleted $event The event.
	 * @return void
	 */
	function block_homework_handle_activity_deletion(\core\event\course_content_deleted $event) {
		global $DB;
		$DB->delete_records('block_homework', array('cmid' => $event->contextinstanceid));
		error_log("cid=" . $event->contextinstanceid);
	}