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
 * Payment subsystem callback implementation for availability_gwpayments.
 *
 * File         service_provider.php
 * Encoding     UTF-8
 *
 * @package     availability_gwpayments
 *
 * @copyright   2021 Ing. R.J. van Dongen
 * @author      Ing. R.J. van Dongen <rogier@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_gwpayments\payment;

/**
 * Payment subsystem callback implementation for availability_gwpayments.
 *
 * @package     availability_gwpayments
 *
 * @copyright   2021 Ing. R.J. van Dongen
 * @author      Ing. R.J. van Dongen <rogier@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class service_provider implements \core_payment\local\callback\service_provider {

    /**
     * Generate payable data.
     *
     * This is a utility method that can modify the actual variables and modify the payment
     * amount. This is where we transform the initial cost for e.g. coupons, discounts etc etc.
     *
     * @param \stdClass $instance enrol instance.
     * @param string $paymentarea Payment area
     * @param int $itemid The enrolment instance id
     * @return \stdClass
     */
    private static function generate_payabledata(\stdClass $instance, string $paymentarea, int $itemid) {
        $result = (object) [
            'amount' => $instance->cost,
            'currency' => $instance->currency,
            'accountid' => $instance->customint1
        ];

        // We might eventually provide voucher codes, much as in enrol_gwpayments.
        // For this reason only, this method is still kept in place.

        // And return result.
        return $result;
    }

    /**
     * Callback function that returns the enrolment cost and the accountid
     * for the course that $instanceid enrolment instance belongs to.
     *
     * @param string $paymentarea Payment area
     * @param int $instanceid The enrolment instance id
     * @return \core_payment\local\entities\payable
     */
    public static function get_payable(string $paymentarea, int $instanceid): \core_payment\local\entities\payable {
        global $DB;

        switch ($paymentarea) {
            case 'cmfee':
                $cm = $DB->get_record('course_modules', ['id' => $instanceid]);
                $avinfo = json_decode($cm->availability);
                break;
            case 'sectionfee':
                $cs = $DB->get_record('course_sections', ['id' => $instanceid]);
                $avinfo = json_decode($cs->availability);
        }

        $data = null;
        foreach ($avinfo->c as $info) {
            if ($info->type === 'gwpayments') {
                $data = $info;
                break;
            }
        }

        if (empty($data)) {
            return new \core_payment\local\entities\payable(0, 'EUR', 0);
        } else {
            return new \core_payment\local\entities\payable($data->cost, $data->currency, $data->accountid);
        }
    }

    /**
     * Callback function that returns the URL of the page the user should be redirected to in the case of a successful payment.
     *
     * @param string $paymentarea Payment area
     * @param int $instanceid The enrolment instance id
     * @return \moodle_url
     */
    public static function get_success_url(string $paymentarea, int $instanceid): \moodle_url {
        global $DB;

        switch ($paymentarea) {
            case 'cmfee':
                list($course, $cm) = get_course_and_cm_from_cmid($instanceid);
                return new \moodle_url('/course/view.php', ['id' => $course->id]);
            case 'sectionfee':
                $section = $DB->get_record('course_sections', ['id' => $instanceid]);
                return new \moodle_url('/course/view.php', ['id' => $section->course]);
        }
    }

    /**
     * Callback function that delivers what the user paid for to them.
     *
     * @param string $paymentarea
     * @param int $instanceid The enrolment instance id
     * @param int $paymentid payment id as inserted into the 'gwpayments' table, if needed for reference
     * @param int $userid The userid the order is going to deliver to
     * @return bool Whether successful or not
     */
    public static function deliver_order(string $paymentarea, int $instanceid, int $paymentid, int $userid): bool {
        // I don't think we really need to do anything here; we could simply check for the existence of a PAYMENT record.
        // But really, there's nothing to deliver.

        return true;
    }

}
