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
 * mod/taskchain/backup/moodle2/restore_taskchain_activity_task.class.php
 *
 * @package    mod
 * @subpackage taskchain
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 */

/** Prevent direct access to this script */
defined('MOODLE_INTERNAL') || die();

/** Include required files */
require_once($CFG->dirroot . '/mod/taskchain/backup/moodle2/restore_taskchain_stepslib.php');

/**
 * restore_taskchain_activity_task
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod
 * @subpackage taskchain
 */
class restore_taskchain_activity_task extends restore_activity_task {

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        // $this->add_step(new restore_taskchain_activity_structure_step('taskchain_structure', 'taskchain.xml'));
    }

    /**
     * Define the contents in the activity that must be
     * processed by the link decoder
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    static public function define_decode_contents() {
        return array();
        //return array(
        //    new restore_decode_content('taskchain', array('entrytext', 'exittext'), 'taskchain')
        //);
    }

    /**
     * Define the decoding rules for links belonging
     * to the activity to be executed by the link decoder
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    static public function define_decode_rules() {
        return array();
        //return array(
        //    new restore_decode_rule('TASKCHAINVIEWBYID', '/mod/taskchain/view.php?id=$1', 'course_module'),
        //    new restore_decode_rule('TASKCHAININDEX', '/mod/taskchain/index.php?id=$1', 'course')
        //);
    }
}
