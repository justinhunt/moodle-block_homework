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
 
require_once($CFG->dirroot . '/blocks/homework/lib.php');

class block_homework_manager {

	private $courseid=0;
	private $userid=0;
	private $course=null;
	private $user=null;
	
	/**
     * constructor. make sure we have the right course
     * @param integer courseid id
	*/
	function block_homework_manager($courseid=0,$userid=0) {
		global $COURSE,$USER, $DB;
		if($courseid){
			$this->courseid=$courseid;
		}else{
			$this->courseid = $COURSE->id; 
			$this->course = $COURSE;
		}
		
		if($userid){
			$this->userid=$userid;
			$this->user=$DB->get_record('user',array('id'=>$userid));
		}else{
			$this->userid=$USER->id;
			$this->user = $USER;
		}
		
    }

    /**
     * Add a homework activity
     * @param integer group id
     * @param integer course module id
     * @return id of homework or false if already added
     */
    public function add_homework($groupid, $courseid, $cmid, $startdate) {
        global $DB,$USER;

       // $homework = $this->get_homework($groupid, $cmid);
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
	
	public function edit_homework($homeworkid, $groupid, $courseid, $cmid, $startdate) {
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
    public function get_homeworks($groupid, $courseid) {
        global $DB;
        return $DB->get_records('block_homework', array('groupid' => $groupid,'courseid'=>$courseid));
    }
	

    /**
     * Return a single homework
     * @param integer $homeworkid
     * @return array of course
     */
    public function get_homework($homeworkid) {
        global $DB;
        return $DB->get_record('block_homework',
                array('id' => $homeworkid));
    }

    /**
     * Delete a homework
     * @param integer $homeworkid
     * @return bool true
     */
    public function delete_homework($homeworkid) {
        global $DB;
        return $DB->delete_records('block_homework',
                array('id' => $homeworkid));
    }
	
	
	
	/*
     * Get all the groups
     * @param integer $homeworkid
     * @return array all the groups
     */
	function get_grouplist(){
		$context = context_course::instance($this->courseid);
		$groups = groups_get_all_groups($this->courseid);
		//If they are an admin, let them see all the groups
		if($context && has_capability('block/homework:seeallgroups', $context) ){
			return $groups;
		 }else{
			//this user's groups
			$grouping = groups_get_user_groups($this->courseid, $this->userid);
			if($grouping && count($grouping)>0){
				$returngroups = array();
				foreach($grouping[0]  as $gpid=>$gpval){
					$returngroups[] = $groups[$gpval];
				}
				return $returngroups;
			
			}else{
				return array();
			}
		}
	}

}
