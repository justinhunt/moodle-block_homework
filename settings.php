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


 $modoptions = array('scorm'=>'Scorm', 'forum'=>'Forum', 'page'=>'Page', 'assign'=>'Assignment',  'wiki'=>'Wiki', 'file'=>'File', 'quiz'=>'Quiz');							
$settings->add(new admin_setting_configmultiselect('block_homework/homeworktypes', 
		get_string('label_homeworktypes', 'block_homework'),  
		get_string('desc_homeworktypes', 'block_homework'), 
		array('scorm'), $modoptions));	
