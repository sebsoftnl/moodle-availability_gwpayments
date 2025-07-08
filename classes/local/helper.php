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
 * availability_gwpayments helper.
 *
 * File         helper.php
 * Encoding     UTF-8
 *
 * @package     availability_gwpayments
 *
 * @copyright   2021 RvD
 * @author      RvD <helpdesk@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_gwpayments\local;

/**
 * availability_gwpayments helper.
 *
 * @package     availability_gwpayments
 *
 * @copyright   2021 RvD
 * @author      RvD <helpdesk@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper {

    /**
     * Can the given user access the given module (aka, do we have a payment)?
     *
     * @param int $userid
     * @param stdClass|cm_info $cm
     * @return bool
     */
    public static function can_access_cm($userid, $cm) {
        global $DB;
        return $DB->record_exists('payments', ['userid' => $userid,
            'component' => 'availability_gwpayments', 'itemid' => $cm->id, 'paymentarea' => 'cmfee']);
    }

    /**
     * Can the given user access the given section (aka, do we have a payment)?
     *
     * @param int $userid
     * @param stdClass $section
     * @return bool
     */
    public static function can_access_section($userid, $section) {
        global $DB;
        return $DB->record_exists('payments', ['userid' => $userid,
            'component' => 'availability_gwpayments', 'itemid' => $section->id, 'paymentarea' => 'sectionfee']);
    }

    /**
     * Returns the list of currencies that the payment subsystem supports and therefore we can work with.
     *
     * @return array[currencycode => currencyname]
     */
    public static function get_possible_currencies(): array {
        $codes = \core_payment\helper::get_supported_currencies();

        $currencies = [];
        foreach ($codes as $c) {
            $currencies[$c] = new \lang_string($c, 'core_currencies');
        }

        uasort($currencies, function($a, $b) {
            return strcmp($a, $b);
        });

        return $currencies;
    }

}
