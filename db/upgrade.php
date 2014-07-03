
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
 * Upgrade script for the quiz module.
 *
 * @package    block_homework
 * @copyright  2014 Justin Hunt (poodllsupport@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Homework module upgrade function.
 * @param string $oldversion the version we are upgrading from.
 */
function xmldb_block_homework_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();
  if ($oldversion < 2014063000) {

       
        // Define table block_homework to be created.
        $table = new xmldb_table('block_homework');

        // Adding fields to table block_homework.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('groupid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('cmid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('startdate', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('editedby', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        // Adding keys to table block_homework.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for block_homework.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
	   
        // Homework savepoint reached.
        upgrade_block_savepoint(true, 2014063000, 'homework');
    }
}