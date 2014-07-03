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
 * @author     Jerome Mouneyrac <jerome@mouneyrac.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 1999 onwards Martin Dougiamas  http://dougiamas.com
 *
 *
 */

class block_homework_manager {

    /**
     * Add a homework activity
     * @param integer group id
     * @param integer course module id
     * @return id of homework or false if already added
     */
    public function block_homework_add_homework($groupid, $courseid, $cmid, $startdate) {
        global $DB,$USER;

        $homework = $this->block_homework_get_homework($groupid, $cmid);
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
     * Return all homeworks for a group
     * @param integer $groupid
     * @return array of course
     */
    public function block_homework_get_homeworks($groupid, $courseid) {
        global $DB;
        return $DB->get_records('block_homework', array('groupid' => $groupid));
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
		global $COURSE,$USER;
		$groups = groups_get_all_groups($COURSE->id);
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
	  function block_homework_fetch_activities() {
        global $CFG, $DB, $OUTPUT;

        $course = $this->page->course;

        require_once($CFG->dirroot.'/course/lib.php');

        $modinfo = get_fast_modinfo($course);
        $modfullnames = array();

        $archetypes = array();

        foreach($modinfo->cms as $cm) {
            // Exclude activities which are not visible or have no link (=label)
            if (!$cm->uservisible or !$cm->has_view()) {
                continue;
            }
            if (array_key_exists($cm->modname, $modfullnames)) {
                continue;
            }
            if (!array_key_exists($cm->modname, $archetypes)) {
                $archetypes[$cm->modname] = plugin_supports('mod', $cm->modname, FEATURE_MOD_ARCHETYPE, MOD_ARCHETYPE_OTHER);
            }
            if ($archetypes[$cm->modname] == MOD_ARCHETYPE_RESOURCE) {
                if (!array_key_exists('resources', $modfullnames)) {
                    $modfullnames['resources'] = get_string('resources');
                }
            } else {
                $modfullnames[$cm->modname] = $cm->modplural;
            }
        }

        core_collator::asort($modfullnames);

        foreach ($modfullnames as $modname => $modfullname) {
            if ($modname === 'resources') {
                $icon = $OUTPUT->pix_icon('icon', '', 'mod_page', array('class' => 'icon'));
                $this->content->items[] = '<a href="'.$CFG->wwwroot.'/course/resources.php?id='.$course->id.'">'.$icon.$modfullname.'</a>';
            } else {
                $icon = '<img src="'.$OUTPUT->pix_url('icon', $modname) . '" class="icon" alt="" />';
                $this->content->items[] = '<a href="'.$CFG->wwwroot.'/mod/'.$modname.'/index.php?id='.$course->id.'">'.$icon.$modfullname.'</a>';
            }
        }

        return $this->content;
    }

}
