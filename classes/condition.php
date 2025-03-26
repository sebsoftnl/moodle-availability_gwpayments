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
 * availability_gwpayments condition.
 *
 * File         lib.php
 * Encoding     UTF-8
 *
 * @package     availability_gwpayments
 *
 * @copyright   2021 Ing. R.J. van Dongen
 * @author      Ing. R.J. van Dongen <rogier@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_gwpayments;

use availability_gwpayments\payment\service_provider;

defined('MOODLE_INTERNAL') || die();

/**
 * availability_gwpayments condition.
 *
 * @package     availability_gwpayments
 *
 * @copyright   2021 Ing. R.J. van Dongen
 * @author      Ing. R.J. van Dongen <rogier@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class condition extends \core_availability\condition {

    private $accountid;
    private $currency;
    private $cost;
    private $vat;

    /**
     * Constructor.
     *
     * @param \stdClass $structure Data structure from JSON decode
     * @throws \coding_exception If invalid data structure.
     */
    public function __construct($structure) {
        if (isset($structure->accountid)) {
            $this->accountid = $structure->accountid;
        }
        if (isset($structure->currency)) {
            $this->currency = $structure->currency;
        }
        if (isset($structure->cost)) {
            $this->cost = $structure->cost;
        }
        if (isset($structure->vat)) {
            $this->vat = $structure->vat;
        }
    }

    /**
     * Save data.
     * @return \stdClass
     */
    public function save() {
        $result = (object) array('type' => 'gwpayments');
        if ($this->accountid) {
            $result->accountid = $this->accountid;
        }
        if ($this->currency) {
            $result->currency = $this->currency;
        }
        if ($this->cost) {
            $result->cost = $this->cost;
        }
        if (strlen((string)$this->vat) > 0) {
            $result->vat = $this->vat;
        }
        return $result;
    }

    /**
     * Returns a JSON object which corresponds to a condition of this type.
     *
     * Intended for unit testing, as normally the JSON values are constructed
     * by JavaScript code.
     *
     * @param int $accountid The account used for gwpayments
     * @param string $currency The currency to charge the user
     * @param string $cost The cost to charge the user
     * @param string $vat The VAT to charge the user
     * @return stdClass Object representing condition
     */
    public static function get_json($accountid, $currency, $cost, $vat) {
        return (object) array(
            'type' => 'gwpayments',
            'accountid' => $accountid,
            'currency' => $currency,
            'cost' => $cost,
            'vat' => $vat
        );
    }

    /**
     * Determines whether a particular item is currently available
     * according to this availability condition.
     *
     * If implementations require a course or modinfo, they should use
     * the get methods in $info.
     *
     * The $not option is potentially confusing. This option always indicates
     * the 'real' value of NOT. For example, a condition inside a 'NOT AND'
     * group will get this called with $not = true, but if you put another
     * 'NOT OR' group inside the first group, then a condition inside that will
     * be called with $not = false. We need to use the real values, rather than
     * the more natural use of the current value at this point inside the tree,
     * so that the information displayed to users makes sense.
     *
     * @param bool $not Set true if we are inverting the condition
     * @param info $info Item we're checking
     * @param bool $grabthelot Performance hint: if true, caches information
     *   required for all course-modules, to make the front page and similar
     *   pages work more quickly (works only for current user)
     * @param int $userid User ID to check availability for
     * @return bool True if available
     */
    public function is_available($not, \core_availability\info $info, $grabthelot, $userid) {
        $context = $info->get_context();
        if ($context->contextlevel === CONTEXT_MODULE) {
            // Course module.
            $allow = local\helper::can_access_cm($userid, $info->get_course_module());
        } else {
            // Assuming section.
            $allow = local\helper::can_access_section($userid, $info->get_section());
        }

        if ($not) {
            $allow = !$allow;
        }
        return $allow;
    }

    /**
     * Obtains a string describing this restriction (whether or not
     * it actually applies). Used to obtain information that is displayed to
     * students if the activity is not available to them, and for staff to see
     * what conditions are.
     *
     * The $full parameter can be used to distinguish between 'staff' cases
     * (when displaying all information about the activity) and 'student' cases
     * (when displaying only conditions they don't meet).
     *
     * If implementations require a course or modinfo, they should use
     * the get methods in $info.
     *
     * The special string <AVAILABILITY_CMNAME_123/> can be returned, where
     * 123 is any number. It will be replaced with the correctly-formatted
     * name for that activity.
     *
     * @param bool $full Set true if this is the 'full information' view
     * @param bool $not Set true if we are inverting the condition
     * @param info $info Item we're checking
     * @return string Information string (for admin) about all restrictions on
     *   this item
     */
    public function get_description($full, $not, \core_availability\info $info) {
        return $this->get_either_description($not, false, $info);
    }

    /**
     * Shows the description using the different lang strings for the standalone
     * version or the full one.
     *
     * @param bool $not True if NOT is in force
     * @param bool $standalone True to use standalone lang strings
     * @param bool $info       Information about the availability condition and module context
     */
    protected function get_either_description($not, $standalone, $info) {
        global $OUTPUT, $PAGE;
        $config = get_config('availability_gwpayments');
        $disablepaymentonmisconfig = (bool)$config->disablepaymentonmisconfig;

        $context = $info->get_context();
        if ($context->contextlevel === CONTEXT_MODULE) {
            // Course module.
            $instanceid = $info->get_course_module()->id;
            $description = $info->get_course_module()->get_formatted_name();
            $paymentarea = 'cmfee';
        } else {
            // Assuming section.
            $instanceid = $info->get_section()->id;
            $description = $info->get_section()->name;
            $paymentarea = 'sectionfee';
        }

        $notifications = [];
        $canpaymentbemade = $this->can_payment_be_made($paymentarea, $instanceid, $notifications);

        $data = (object)[
            'isguestuser' => isguestuser(),
            'cost' => \core_payment\helper::get_cost_as_string($this->cost, $this->currency),
            'component' => 'availability_gwpayments',
            'paymentarea' => $paymentarea,
            'instanceid' => $instanceid,
            'description' => get_string('purchasedescription', 'availability_gwpayments', $description),
            'successurl' => service_provider::get_success_url($paymentarea, $instanceid)->out(false),
        ];
        $data->localisedcost = $data->cost;

        if (!$canpaymentbemade && $disablepaymentonmisconfig) {
            $data->disablepaymentbutton = true;
        }
        $data->hasnotifications = false;
        if (!$canpaymentbemade) {
            $data->hasnotifications = true;
            if (is_siteadmin() || has_capability('moodle/course:update', $context)) {
                $data->notifications = $notifications;
            } else {
                $data->notifications = [get_string('err:payment:misconfiguration', 'availability_gwpayments')];
            }
        }

        // Using $OUTPUT can produce "The theme has already been set up for this page ready for output" error.
        // So only render the payment button when its really needed (ie, within the course).
        // For notifications, just return the text string.
        $paymentregion = '';
        if ($PAGE->state !== $PAGE::STATE_BEFORE_HEADER) {
            $paymentregion = $OUTPUT->render_from_template('availability_gwpayments/payment_region', $data);
        }

        if ($not) {
            return get_string('notdescription', 'availability_gwpayments', $paymentregion);
        } else {
            return get_string('eitherdescription', 'availability_gwpayments', $paymentregion);
        }
    }

    /**
     * Obtains a representation of the options of this condition as a string,
     * for debugging.
     *
     * @return string Text representation of parameters
     */
    protected function get_debug_string() {
        return gmdate('Y-m-d H:i:s');
    }

    /**
     * Determine whether a valid payment can be made.
     *
     * @param string $paymentarea
     * @param int $itemid
     * @param array $reasons
     * @return boolean
     */
    private function can_payment_be_made(string $paymentarea, int $itemid, array &$reasons) {
        // If no account set...
        if (empty($this->accountid)) {
            $reasons[] = get_string('err:no-payment-account-set', 'availability_gwpayments');
            return false;
        }
        try {
            // Account validation.
            $account = new \core_payment\account($this->accountid);
            if (!$account->is_available()) {
                $reasons[] = get_string('err:payment-account-unavailable', 'availability_gwpayments');
                return false;
            }
            // Gateway currency validation.
            $gateways = \core_payment\helper::get_available_gateways('availability_gwpayments', $paymentarea, $itemid);
            if (count($gateways) == 0) {
                $reasons[] = get_string('err:payment-no-available-gateways', 'availability_gwpayments');
                return false;
            }
        } catch (\dml_missing_record_exception $e) {
            $reasons[] = get_string('err:payment-account-not-exists', 'availability_gwpayments');
            return false;
        }
        return true;
    }

}
