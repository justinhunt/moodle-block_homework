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

/*
$search = optional_param('search', null, PARAM_TEXT);

//if no capability to search course, display an error message
$usercansearch = has_capability('moodle/community:add', $context);
$usercandownload = has_capability('moodle/community:download', $context);
if (empty($usercansearch)) {
    $notificationerror = get_string('cannotsearchcommunity', 'hub');
} else if (!extension_loaded('xmlrpc')) {
    $notificationerror = $OUTPUT->doc_link('admin/environment/php_extension/xmlrpc', '');
    $notificationerror .= get_string('xmlrpcdisabledcommunity', 'hub');
}
if (!empty($notificationerror)) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('searchcommunitycourse', 'block_community'), 3, 'main');
    echo $OUTPUT->notification($notificationerror);
    echo $OUTPUT->footer();
    die();
}

$communitymanager = new block_community_manager();
*/
$renderer = $PAGE->get_renderer('block_homework');
$bmh = new block_homework_manager();

// OUTPUT
echo $OUTPUT->header();

$message=false;

//don't do anything without a groupid
if($groupid == 0){$action='group';}

switch($action){
	
	case 'add':
		echo $OUTPUT->heading(get_string('addhomework', 'block_homework'), 3, 'main');
		$addform = new block_homework_add_form(null,array('groupid'=>$groupid));
		$addform->display();
		echo $OUTPUT->footer();
		return;
	

	
	case 'edit':
		echo $OUTPUT->heading(get_string('edithomeworkheading', 'block_homework',$groupid), 3, 'main');
		$editform = new block_homework_edit_form(null,array('groupid'=>$groupid));

		if($homeworkid > 0){
			$bmh = new block_homework_manager();
			$hdata = $bmh->block_homework_get_homework($homeworkid);
			$hdata->homeworkid=$homeworkid;
			$editform->set_data($hdata);
			$editform->display();
		}else{
			echo get_string('invalidhomeworkid', 'block_homework');
		}
		
		
		echo $OUTPUT->footer();
		return;
		

	
	case 'delete':
		echo $OUTPUT->heading(get_string('deletehomeworkheading', 'block_homework',$groupid), 3, 'main');
		$deleteform = new block_homework_delete_form(null,array('groupid'=>$groupid));
		
		if($homeworkid > 0){
			$bmh = new block_homework_manager();
			$hdata = $bmh->block_homework_get_homework($homeworkid);
			$hdata->homeworkid=$homeworkid;		
			
			$modinfo = get_fast_modinfo($parentcourse);
			$cm = $modinfo->get_cm($hdata->cmid);
			$hdata->activityname =$cm->name;
			
			$hdata->startdate = userdate($hdata->startdate,'%d %B %Y');
			
			$deleteform->set_data($hdata);
			$deleteform->display();
		}else{
			echo get_string('invalidhomeworkid', 'block_homework');
		}

		echo $OUTPUT->footer();
		return;
		
	case 'group':
		echo $OUTPUT->heading(get_string('selectgroup', 'block_homework'), 3, 'main');
		$gdata = new stdClass();
		$gdata->courseid=$courseid;
		$gdata->groupid=$groupid;
		$groupform = new block_homework_group_form();
		$groupform->set_data($gdata);
		$groupform->display();
		echo $OUTPUT->footer();
		return;

	
	case 'doadd':
		//To do. here collect the data from the form and update in the db using. maybe
		$bmh = new block_homework_manager();
		//get add form
		$add_form = new block_homework_add_form();
		//print_r($add_form);
		$data = $add_form->get_data();
		$ret = $bmh->block_homework_add_homework($data->groupid,$data->courseid,$data->cmid,$data->startdate);
		if($ret){
			$message = get_string('addedsuccessfully','block_homework');
		}else{
			$message = get_string('failedtoadd','block_homework');
		}
		break;
		
	case 'doedit':
		//To do. here collect the data from the form and update in the db using. maybe
		$bmh = new block_homework_manager();
		//get add form
		$edit_form = new block_homework_edit_form();
		//print_r($add_form);
		$data = $edit_form->get_data();
		$ret = $bmh->block_homework_edit_homework($data->homeworkid, $data->groupid,$data->courseid,$data->cmid,$data->startdate);
		if($ret){
			$message = get_string('updatedsuccessfully','block_homework');
		}else{
			$message = get_string('failedtoupdate','block_homework');
		}
		break;
		
	case 'dodelete':
		//To do. here collect the data from the form and update in the db using. maybe
		//get add form
		$delete_form = new block_homework_delete_form();
		$data = $delete_form->get_data();
		$ret = $bmh->block_homework_delete_homework($data->homeworkid);
		if($ret){
			$message = get_string('deletedsuccessfully','block_homework');
		}else{
			$message = get_string('failedtodelete','block_homework');
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
		echo $OUTPUT->heading($message,5,'main');
	}
	
	$groupname = groups_get_group_name($groupid);
	echo $OUTPUT->heading(get_string('homeworklist', 'block_homework', $groupname), 3, 'main');
	
	//group form
	//echo $OUTPUT->heading(get_string('selectgroup', 'block_homework'), 3, 'main');
	$gdata = new stdClass();
	$gdata->courseid=$courseid;
	$gdata->groupid=$groupid;
	$groupform = new block_homework_group_form();
	$groupform->set_data($gdata);
	$groupform->display();

	
	//list of homeworks for current group
	$homeworkdata=$bmh->block_homework_get_homeworks($groupid,$courseid);
	if($homeworkdata){
		echo show_homework_list($homeworkdata,$courseid,$groupid);
	}else{
		echo $OUTPUT->heading( get_string('nohomeworks','block_homework',$groupid),4,'main');
	}
	echo show_buttons($groupid, $groupname);
	echo $OUTPUT->footer();
		
/*
$hubselectorform->display();
if (!empty($errormessage)) {
    echo $errormessage;
}
*/

//load javascript
/*
$commentedcourseids = array(); //result courses with comments only
$courseids = array(); //all result courses
$courseimagenumbers = array(); //number of screenshots of all courses (must be exact same order than $courseids)
if (!empty($courses)) {
    foreach ($courses as $course) {
        if (!empty($course['comments'])) {
            $commentedcourseids[] = $course['id'];
        }
        $courseids[] = $course['id'];
        $courseimagenumbers[] = $course['screenshots'];
    }
}
$PAGE->requires->yui_module('moodle-block_community-comments', 'M.blocks_community.init_comments',
        array(array('commentids' => $commentedcourseids, 'closeButtonTitle' => get_string('close', 'editor'))));
$PAGE->requires->yui_module('moodle-block_community-imagegallery', 'M.blocks_community.init_imagegallery',
        array(array('imageids' => $courseids, 'imagenumbers' => $courseimagenumbers,
                'huburl' => $huburl, 'closeButtonTitle' => get_string('close', 'editor'))));

echo highlight($search, $renderer->course_list($courses, $huburl, $courseid));

//display givememore/Next link if more course can be displayed
if (!empty($courses)) {
    if (($options->givememore + count($courses)) < $coursetotal) {
        $fromformdata['givememore'] = count($courses) + $options->givememore;
        $fromformdata['executesearch'] = true;
        $fromformdata['sesskey'] = sesskey();
        echo $renderer->next_button($fromformdata);
    }
}
*/

function show_buttons($groupid,$groupname){
	global $COURSE;
	
			$addurl = new moodle_url('/blocks/homework/view.php', array('courseid'=>$COURSE->id,'action'=>'add','groupid'=>$groupid));
			echo '<br />' . html_writer::link($addurl,  get_string('addhomework','block_homework',$groupname) );
			$listurl = new moodle_url('/blocks/homework/view.php', array('courseid'=>$COURSE->id,'action'=>'list','groupid'=>$groupid));
			echo '<br />' . html_writer::link($listurl,  get_string('listhomeworks','block_homework',$groupname) );

}

function show_homework_list($homeworkdatas,$courseid,$groupid){

	global $COURSE;
	
	$table = new html_table();
	$table->id = 'block_homework_panel';
	$table->head = array(
		get_string('startdate', 'block_homework'),
		get_string('activitytitle', 'block_homework'),
		get_string('actions', 'block_homework')
	);
	$table->headspan = array(1,1,2);
	$table->colclasses = array(
		'startdate', 'activitytitle', 'edit','delete'
	);
	
	$modinfo = get_fast_modinfo($COURSE);


	foreach ($homeworkdatas as $hwork) {
		$row = new html_table_row();
		//$row->attributes['class'] = 'type-' . $plugin->type . ' name-' . $plugin->type . '_' . $plugin->name;
		/*
		if ($this->page->theme->resolve_image_location('icon', $plugin->type . '_' . $plugin->name)) {
			$icon = $this->output->pix_icon('icon', '', $plugin->type . '_' . $plugin->name, array('class' => 'icon pluginicon'));
		} else {
			$icon = $this->output->pix_icon('spacer', '', 'moodle', array('class' => 'icon pluginicon noicon'));
		}
		$status = $plugin->get_status();
		$row->attributes['class'] .= ' status-'.$status;
		if ($status === core_plugin_manager::PLUGIN_STATUS_MISSING) {
			$msg = html_writer::tag('span', get_string('status_missing', 'core_plugin'), array('class' => 'statusmsg'));
		} else if ($status === core_plugin_manager::PLUGIN_STATUS_NEW) {
			$msg = html_writer::tag('span', get_string('status_new', 'core_plugin'), array('class' => 'statusmsg'));
		} else {
			$msg = '';
		}
		*/
		
		$startdatecell = new html_table_cell(userdate($hwork->startdate,'%d %B %Y'));
		
		$cm = $modinfo->get_cm($hwork->cmid);
		$displayname=$cm->name;
		$activityname  = html_writer::tag('div', $displayname, array('class' => 'displayname'));
		$activitycell  = new html_table_cell($activityname);
		
		$actionurl = '/blocks/homework/view.php';
		$editurl = new moodle_url($actionurl, array('homeworkid'=>$hwork->id,'action'=>'edit','courseid'=>$courseid,'groupid'=>$groupid));
		$editlink = html_writer::link($editurl, get_string('edithomeworklink', 'block_homework'));
		$editcell = new html_table_cell($editlink);
		
		$deleteurl = new moodle_url($actionurl, array('homeworkid'=>$hwork->id,'action'=>'delete','courseid'=>$courseid,'groupid'=>$groupid));
		$deletelink = html_writer::link($deleteurl, get_string('deletehomeworklink', 'block_homework'));
		$deletecell = new html_table_cell($deletelink);

		$row->cells = array(
			$startdatecell, $activitycell, $editcell, $deletecell
		);
		$table->data[] = $row;
	}

    return html_writer::table($table);

}