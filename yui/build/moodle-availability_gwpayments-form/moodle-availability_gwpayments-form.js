YUI.add('moodle-availability_gwpayments-form', function (Y, NAME) {

/**
 * JavaScript for form editing date conditions.
 *
 * @module moodle-availability_gwpayments-form
 */
M.availability_gwpayments = M.availability_gwpayments || {};

/**
 * @class M.availability_gwpayments.form
 * @extends M.core_availability.plugin
 */
M.availability_gwpayments.form = Y.Object(M.core_availability.plugin);

/**
 * Initialises this plugin.
 *
 * @method initInner
 * @param {Array} currencies Array of currency_code => localised string
 * @param {Array} accounts Array of id => localised string
 * @param {Array} defaults array of key => value
 */
M.availability_gwpayments.form.initInner = function(currencies, accounts, defaults) {
    this.currencies = currencies;
    this.accounts = accounts;
    this.defaults = defaults;
};

/**
 * get/create the condition node(s).
 *
 * @param {Object} json
 * @return {Object} node
 */
M.availability_gwpayments.form.getNode = function(json) {
    var selectedString = '';
    var currenciesOptions = '';
    for (var curr in this.currencies) {
        if (json.currency === curr) {
            selectedString = ' selected="selected"';
        } else if (json.currency === undefined && this.defaults.currency !== undefined
                && this.defaults.currency === curr) {
            selectedString = ' selected="selected"';
        } else {
            selectedString = '';
        }
        currenciesOptions += '<option value="' + curr + '" ' + selectedString + ' >';
        currenciesOptions += this.currencies[curr];
        currenciesOptions += '</option>';
    }

    var accountOptions = '';
    for (var accid in this.accounts) {
        if (json.accountid === accid) {
            selectedString = ' selected="selected" ';
        } else {
            selectedString = '';
        }
        accountOptions += '<option value="' + accid + '" ' + selectedString + ' >';
        accountOptions += this.accounts[accid];
        accountOptions += '</option>';
    }

    var html = '';

    html += '<div><label>';
    html += M.util.get_string('paymentaccount', 'availability_gwpayments');
    html += '<select name="accountid" />' + accountOptions + '</select>';
    html += '</label></div>';

    html += '<div><label>';
    html += M.util.get_string('currency', 'availability_gwpayments');
    html += '<select name="currency" />' + currenciesOptions + '</select>';
    html += '</label></div>';

    html += '<div><label>';
    html += M.util.get_string('cost', 'availability_gwpayments');
    html += '<input name="cost" type="text" />';
    html += '</label></div>';

    html += '<div><label>';
    html += M.util.get_string('vat', 'availability_gwpayments');
    html += '<input name="vat" type="text" />';
    html += '</label></div>';

    var node = Y.Node.create('<span>' + html + '</span>');

    // Set initial values based on the value from the JSON data in Moodle
    // database. This will have values undefined if creating a new one.
    if (json.cost !== undefined) {
        node.one('input[name=cost]').set('value', json.cost);
    } else if (this.defaults.cost !== undefined) {
        node.one('input[name=cost]').set('value', this.defaults.cost);
    }
    if (json.vat !== undefined) {
        node.one('input[name=vat]').set('value', json.vat);
    } else if (this.defaults.vat !== undefined) {
        node.one('input[name=vat]').set('value', this.defaults.vat);
    }

    // Add event handlers (first time only). You can do this any way you
    // like, but this pattern is used by the existing code.
    if (!M.availability_gwpayments.form.addedEvents) {
        M.availability_gwpayments.form.addedEvents = true;
        var root = Y.one('#fitem_id_availabilityconditionsjson');
        root.delegate('change', function() {
            M.core_availability.form.update();
        }, '.availability_gwpayments select[name=accountid]');
        root.delegate('change', function() {
            M.core_availability.form.update();
        }, '.availability_gwpayments select[name=currency]');
        root.delegate('valuechange', function() {
                // The key point is this update call. This call will update
                // the JSON data in the hidden field in the form, so that it
                // includes the new value of the checkbox.
                M.core_availability.form.update();
        }, '.availability_gwpayments input');
    }

    return node;
};

/**
 * Fill or fetch a value
 *
 * @param {Object} value
 * @param {Object} node
 */
M.availability_gwpayments.form.fillValue = function(value, node) {
    // This function gets passed the node (from above) and a value
    // object. Within that object, it must set up the correct values
    // to use within the JSON data in the form. Should be compatible
    // with the structure used in the __construct and save functions
    // within condition.php.
    value.accountid = node.one('select[name=accountid]').get('value');

    value.currency = node.one('select[name=currency]').get('value');

    value.cost = this.getValue('cost', node);

    value.vat = this.getValue('vat', node);
};

/**
 * Gets the numeric value of an input field. Supports decimal points (using
 * dot or comma).
 *
 * @method getValue
 * @return {String} field Field name
 * @return {Object} node Value of field as number or string if not valid
 */
M.availability_gwpayments.form.getValue = function(field, node) {
    // Get field value.
    var value = node.one('input[name=' + field + ']').get('value');

    // If it is not a valid positive number, return false.
    if (!(/^[0-9]+([.,][0-9]+)?$/.test(value))) {
        return value;
    }

    // Replace comma with dot and parse as floating-point.
    var result = parseFloat(value.replace(',', '.'));
    return result;
};

/**
 * Fill errors
 *
 * @param {Array} errors
 * @param {Object} node
 */
M.availability_gwpayments.form.fillErrors = function(errors, node) {
    var value = {};
    this.fillValue(value, node);

    if ((value.cost !== undefined && (typeof value.cost === 'string')) || value.cost <= 0) {
        errors.push('availability_gwpayments:error_cost');
    }
    if ((value.vat !== undefined && (typeof value.vat  === 'string')) || value.vat < 0 || value.vat > 100) {
        errors.push('availability_gwpayments:error_vat');
    }
};


}, '@VERSION@', {"requires": ["base", "node", "event", "moodle-core_availability-form"]});
