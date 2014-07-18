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
 * Controller for various actions of the block.
 *
 * This page display the community course search form.
 * It also handles adding a course to the community block.
 * It also handles downloading a course template.
 *
 * @package    block_homework
 * @author     Justin Hunt 
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 1999 onwards Justin Hunt poodllsupport@gmail.com
 */

require('../../config.php');
require_once($CFG->dirroot . '/local/family/lib.php');

require_login();


$courseid = required_param('courseid', PARAM_INT); //if no courseid is given


global $DB,$USER;

$parentcourse = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

$context = context_course::instance($courseid);
$PAGE->set_course($parentcourse);
$PAGE->set_url('/blocks/homework/apiview.php');
$PAGE->set_heading($SITE->fullname);
$PAGE->set_pagelayout('course');
$PAGE->set_title("API VIEW for YL");
$PAGE->navbar->add("API VIEW for YL");

echo $OUTPUT->header();

$isparent = local_family_is_parent($USER->id);
if($isparent){
	echo $OUTPUT->heading("Parent Data: " . fullname($USER),2,'main');
	$child_users =  local_family_fetch_child_users($USER->id);
	if(!$child_users || count($child_users) <1){
		echo $OUTPUT->heading(fullname($USER) . ' has no children.',4,'main');
	}else{
		foreach($child_users as $child){
			show_user_links($child);
		}
	}
}else{
	echo $OUTPUT->heading(fullname($USER) . ' is not a parent.',2,'main');
}
echo $OUTPUT->footer();


	
function show_user_links($child){
	global $OUTPUT;
	
	echo $OUTPUT->heading("Links for child: " . fullname($child),3,'main');
	
	$courses = local_family_fetch_user_courses($child->id);
	foreach($courses as $course){
		echo $OUTPUT->heading('Course: ' . $course->fullname,4,'main');
		$loginas = local_family_fetch_loginas_url($child->id, $course->id);
		$outline_report = local_family_fetch_outlinereport_url($child->id, $course->id,'outline') ;
		$complete_report = local_family_fetch_outlinereport_url($child->id, $course->id,'complete') ;
		$grade_report = local_family_fetch_gradereport_url($child->id, $course->id);
		
		echo html_writer::link($loginas, 'Login As') . '<br />';
		echo html_writer::link($outline_report, 'Outline Report') . '<br />';
		echo html_writer::link($outline_report, 'Complete Report') . '<br />';
		echo html_writer::link($grade_report, 'Grade Report') . '<br />';
		echo '<hr />';
	}
	
}
