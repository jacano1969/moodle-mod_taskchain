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
 * mod/taskchain/attempt/hp/6/jcloze/jgloss.js
 *
 * @package    mod
 * @subpackage taskchain
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 */

/**
 * mod/taskchain/attempt/hp/6/jcloze/jgloss.js
 *
 * @package   mod-taskchain
 * @copyright 2010 Gordon Bateson <gordon.bateson@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * JClozeJGloss
 *
 * @param xxx sendallclicks
 * @param xxx forceajax
 */
function JClozeJGloss(sendallclicks, forceajax) {
    this.tasktype = 'JCloze';

    /**
     * initQuestion
     *
     * @param xxx i
     */
    this.initQuestion = function (i) {
        this.questions[i].name = parseInt(i)+1; // gap number
        this.questions[i].type = 2; // 2 = JCloze
        this.questions[i].text = I[i][2]; // clue (=definition)
        this.questions[i].correct = I[i][1][0][0]; // correct answer
    }

    /**
     * onclickCheck
     *
     * @param xxx i
     */
    this.onclickCheck = function (i) {
        if (typeof(i)=='number' && this.questions[i]) {
            this.questions[i].checks++;
            this.questions[i].score = '100%';
        }
        if (window.Finished) {
            return;
        }
        var count = 0;
        for (var i in this.questions) {
            if (this.questions[i].checks) {
                count++;
            }
        }
        if (count) {
            if (count==this.questions.length) {
                window.Score = 100;
                window.Finished = true;
            } else {
                window.Score = Math.floor(100 * (count / this.questions.length));
            }
        }
    }

    this.init(I.length, sendallclicks, forceajax);
}
JClozeJGloss.prototype = new hpTaskAttempt();
