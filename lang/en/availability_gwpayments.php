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
 * Plugin strings are defined here.
 *
 * File         availability_gwpayments.php
 * Encoding     UTF-8
 *
 * @package     availability_gwpayments
 *
 * @copyright   2021 RvD
 * @author      RvD <helpdesk@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['cost'] = 'Cost';
$string['currency'] = 'Currency';
$string['description'] = 'Require users to make a payment via a payment gateway to access the activity or resource.';
$string['disableifmoodleapp'] = 'Disable payments for Moodle App?';
$string['disableifmoodleapp_help'] = 'If set to disable, we\'ll display a message that payments can only be made from the browser';
$string['disablepaymentonmisconfig'] = 'Do not display payment button when no (valid) payment can be made.';
$string['disablepaymentonmisconfig_help'] = 'When enabled, the payment button will be hidden from view when a payment cannot be made, e.g. on currency mismappings or no gateways are available';
$string['eitherdescription'] = 'you make a payment with a payment gateway.<br/>{$a}';
$string['err:no-payment-account-set'] = 'No payment account is set for this activity.';
$string['err:payment-account-not-exists'] = 'The payment account for this activity no longer exists.';
$string['err:payment-account-unavailable'] = 'The payment account for this activity is unavailable.';
$string['err:payment-no-available-gateways'] = 'No gateway available for this activity. This is usually the case when the expected currency is not available for any gateway or one or more gateway(s) is/are disabled.';
$string['err:payment:misconfiguration'] = 'Due to a misconfiguration no payment can be made at this time.<br/>
If this message persists, please notify the system administrator.';
$string['error_cost'] = 'Cost field has an invalid value.';
$string['error_vat'] = 'VAT field has an invalid value (must be 0 or greater and below 100).';
$string['notdescription'] = 'you have not made a payment yet with a payment gateway.<br/>{$a}';
$string['paymentaccount'] = 'Payment account';
$string['paymentaccount_help'] = 'Enrolment fees will be paid to this account.';
$string['paymentrequired'] = 'This resource requires a payment';
$string['pluginname'] = 'PaymentS availability condition for paid access';
$string['privacy:metadata'] = 'The PaymentS payment for conditional access availability plugin does not store any personal data.';
$string['promo'] = 'PaymentS availability plugin for Moodle';
$string['promodesc'] = 'This plugin is written by Sebsoft Managed Hosting & Software Development
(<a href=\'https://www.sebsoft.nl/\' target=\'_new\'>https://sebsoft.nl</a>).<br /><br />
{$a}<br /><br />';
$string['purchasedescprepend'] = 'Purchase description prefix';
$string['purchasedescprepend_help'] = 'You can configure text to be prepended to the purchase description here.<br/>
By default, only the activity/resource name will be used. This fields fully supports multi language formatting.';
$string['purchasedescription'] = 'Access to {$a}';
$string['sendpaymentbutton'] = 'Select payment type';
$string['title'] = 'PaymentS payment';
$string['vat'] = 'VAT';
$string['vat_help'] = 'VAT percentage of cost (note: cost is including VAT so this is purely indicative).';
$string['warn:disabledifmoodleapp'] = 'Payments can only be made from within the browser, please log in to the LMS from there to perform payments.';
