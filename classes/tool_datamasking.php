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

namespace mod_oublog;

/**
 * Implementation of data masking for this plugin.
 *
 * The corresponding test script tool_datamasking_test.php checks every masked field.
 *
 * @package mod_oublog
 * @copyright 2021 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_datamasking implements \tool_datamasking\plugin {

    public function build_plan(\tool_datamasking\plan $plan): void {
        $plan->table('oublog')->add(new \tool_datamasking\unique_email_mask('reportingemail'));
        $plan->table('oublog_comments')->add(new \tool_datamasking\fixed_value_mask(
                'authorname', 'Masked User'));
        $plan->table('oublog_comments')->add(new \tool_datamasking\similar_text_mask(
                'message', true, \tool_datamasking\similar_text_mask::MODEL_POST));
        $plan->table('oublog_comments')->add(new \tool_datamasking\similar_text_mask(
                'title', false, \tool_datamasking\similar_text_mask::MODEL_SUBJECT));
        $plan->table('oublog_comments_moderated')->add(new \tool_datamasking\fixed_value_mask(
                'authorname', 'Masked User'));
        $plan->table('oublog_comments_moderated')->add(new \tool_datamasking\similar_text_mask(
                'message', true, \tool_datamasking\similar_text_mask::MODEL_POST));
        $plan->table('oublog_comments_moderated')->add(new \tool_datamasking\similar_text_mask(
                'title', false, \tool_datamasking\similar_text_mask::MODEL_SUBJECT));
        $plan->table('oublog_edits')->add(new \tool_datamasking\similar_text_mask(
                'oldmessage', true, \tool_datamasking\similar_text_mask::MODEL_POST));
        $plan->table('oublog_edits')->add(new \tool_datamasking\similar_text_mask(
                'oldtitle', false, \tool_datamasking\similar_text_mask::MODEL_SUBJECT));
        // Do blog instances after user names.
        $plan->table('oublog_instances', 200);
        $plan->table('oublog_instances')->add(new \tool_datamasking\user_name_mask(
                'name', 'userid', '', '\'s blog', 'Masked User', 'Masked blog name'));
        $plan->table('oublog_instances')->add(new \tool_datamasking\similar_text_mask(
                'summary', true, \tool_datamasking\similar_text_mask::MODEL_POST));
        $plan->table('oublog_posts')->add(new \tool_datamasking\similar_text_mask(
                'message', true, \tool_datamasking\similar_text_mask::MODEL_POST));
        $plan->table('oublog_posts')->add(new \tool_datamasking\similar_text_mask(
                'title', false, \tool_datamasking\similar_text_mask::MODEL_SUBJECT));

        $plan->table('files')->add(new \tool_datamasking\files_mask('mod_oublog', 'attachment'));
        $plan->table('files')->add(new \tool_datamasking\files_mask('mod_oublog', 'edit'));
        $plan->table('files')->add(new \tool_datamasking\files_mask('mod_oublog', 'message'));
        $plan->table('files')->add(new \tool_datamasking\files_mask('mod_oublog', 'messagecomment'));
    }
}
