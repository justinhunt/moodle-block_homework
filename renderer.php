<?php

///////////////////////////////////////////////////////////////////////////
//                                                                       //
// This file is part of Moodle - http://moodle.org/                      //
// Moodle - Modular Object-Oriented Dynamic Learning Environment         //
//                                                                       //
// Moodle is free software: you can redistribute it and/or modify        //
// it under the terms of the GNU General Public License as published by  //
// the Free Software Foundation, either version 3 of the License, or     //
// (at your option) any later version.                                   //
//                                                                       //
// Moodle is distributed in the hope that it will be useful,             //
// but WITHOUT ANY WARRANTY; without even the implied warranty of        //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         //
// GNU General Public License for more details.                          //
//                                                                       //
// You should have received a copy of the GNU General Public License     //
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.       //
//                                                                       //
///////////////////////////////////////////////////////////////////////////

/**
 * Block Homework renderer.
 * @package   block_homework
 * @copyright 2014 Justin Hunt (poodllsupport@gmail.com)
 * @author    Justin Hunt
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_homework_renderer extends plugin_renderer_base {
	
	/**
	 * Return a single homework item for display in a block
	 * @param object homework object
	 * @return string html of item
	 */
    public function fetch_homework_item($onehomework){	
    	global $CFG;
    
		if ($onehomework->cm->modname === 'resources') {
			$icon = $this->output->pix_icon('icon', '', 'mod_page', array('class' => 'icon'));
		} else {
			$icon = '<img src="'.$this->output->pix_url('icon', $onehomework->cm->modname) . '" class="icon" alt="" />';
		}
		
		$modurl = $CFG->wwwroot.'/mod/'.$onehomework->cm->modname.'/view.php?id=' . $onehomework->cm->id;
		$displaydate = userdate($onehomework->startdate,'%d %B %Y'); 
		$displaylink = html_writer::link($modurl,$icon . $onehomework->cm->name);
		
		$homeworkitem = $displaydate . ' ' . $displaylink;
		return $homeworkitem;
    }
    
    /**
	 * Return "manage homeworks" link
	 * @param int course id
	 * @param int groupid
	 * @return string html of manage homework item
	 */
     public function fetch_manage_homeworks_item($courseid, $groupid){	
		$url = new moodle_url('/blocks/homework/view.php', array('courseid'=>$courseid,'action'=>'list','groupid'=>$groupid));
		$manageitem = html_writer::link($url, get_string('managehomeworks','block_homework'));
		return $manageitem;
    }
    
	
	/**
	 * Return the add list buttons at bottom of table (ugly
	 * @param integer $groupid
	 * @param integer $groupname
	 * @return string html of buttons
	 */
	function show_buttons($courseid, $groupid,$groupname){
		$addurl = new moodle_url('/blocks/homework/view.php', array('courseid'=>$courseid,'action'=>'add','groupid'=>$groupid));
		$listurl = new moodle_url('/blocks/homework/view.php', array('courseid'=>$courseid,'action'=>'list','groupid'=>$groupid));				
		return $this->output->single_button($addurl,get_string('addhomework','block_homework',$groupname) );
	}

	/**
	 * Return the html table of homeworks for a group  / course
	 * @param array homework objects
	 * @param integer $courseid
	 * @param integer $groupid
	 * @return string html of table
	 */
	function show_homework_list($homeworkdatas,$modinfo,$courseid,$groupid){
	
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

		//sort by start date
		core_collator::asort_objects_by_property($homeworkdatas,'startdate',core_collator::SORT_NUMERIC);

		//loop through the homoworks and add to table
		foreach ($homeworkdatas as $hwork) {
			$row = new html_table_row();
		
		
			$startdatecell = new html_table_cell(userdate($hwork->startdate,'%d %B %Y'));
			try{
				$cm = $modinfo->get_cm($hwork->cmid);
			} catch (Exception $e) {
				block_homework_purge_old_activities();
    			error_log( 'An assigned homework has been deleted: ' .  $e->getMessage());
    			continue;
			}
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

}
