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
 * Plugin administration pages are defined here.
 *
 * @package     availability_gwpayments
 * @category    admin
 * @copyright   2017 R.J. van Dongen <rogier@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $config = get_config('availability_gwpayments');
    // Logo.
    $image = '<a href="http://www.sebsoft.nl" target="_new"><img src="' .
            $OUTPUT->image_url('logo', 'availability_gwpayments') . '" /></a>&nbsp;&nbsp;&nbsp;';
    $donate = '<a href="https://customerpanel.sebsoft.nl/sebsoft/donate/intro.php" target="_new"><img src="' .
            $OUTPUT->image_url('donate', 'availability_gwpayments') . '" /></a>';
    $header = '<div class="availability_gwpayments-logopromo">' . $image . $donate . '</div>';
    $settings->add(new admin_setting_heading('availability_gwpayments_logopromo',
            get_string('promo', 'availability_gwpayments'),
            get_string('promodesc', 'availability_gwpayments', $header)));

    $settings->add(new admin_setting_configtext('availability_gwpayments/vat',
            get_string('vat', 'availability_gwpayments'),
            get_string('vat_help', 'availability_gwpayments'),
            21, PARAM_INT, 4));
}
