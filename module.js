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
 * JavaScript library for the Ratings Block.
 *
 * @package    block
 * @subpackage homework
 * @copyright  2014 Justin Hunt  {@link http://poodll.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

M.block_homework = {

		str: [],
		opts: [],

        /**
         *  Initialize homework block
         */
        init: function (Y,opts)
        {
        	//store the strings we need
       		 this.str = Y.merge(M.str.moodle, M.str.block_homework);
       		 this.opts = opts;
        
            var sections = Y.Node.all('.course-content ' + M.course.format.get_section_wrapper(Y));
            sections.each(function (section)
            {
                section.all('li.activity').each(function (activity)
                {
                	var cmid = activity.get('id').replace('module-','');
           
                	//if this is a current homework, flag it as so
           	  	    var currenthomeworks = this.opts['currenthomeworks'];
                	if(currenthomeworks[cmid]){
                		activity.addClass('block_homework_currenthomework');
                	}
                	
                    var homework = this.create_homework_command(cmid);
                    var menu = activity.one('ul[role="menu"]');
                    if (menu) {
                        menu.append(Y.Node.create('<li role="presentation"/>').append(homework.set('role', 'menuitem')));
                        if (menu.getStyle('display') == 'none') {
                            homework.append(homework.get('title'));
                        }
                  // } else {
                   //     activity.one('.commands').append(homework);
                    }
                   // homework.on('click', this.on_homework, this);
                    
                }, this);
            }, this);
           
        }, //end of function
        
        /**
         *  Create a command icon
         *  
         */
        create_homework_command: function(cmid)
        {
        	var strname = this.str['addtohomework'];
        	var iconcss ="block_homework_add";
        	var iconpix ="t/dock_to_block";
        	var url= M.cfg.wwwroot + '/blocks/homework/view.php?courseid=' + this.opts['courseid'] +'&action=quickadd&activityid='+cmid;
            return Y.Node.create('<a href="' + url + '"/>')
               .addClass(iconcss)
                .set('title', strname)
                .append(
                    Y.Node.create('<img class="iconsmall"/>')
                        .set('alt', strname)
                        .set('src', M.util.image_url(iconpix))
                    );
        }
};//end of class