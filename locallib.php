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
 * Community library
 *
 * @package    block_homework
 * @author     Justin Hunt <poodllsupport@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2014 onwards Justin Hunt
 *
 *
 */

class block_homework_manager {

	private $courseid=0;
	private $course=null;
	
	/**
     * constructor. make sure we have the right course
     * @param integer courseid id
	*/
	function block_homework_manager($courseid=0) {
		global $COURSE;
		if($courseid){
        $this->courseid=$courseid;
		}else{
			$this->courseid = $COURSE->id; 
			$this->course = $COURSE;
		}
    }

    /**
     * Add a homework activity
     * @param integer group id
     * @param integer course module id
     * @return id of homework or false if already added
     */
    public function block_homework_add_homework($groupid, $courseid, $cmid, $startdate) {
        global $DB,$USER;

       // $homework = $this->block_homework_get_homework($groupid, $cmid);
        if (empty($homework)) {
            $homework = new stdClass();
            $homework->groupid = $groupid;
			$homework->courseid = $courseid;
            $homework->cmid = $cmid;
            $homework->startdate = $startdate;
            $homework->editedby = $USER->id;
            return $DB->insert_record('block_homework', $homework);
        } else {
            return false;
        }
    }
	
	public function block_homework_edit_homework($homeworkid, $groupid, $courseid, $cmid, $startdate) {
        global $DB,$USER;

            $homework = new stdClass();
			 $homework->id = $homeworkid;
            $homework->groupid = $groupid;
			$homework->courseid = $courseid;
            $homework->cmid = $cmid;
            $homework->startdate = $startdate;
            $homework->editedby = $USER->id;
        if( $DB->update_record('block_homework', $homework))
		{
			return true;
        } else {
            return false;
        }
    }

    /**
     * Return all homeworks for a group in a given course
     * @param integer $groupid
     * @return array of course
     */
    public function block_homework_get_homeworks($groupid, $courseid) {
        global $DB;
        return $DB->get_records('block_homework', array('groupid' => $groupid,'courseid'=>$courseid));
    }
	
	 /**
     * Return all current homeworks for a group in a given course, tht are after the start date
     * @param integer $groupid
     * @return array of course
     */
    public function block_homework_get_live_homeworks($courseid,$groupid) {
        global $DB;
		$select = "groupid = $groupid AND courseid = $courseid AND startdate <= " . time(); //where clause
		$table = 'block_homework';
		return $DB->get_records_select($table,$select);
    }
	
	/**
     * Check if an activity has been completed(we assume for now 03/07/2014 that is "viewed"
     * @param object $cm The course module
	 * @param integer $userid pass in to check X user, blank to use current user 
     * @return boolean true:complete false:incomplete
     */
	public function activity_is_complete($cm, $userid = 0){
        global $USER,$DB;
		if($userid==0){
			$userid=$USER->id;
		}
		
		//if we do not have a course object, get one.
		if(!$this->course){
			$this->course = $DB->get_record('course', array('id'=>$this->courseid));
		}
        
		// Get current completion state
        $completion = new completion_info($this->course);
        $data = $completion->get_data($cm, false, $userid);

        // Is the activity already complete
       //$completed= $data->viewed == COMPLETION_VIEWED; 
	   $completed = $data->completionstate == COMPLETION_COMPLETE;
        return $completed;
    }

    /**
     * Return a single homework
     * @param integer $groupid
     * @param integer $cmid
     * @return array of course
     */
    public function block_homework_get_homework($homeworkid) {
        global $DB;
        return $DB->get_record('block_homework',
                array('id' => $homeworkid));
    }



    /**
     * Delete a homework
     * @param integer $groupid
     * @param integer $cmid
     * @return bool true
     */
    public function block_homework_delete_homework($homeworkid) {
        global $DB, $USER;
        return $DB->delete_records('block_homework',
                array('id' => $homeworkid));
    }
	
	function block_homework_get_grouplist(){
		global $USER;
		$groups = groups_get_all_groups($this->courseid);
		return $groups;
	}
	
	/**
	 * course_content_deleted event handler
	 *
	 * @param \core\event\course_content_deleted $event The event.
	 * @return void
	 */
	function block_homework_handle_activity_deletion(\core\event\course_content_deleted $event) {
		global $DB;
		$DB->delete_records('block_homework', array('cmid' => $event->contextinstanceid));
	}

	/**
     * Fetch all (visible) activities in course for use in a list 
     * @return bool true
     */
	  function block_homework_fetch_activities($groupid = 0) {
        global $CFG, $DB, $OUTPUT;

        require_once($CFG->dirroot.'/course/lib.php');

		//if we do not have a course object, get one.
		if(!$this->course){
			$this->course = $DB->get_record('course', array('id'=>$this->courseid));
		}
		
        $modinfo = get_fast_modinfo($this->course);
        $homeworks = array();

        $archetypes = array();
		
		$livehomeworks = $this->block_homework_get_live_homeworks($this->courseid,$groupid);
		if(!$livehomeworks){
			return $homeworks;
		}

        foreach($modinfo->cms as $cm) {
			$onehomework = new stdClass();
            // Exclude activities which are not visible or have no link (=label)
            if (!$cm->uservisible or !$cm->has_view()) {
                continue;
            }
			
			//loop through live homework and continue if its not there.
			$cm_is_livehomework=false;
			foreach($livehomeworks as $livehomework){
				if ($livehomework->cmid == $cm->id){
					$cm_is_livehomework=true;
				 	$onehomework->startdate = $livehomework->startdate;
					break;
				}
			}
			if(!$cm_is_livehomework){continue;}
			
			//If user has completed this, we can unshow it. ie continue
			//we will need to configure completion on SCORM object
			if($this->activity_is_complete($cm)){
				continue;
			}
			
			$onehomework->cm = $cm;
			 $homeworks[] =  $onehomework;  
        }

        //core_collator::asort($homeworks);
		
		return $homeworks;


    }

}
