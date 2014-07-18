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
 * @package    block_community
 * @author     Jerome Mouneyrac <jerome@mouneyrac.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 1999 onwards Martin Dougiamas  http://dougiamas.com
 */

require('../../config.php');
require_once($CFG->dirroot . '/blocks/homework/locallib.php');
require_once($CFG->dirroot . '/blocks/homework/forms.php');

require_login();
$courseid = required_param('courseid', PARAM_INT); //if no courseid is given
$action = required_param('action', PARAM_TEXT); //the user action to take
$groupid =  optional_param('groupid',0, PARAM_INT); //the id of the group
$homeworkid =  optional_param('homeworkid',0, PARAM_INT); //the id of the group

$parentcourse = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

$context = context_course::instance($courseid);
$PAGE->set_course($parentcourse);
$PAGE->set_url('/blocks/homework/view.php');
$PAGE->set_heading($SITE->fullname);
$PAGE->set_pagelayout('course');
$PAGE->set_title(get_string('listview', 'block_homework'));
$PAGE->navbar->add(get_string('listview', 'block_homework'));

$bmh = new block_homework_manager($courseid);
$renderer = $PAGE->get_renderer('block_homework');

// OUTPUT
echo $renderer->header();
$message=false;

//only admins and editing teachers should get here really
if(!has_capability('block/homework:managehomeworks', $context) ){
	echo $renderer->heading(get_string('inadequatepermissions', 'block_homework'), 3, 'main');
	echo $renderer->footer();
	return;
 }


//don't do anything without a groupid
if($groupid == 0){
	$action='group';
}else{		
	$groupname = groups_get_group_name($groupid);
	if(!$groupname){
		$message = get_string('invalidgroupid','block_homework');
		$action='group';
	}
}


switch($action){

	
	case 'add':
		echo $renderer->heading(get_string('addhomeworkheading', 'block_homework',$groupname), 3, 'main');
		$addform = new block_homework_add_form(null,array('groupid'=>$groupid));
		$addform->display();
		echo $renderer->footer();
		return;
	

	
	case 'edit':
		echo $renderer->heading(get_string('edithomeworkheading', 'block_homework',$groupname), 3, 'main');
		$editform = new block_homework_edit_form(null,array('groupid'=>$groupid));

		if($homeworkid > 0){
			$bmh = new block_homework_manager();
			$hdata = $bmh->get_homework($homeworkid);
			$hdata->homeworkid=$homeworkid;
			$editform->set_data($hdata);
			$editform->display();
		}else{
			echo get_string('invalidhomeworkid', 'block_homework');
		}
		
		
		echo $renderer->footer();
		return;
		

	
	case 'delete':
		echo $renderer->heading(get_string('deletehomeworkheading', 'block_homework',$groupname), 3, 'main');
		$deleteform = new block_homework_delete_form(null,array('groupid'=>$groupid));
		
		if($homeworkid > 0){
			$bmh = new block_homework_manager();
			$hdata = $bmh->get_homework($homeworkid);
			$hdata->homeworkid=$homeworkid;		
			
			$modinfo = get_fast_modinfo($parentcourse);
			try{
				$cm = $modinfo->get_cm($hdata->cmid);
				$hdata->activityname =$cm->name;
				$hdata->startdate = userdate($hdata->startdate,'%d %B %Y');
				$deleteform->set_data($hdata);
				$deleteform->display();
			}catch(Exception $e){
				block_homework_purge_old_activities();
				error_log( 'An assigned homework has been deleted: ' .  $e->getMessage());
				echo get_string('invalidhomeworkid', 'block_homework');
			}
			
		}else{
			
		}

		echo $renderer->footer();
		return;
		
	case 'group':
		//might have been possible to use moodle groups dropdown
		//http://docs.moodle.org/dev/Groups_API see groups_print_activity_menu
	
		//if we have a status message, display it.
		if($message){
			echo $renderer->heading($message,5,'main');
		}
		echo $renderer->heading(get_string('selectgroup', 'block_homework'), 3, 'main');
		$gdata = new stdClass();
		$gdata->courseid=$courseid;
		$gdata->groupid=$groupid;
		$groupform = new block_homework_group_form(null,array('courseid'=>$courseid));
		$groupform->set_data($gdata);
		$groupform->display();
		echo $renderer->footer();
		return;

	
	case 'doadd':
		//get add form
		$add_form = new block_homework_add_form();
		//print_r($add_form);
		$data = $add_form->get_data();
		if($data){
			$ret = $bmh->add_homework($data->groupid,$data->courseid,$data->cmid,$data->startdate);
			if($ret){
				$message = get_string('addedsuccessfully','block_homework');
			}else{
				$message = get_string('failedtoadd','block_homework');
			}
		}else{
			$message = get_string('canceledbyuser','block_homework');
		}
		break;
		
	case 'doedit':
		//get add form
		$edit_form = new block_homework_edit_form();
		//print_r($add_form);
		$data = $edit_form->get_data();
		if($data){
			$ret = $bmh->edit_homework($data->homeworkid, $data->groupid,$data->courseid,$data->cmid,$data->startdate);
			if($ret){
				$message = get_string('updatedsuccessfully','block_homework');
			}else{
				$message = get_string('failedtoupdate','block_homework');
			}
		}else{
			$message = get_string('canceledbyuser','block_homework');
		}
		break;
		
	case 'dodelete':
		//To do. here collect the data from the form and update in the db using. maybe
		//get add form
		$delete_form = new block_homework_delete_form();
		$data = $delete_form->get_data();
		if($data){
			$ret = $bmh->delete_homework($data->homeworkid);
			if($ret){
				$message = get_string('deletedsuccessfully','block_homework');
			}else{
				$message = get_string('failedtodelete','block_homework');
			}
		}else{
			$message = get_string('canceledbyuser','block_homework');
		}
		break;
		
	case 'dogroup':
		//To do. here collect the data from the form and update in the db using. maybe
		//get add form
		$group_form = new block_homework_group_form();
		$data = $group_form->get_data();
		$groupid = $data->groupid;
		$message = get_string('groupupdated','block_homework');

		break;
	
	case 'list':
	default:

}

	//if we have a status message, display it.
	if($message){
		echo $renderer->heading($message,5,'main');
	}

	echo $renderer->heading(get_string('homeworklist', 'block_homework', $groupname), 3, 'main');
	
	//group form
	//echo $renderer->heading(get_string('selectgroup', 'block_homework'), 3, 'main');
	$gdata = new stdClass();
	$gdata->courseid=$courseid;
	$gdata->groupid=$groupid;
	$groupform = new block_homework_group_form();
	$groupform->set_data($gdata);
	$groupform->display();

	
	//list of homeworks for current group
	$homeworkdata=$bmh->get_homeworks($groupid,$courseid);
	if($homeworkdata){
		$modinfo = get_fast_modinfo($parentcourse);
		echo $renderer->show_homework_list($homeworkdata,$modinfo, $courseid,$groupid);
	}else{
		echo $renderer->heading( get_string('nohomeworks','block_homework',$groupname),4,'main');
	}
	echo $renderer->show_buttons($courseid, $groupid, $groupname);
	echo $renderer->footer();
