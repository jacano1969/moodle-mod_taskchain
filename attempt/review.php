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
 * mod/taskchain/attempt/review.php
 *
 * @package    mod
 * @subpackage taskchain
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 */

/** Prevent direct access to this script */
defined('MOODLE_INTERNAL') || die();

/**
 * mod_taskchain_attempt_review
 *
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod
 * @subpackage taskchain
 */
class mod_taskchain_attempt_review {

    /**
     * return true if question text should appear on review page, or false otherwise
     *
     * @return boolean
     */
    static function show_question_text()  {
        return true;
    }

    /**
     * attempt_fields
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    static function attempt_fields()  {
        return array('tnumber', 'score', 'penalties', 'status', 'duration', 'timestart');
    }

    /**
     * response_text_fields
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    static function response_text_fields()  {
        return array('correct', 'ignored', 'wrong');
    }

    /**
     * response_num_fields
     *
     * @return xxx
     * @todo Finish documenting this function
     */
    static function response_num_fields()  {
        return array('score', 'weighting', 'hints', 'clues', 'checks');
    }

    /**
     * does this output format allow task attempts to be reviewed?
     *
     * @return boolean true if attempts can be reviewed, otherwise false
     */
    static function provide_review()  {
        return true;
    }

    /**
     * can the current task attempt be reviewed now?
     *
     * @return boolean true if attempt can be reviewed, otherwise false
     */
    static function can_reviewattempts()  {
        return self::provide_review();

        // when $TC->task->reviewoptions are implemented,
        // we can do something like the following ...

        if (self::provide_review() && $TC->task->reviewoptions) {
            if ($attempt = $TC->get_attempt()) {
                if ($TC->task->reviewoptions & mod_taskchain::REVIEW_DURINGATTEMPT) {
                    // during attempt
                    if ($TC->taskattempt->status==mod_taskchain::STATUS_INPROGRESS) {
                        return true;
                    }
                }
                if ($TC->task->reviewoptions & mod_taskchain::REVIEW_AFTERATTEMPT) {
                    // after attempt (but before task closes)
                    if ($TC->taskattempt->status==mod_taskchain::STATUS_COMPLETED) {
                        return true;
                    }
                    if ($TC->taskattempt->status==mod_taskchain::STATUS_ABANDONED) {
                        return true;
                    }
                    if ($TC->taskattempt->status==mod_taskchain::STATUS_TIMEDOUT) {
                        return true;
                    }
                }
                if ($TC->task->reviewoptions & mod_taskchain::REVIEW_AFTERCLOSE) {
                    // after the task closes
                    if ($TC->timeclose < $TC->time) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * review
     *
     * @uses $DB
     * @param xxx $TC
     * @param xxx $class
     * @return xxx
     * @todo Finish documenting this function
     */
    static function review($TC, $class)  {
        global $DB;

        // for the time-being we set this setting manually here
        // but one day it will be settable in "mod/taskchain/mod_form.php"
        $TC->task->reviewoptions = mod_taskchain::REVIEW_DURINGATTEMPT | mod_taskchain::REVIEW_AFTERATTEMPT | mod_taskchain::REVIEW_AFTERCLOSE;

        // set $reviewoptions to relevant part of $TC->task->reviewoptions
        $reviewoptions = 0;
        if ($TC->can_reviewallattempts()) {
            // teacher can always review (anybody's) task attempts
            $reviewoptions = (mod_taskchain::REVIEW_AFTERATTEMPT | mod_taskchain::REVIEW_AFTERCLOSE);
        } else if ($TC->timeclose && $TC->timeclose > $TC->time) {
            // task is closed
            if ($TC->task->reviewoptions & mod_taskchain::REVIEW_AFTERCLOSE) {
                // user can review task attempt after task closes
                $reviewoptions = ($TC->task->reviewoptions & mod_taskchain::REVIEW_AFTERCLOSE);
            } else if ($TC->task->reviewoptions & mod_taskchain::REVIEW_AFTERATTEMPT) {
                return get_string('noreviewbeforeclose', 'taskchain', userdate($TC->timeclose));
            } else {
                return get_string('noreview', 'taskchain');
            }
        } else {
            // task is still open
            if ($TC->task->reviewoptions & mod_taskchain::REVIEW_AFTERATTEMPT) {
                // user can review task attempt while task is open
                $reviewoptions = ($TC->task->reviewoptions & mod_taskchain::REVIEW_AFTERATTEMPT);
            } else if ($TC->task->reviewoptions & mod_taskchain::REVIEW_AFTERCLOSE) {
                return get_string('noreviewafterclose', 'taskchain');
            } else {
                return get_string('noreview', 'taskchain');
            }
        }

        // if necessary, remove score and weighting fields
        $response_num_fields = $class::response_num_fields();
        if (! ($reviewoptions & mod_taskchain::REVIEW_SCORES)) {
            $response_num_fields = preg_grep('/^score|weighting$/', $response_num_fields, PREG_GREP_INVERT);
        }

        // if necessary, remove reponses fields
        $response_text_fields = $class::response_text_fields();
        if (! ($reviewoptions & mod_taskchain::REVIEW_RESPONSES)) {
            $response_text_fields = array();
        }

        // set flag to remove, if necessary, labels that show whether responses are correct or not
        if (! ($reviewoptions & mod_taskchain::REVIEW_ANSWERS)) {
            $neutralize_text_fields = true;
        } else {
            $neutralize_text_fields = false;
        }

        $table = new html_table();
        $table->id          = 'responses';
        $table->class       = 'generaltable generalbox';
        $table->cellpadding = 4;
        $table->cellspacing = 0;

        if (count($response_num_fields)) {
            $question_colspan  = count($response_num_fields) * 2;
            $textfield_colspan = $question_colspan - 1;
        } else {
            $question_colspan  = 2;
            $textfield_colspan = 1;
        }

        $strtimeformat = get_string('strftimerecentfull');

        $callback = array($class, 'attempt_fields');
        $attempt_fields = call_user_func($callback);

        foreach ($attempt_fields as $field) {
            $row = new html_table_row();

            // add heading
            $text = $class::format_attempt_heading($field);
            $cell = new html_table_cell($text, array('class'=>'attemptfield'));
            $row->cells[] = $cell;

            // add data
            $callback = array($class, 'format_attempt_data');
            $params = array($TC->taskattempt, $field, $strtimeformat);
            $text = call_user_func_array($callback , $params);

            $cell = new html_table_cell($text, array('class'=>'attemptvalue'));
            $cell->colspan = $textfield_colspan;
            $row->cells[] = $cell;

            $table->data[] = $row;
        }

        // get questions and responses relevant to this task attempt
        $questions = $DB->get_records('taskchain_questions', array('taskid' => $TC->task->id));
        $responses = $DB->get_records('taskchain_responses', array('attemptid' => $TC->taskattempt->id));

        if (empty($questions) || empty($responses)) {
            $row = new html_table_row();

            $cell = new html_table_cell(get_string('noresponses', 'taskchain'), array('class'=>'noresponses'));
            $cell->colspan = $question_colspan;

            $row->cells[] = $cell;
            $table->data[] = $row;

        } else {

            // we have some responses, so print them
            foreach ($responses as $response) {

                if (empty($questions[$response->questionid])) {
                    continue; // invalid question id - shouldn't happen !!
                }

                // add separator
                if (count($table->data)) {
                    $class::add_separator($table, $question_colspan);
                }

                // question text
                if ($class::show_question_text()) {
                    if ($text = mod_taskchain::get_question_text($questions[$response->questionid])) {
                        $class::add_question_text($table, $text, $question_colspan);
                    }
                }

                // string fields
                $neutral_text = '';
                foreach ($response_text_fields as $field) {
                    if (empty($response->$field)) {
                        continue; // shouldn't happen !!
                    }

                    $text = array();
                    if ($records = mod_taskchain::get_strings($response->$field)) {
                        foreach ($records as $record) {
                            $text[] = $record->string;
                        }
                    }
                    unset($records);

                    if (! $text = implode(',', $text)) {
                        continue; // skip empty rows
                    }

                    if ($neutralize_text_fields) {
                        $neutral_text .= ($neutral_text ? ',' : '').$text;
                    } else {
                        $class::add_text_field($table, $field, $text, $textfield_colspan);
                    }
                }
                if ($neutral_text) {
                    $class::add_text_field($table, 'responses', $neutral_text, $textfield_colspan);
                }

                // numeric fields
                $row = new html_table_row();
                foreach ($response_num_fields as $field) {
                    $class::add_num_field($row, $field, $response->$field);
                }
                $table->data[] = $row;
            }
        }

        return html_writer::table($table);
    }

    /**
     * format_attempt_heading
     *
     * @param xxx $field
     * @return xxx
     * @todo Finish documenting this function
     */
    static function format_attempt_heading($field) {
        switch ($field) {
            case 'score'     : return get_string('score', 'quiz');
            case 'timestart' : return get_string('time', 'quiz');
            default          : return get_string($field, 'taskchain');
        }
    }

    /**
     * format_attempt_data
     *
     * @param xxx $attempt
     * @param xxx $field
     * @param xxx $strtimeformat
     * @return xxx
     * @todo Finish documenting this function
     */
    static function format_attempt_data($attempt, $field, $strtimeformat) {
        switch ($field) {
            case 'duration'  : return (($duration = $attempt->$field) ? format_time($duration) : '');
            case 'status'    : return mod_taskchain::format_status($attempt->$field);
            case 'timestart' : return trim(userdate($attempt->$field, $strtimeformat));
            default          : return $attempt->$field;
        }
    }

    /**
     * add_separator
     *
     * @param xxx $table (passed by reference)
     * @param xxx $colspan
     * @todo Finish documenting this function
     */
    static function add_separator(&$table, $colspan)  {
        $row = new html_table_row();

        $text = html_writer::tag('div', '', array('class' => 'tabledivider'));
        $cell = new html_table_cell($text);
        $cell->colspan = $colspan;

        $row->cells[] = $cell;
        $table->data[] = $row;
    }

    /**
     * add_question_text
     *
     * @param xxx $table (passed by reference)
     * @param xxx $text
     * @param xxx $colspan
     * @todo Finish documenting this function
     */
    static function add_question_text(&$table, $text, $colspan)  {
        $row = new html_table_row();

        $cell = new html_table_cell($text, array('class'=>'questiontext'));
        $cell->colspan = $colspan;

        $row->cells[] = $cell;
        $table->data[] = $row;
    }

    /**
     * add_text_field
     *
     * @param xxx $table (passed by reference)
     * @param xxx $field
     * @param xxx $text
     * @param xxx $colspan
     * @todo Finish documenting this function
     */
    static function add_text_field(&$table, $field, $text, $colspan)  {
        $row = new html_table_row();

        // heading
        $cell = new html_table_cell(get_string($field, 'taskchain'), array('class'=>'responsefield'));
        $row->cells[] = $cell;

        // data
        $cell = new html_table_cell($text, array('class'=>'responsevalue'));
        $cell->colspan = $colspan;
        $row->cells[] = $cell;

        $table->data[] = $row;
    }

    /**
     * add_num_field
     *
     * @param xxx $row (passed by reference)
     * @param xxx $field
     * @param xxx $value
     * @todo Finish documenting this function
     */
    static function add_num_field(&$row, $field, $value)  {
        // heading
        $cell = new html_table_cell(get_string($field, 'taskchain'), array('class'=>'responsefield'));
        $row->cells[] = $cell;

        // data
        $cell = new html_table_cell($value, array('class'=>'responsevalue'));
        $row->cells[] = $cell;
    }
}
