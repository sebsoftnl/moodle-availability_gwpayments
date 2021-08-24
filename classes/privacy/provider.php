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
 * Privacy Subsystem implementation for availability_gwpayments.
 *
 * File         provider.php
 * Encoding     UTF-8
 *
 * @package     availability_gwpayments
 *
 * @copyright   Ing. R.J. van Dongen
 * @author      R.J. van Dongen <rogier@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_gwpayments\privacy;

use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use core_payment\helper as payment_helper;

/**
 * Privacy Subsystem for availability_gwpayments implementing null_provider.
 *
 * @package     availability_gwpayments
 *
 * @copyright   Ing. R.J. van Dongen
 * @author      R.J. van Dongen <rogier@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\null_provider,
    \core_payment\privacy\consumer_provider
{
    /**
     * Get the language string identifier with the component's language
     * file to explain why this plugin stores no data.
     *
     * @return  string
     */
    public static function get_reason(): string {
        return 'privacy:metadata';
    }

    /**
     * Return contextid for the provided payment data
     *
     * @param string $paymentarea Payment area
     * @param int $itemid The item id
     * @return int|null
     */
    public static function get_contextid_for_payment(string $paymentarea, int $itemid): ?int {
        global $DB;

        switch ($paymentarea) {
            case 'cmfee':
                $sql = "SELECT ctx.id
                          FROM {course_modules} cm
                          JOIN {context} ctx ON (cm.id = ctx.instanceid AND ctx.contextlevel = :contextmodule)
                         WHERE cm.id = :cmid";
                $params = [
                    'contextmodule' => CONTEXT_MODULE,
                    'cmid' => $itemid,
                ];
                break;
            case 'sectionfee':
                $sql = "SELECT ctx.id
                          FROM {course_sections} cs
                          JOIN {course} c ON c.id = cs.course
                          JOIN {context} ctx ON (c.id = ctx.instanceid AND ctx.contextlevel = :contextcourse)
                         WHERE cs.id = :sectionid";
                $params = [
                    'contextcourse' => CONTEXT_COURSE,
                    'sectionid' => $itemid,
                ];
                break;
        }

        $contextid = $DB->get_field_sql($sql, $params);

        return $contextid ?: null;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if ($context instanceof \context_course) {
            $sql = "SELECT p.userid
                      FROM {payments} p
                      JOIN {course_sections} cs ON (p.component = :component AND p.itemid = cs.id AND p.paymentarea = :area)
                      JOIN {course} c ON c.id = cs.course
                     WHERE c.id = :courseid";
            $params = [
                'component' => 'availability_gwpayments',
                'area' => 'sectionfee',
                'courseid' => $context->instanceid,
            ];
            $userlist->add_from_sql('userid', $sql, $params);
        } else if ($context instanceof \context_module) {
            $sql = "SELECT p.userid
                      FROM {payments} p
                      JOIN {course_modules} cm ON (p.component = :component AND p.itemid = cm.id AND p.paymentarea = :area)";
            $params = [
                'component' => 'availability_gwpayments',
                'area' => 'cmfee',
            ];
            $userlist->add_from_sql('userid', $sql, $params);
        }
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $subcontext = [
            get_string('pluginname', 'availability_gwpayments'),
        ];
        foreach ($contextlist as $context) {
            if (!$context instanceof \context_course) {
                continue;
            }
            $coursesections = $DB->get_records('course_sections', ['course' => $context->instanceid]);
            $coursesections = array_filter($coursesections, function($section) {
                if (empty($section->availability)) {
                    return false;
                }
                $avcondition = json_decode($section->availability);
                foreach ($avcondition->c as $condition) {
                    if ($condition->type === 'availability_gwpayments') {
                        return true;
                    }
                }
                return false;
            });

            foreach ($coursesections as $section) {
                \core_payment\privacy\provider::export_payment_data_for_user_in_context(
                    $context,
                    $subcontext,
                    $contextlist->get_user()->id,
                    'availability_gwpayments',
                    'sectionfee', // We know course context equals section fees because we developed it this way.
                    $section->id
                );
            }
        }
        foreach ($contextlist as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }
            $modules = $DB->get_records('course_modules', ['id' => $context->instanceid]);
            foreach ($modules as $module) {
                \core_payment\privacy\provider::export_payment_data_for_user_in_context(
                    $context,
                    $subcontext,
                    $contextlist->get_user()->id,
                    'availability_gwpayments',
                    'cmfee', // We know module context equals course module fees because we developed it this way.
                    $module->id
                );
            }
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param context $context The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        if ($context instanceof \context_course) {
            $sql = "SELECT p.id
                      FROM {payments} p
                      JOIN {course_sections} cs ON (p.component = :component AND p.itemid = cs.id AND p.paymentarea = :area)
                      JOIN {course} c ON c.id = cs.course
                     WHERE c.id = :courseid";
            $params = [
                'component' => 'availability_gwpayments',
                'area' => 'sectionfee',
                'courseid' => $context->instanceid,
            ];

            \core_payment\privacy\provider::delete_data_for_payment_sql($sql, $params);
        } else if ($context instanceof \context_module) {
            $sql = "SELECT p.id
                      FROM {payments} p
                      JOIN {course_modules} cm ON (p.component = :component AND p.itemid = cm.id AND p.paymentarea = :area)";
            $params = [
                'component' => 'availability_gwpayments',
                'area' => 'cmfee',
            ];

            \core_payment\privacy\provider::delete_data_for_payment_sql($sql, $params);
        }
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $contexts = $contextlist->get_contexts();

        $courseids = [];
        $cmids = [];
        foreach ($contexts as $context) {
            if ($context instanceof \context_course) {
                $courseids[] = $context->instanceid;
            } else if ($context instanceof \context_module) {
                $cmids[] = $context->instanceid;
            }
        }

        // Context course.
        [$cinsql, $cinparams] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED);
        $sql = "SELECT p.id
                      FROM {payments} p
                      JOIN {course_sections} cs ON (p.component = :component AND p.itemid = cs.id AND p.paymentarea = :area)
                      JOIN {course} c ON c.id = cs.course
                      WHERE p.userid = :userid AND c.id $cinsql";
        $params = $cinparams + [
            'component' => 'availability_gwpayments',
            'area' => 'sectionfee',
            'userid' => $contextlist->get_user()->id,
        ];
        \core_payment\privacy\provider::delete_data_for_payment_sql($sql, $params);

        // COntext module.
        [$cminsql, $cminparams] = $DB->get_in_or_equal($cmids, SQL_PARAMS_NAMED);
        $sql = "SELECT p.id
                      FROM {payments} p
                      JOIN {course_modules} cm ON (p.component = :component AND p.itemid = cm.id AND p.paymentarea = :area)
                      WHERE p.userid = :userid AND cm.id $cminsql";
        $params = $cminparams + [
            'component' => 'availability_gwpayments',
            'area' => 'cmfee',
            'userid' => $contextlist->get_user()->id,
        ];
        \core_payment\privacy\provider::delete_data_for_payment_sql($sql, $params);
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();

        if ($context instanceof \context_course) {
            [$usersql, $userparams] = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED);
            $sql = "SELECT p.id
                      FROM {payments} p
                      JOIN {course_sections} cs ON (p.component = :component AND p.itemid = cs.id AND p.paymentarea = :area)
                      JOIN {course} c ON c.id = cs.course
                     WHERE c.id = :courseid AND p.userid $usersql";
            $params = $userparams + [
                'component' => 'availability_gwpayments',
                'area' => 'sectionfee',
                'courseid' => $context->instanceid,
            ];

            \core_payment\privacy\provider::delete_data_for_payment_sql($sql, $params);
        } else if ($context instanceof \context_system) {
            // Orphaned.
            [$usersql, $userparams] = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED);
            $sql = "SELECT p.id
                      FROM {payments} p
                      JOIN {course_modules} cm ON (p.component = :component AND p.itemid = cm.id AND p.paymentarea = :area)
                      WHERE p.userid $usersql";
            $params = $userparams + [
                'component' => 'availability_gwpayments',
                'area' => 'cmfee',
            ];

            \core_payment\privacy\provider::delete_data_for_payment_sql($sql, $params);
        }
    }

}
