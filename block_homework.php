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
 * homework block caps.
 *
 * @package    block_homework
 * @copyright  Justin Hunt <poodllsupport@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/homework/locallib.php');
require_once($CFG->dirroot . '/local/family/lib.php');

class block_homework extends block_list {

    function init() {
        $this->title = get_string('pluginname', 'block_homework');
    }

    function get_content() {
        global $CFG, $COURSE,$USER, $DB;
        
        //try to get the homework course the user is enrolled in for My Moodle page
        //If a user is on more than one course, there will need to be some change to this
        //global $COURSE should work though, if in the course itself
        if(!$COURSE || $COURSE->id<2){
        	$homeworkcourses = block_homework_fetch_user_courses($USER->id,10);
        	if(count($homeworkcourses) > 0){
        		$homeworkcourse = array_pop($homeworkcourses);
        	}else{
        		$homeworkcourse = false;
        	}
        }else{
        	$homeworkcourse = $COURSE;
        }
		
		//Get the homework user.
		$childid =  optional_param('childid',0, PARAM_INT); //the userid of the user whose homework we are showing		
		$homeworkuser = false;
		if($childid && local_family_is_users_child($childid)){
			$homeworkuser = $DB->get_record('user',array('id'=>$childid));
		}else{
			$homeworkuser = $USER;
		}
		if(!$homeworkuser){return;}
		
		//if we are logged in as a user who is NOT the child we set parentmode. to deactivate links
		$parentmode=!($homeworkuser->id==$USER->id);
		
		
        if ($this->content !== null) {
            return $this->content;
        }

		// if we are not an instance, or there is no enroled course, can out
        if (empty($this->instance) || !$homeworkcourse) {
            $this->content = '';
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = '';

        // user/index.php expect course context, so get one if page has module context.
        $currentcontext = $this->page->context->get_course_context(false);
		$course = $this->page->course;
		$renderer = $this->page->get_renderer('block_homework');
		
		//this array is for Javascript
		$currenthomeworks = array();
		
		//get group
		$groups = groups_get_user_groups($homeworkcourse->id, $homeworkuser->id); 
		if($groups && count($groups[0])>0 ){
			$groupid = array_pop($groups[0]);
			$todoonly = true;
			$homeworks =  block_homework_fetch_homework_activities($homeworkcourse, $groupid, $todoonly);
			
			if(count($homeworks)>0){
				foreach ($homeworks as $onehomework) {
					$currenthomeworks[$onehomework->cm->id] = $onehomework->cm->id; 
					$homeworkitem = $renderer->fetch_homework_item($onehomework,$parentmode);
					$this->content->items[] = $homeworkitem;
				}
			}else{
				$this->content->items[] = get_string('nohomeworksyet','block_homework');
			}

		}	
		
		//We need this so that we can require course libraries in js
		$jsmodule = array(
			'name'     => 'block_homework',
			'fullpath' => '/blocks/homework/module.js',
			'requires' => array('moodle-course-dragdrop')
		);
		$options=array();
		$options['courseid'] = $homeworkcourse->id;
		$options['currenthomeworks'] = $currenthomeworks;
  
	   $this->page->requires->strings_for_js(
				array('yes', 'no', 'ok', 'cancel', 'error', 'edit', 'move', 'delete', 'movehere'),
				'moodle'
				);
		$this->page->requires->strings_for_js(
				array('addtohomework'),
				'block_homework'
				);
				
		//setup our JS call
		$this->page->requires->js_init_call('M.block_homework.init', array($options),false,$jsmodule);


		//If they don't have permission don't show the manage homework link
		if($currentcontext && has_capability('block/homework:managehomeworks', $currentcontext) ){
			$this->content->items[] = $renderer->fetch_manage_homeworks_item($homeworkcourse->id, 0);
		 }

		return $this->content;
		
    }
    

    // my moodle can only have SITEID and it's redundant here, so take it away
    public function applicable_formats() {
        return array('all' => false,
        			'my'=>true,
                     'site' => true,
                     'site-index' => true,
                     'course-view' => true, 
                     'course-view-social' => false,
                     'mod' => true, 
                     'mod-quiz' => false);
    }

    public function instance_allow_multiple() {
          return true;
    }

    function has_config() {return true;}

    public function cron() {
            mtrace( "Hey, my cron script is running" );
             
                 // do something
                  
                      return true;
    }
}
