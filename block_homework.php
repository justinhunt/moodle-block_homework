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

class block_homework extends block_list {

    function init() {
        $this->title = get_string('pluginname', 'block_homework');
    }

    function get_content() {
        global $CFG, $OUTPUT, $COURSE,$USER;

        if ($this->content !== null) {
            return $this->content;
        }

        if (empty($this->instance)) {
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
		
		//get our block omework helper class
		$bmh = new block_homework_manager($this->page->course->id);
		
		//get group
		$groups = groups_get_user_groups($COURSE->id, $USER->id);
		if($groups && count($groups[0])>0 ){
			$groupid = array_shift($groups[0]);
			$homeworks =  $bmh->block_homework_fetch_activities($groupid);
	
			foreach ($homeworks as $onehomework) {

				if ($onehomework->cm->modname === 'resources') {
					$icon = $OUTPUT->pix_icon('icon', '', 'mod_page', array('class' => 'icon'));
				} else {
					$icon = '<img src="'.$OUTPUT->pix_url('icon', $onehomework->cm->modname) . '" class="icon" alt="" />';
				}
				$modurl = $CFG->wwwroot.'/mod/'.$onehomework->cm->modname.'/view.php?id=' . $onehomework->cm->id;
				$this->content->items[] = userdate($onehomework->startdate,'%d %B %Y') . ' ' . html_writer::link($modurl,$icon . $onehomework->cm->name);
				//$this->content->items[] = '<a href="'.$CFG->wwwroot.'/mod/'.$modname.'/index.php?id='.$course->id.'">'.$icon.$modfullname.'</a>';
			}

		}
		

		//If they don't have permission don't show it
	//	if(has_capability('block/homework:managehomeworks', $currentcontext) ){
	if(true){
			$url = new moodle_url('/blocks/homework/view.php', array('courseid'=>$COURSE->id,'action'=>'list','groupid'=>'0'));
			//$this->content->items[] = "<a href='" . $url->out(false). "'>" . get_string('managehomeworks','block_homework') . "</a>";
			$this->content->items[] = html_writer::link($url, get_string('managehomeworks','block_homework'));
		 }
		

		$this->content->footer = '';
		return $this->content;
		
        if (! empty($this->config->text)) {
            $this->content->text = $this->config->text;
        }

        $this->content = '';
        if (empty($currentcontext)) {
            return $this->content;
        }
        if ($this->page->course->id == SITEID) {
            $this->context->text .= "site context";
        }

        if (! empty($this->config->text)) {
            $this->content->text .= $this->config->text;
        }

        return $this->content;
    }

    // my moodle can only have SITEID and it's redundant here, so take it away
    public function applicable_formats() {
        return array('all' => false,
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
