// Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

/* global document */
/* global json */

var midas = midas || {};
midas.user = midas.user || {};
midas.user.login = midas.user.login || {};

midas.user.login.rsnavalidateLoginForm = function () {
    'use strict';
    $('input[name=previousuri]').val(json.global.currentUri);
    if ($('td#rsnaPassword #password').val() == '') {
        midas.createNotice('Password field must not be empty', 3500, 'error');
        return false;
    }
    $('#rsnaloginForm').find('input[type=submit]').attr('disabled', 'disabled');
    $('#rsnaloginWaiting').show();
};

midas.user.login.rsnaloginResult = function (responseText) {
    'use strict';
    $('#rsnaloginWaiting').hide();
    $('#rsnaloginForm').find('input[type=submit]').removeAttr('disabled');
    try {
        var resp = $.parseJSON(responseText);
        if (resp.status && resp.redirect) {
            window.location.href = resp.redirect;
        }
        else if (resp.dialog) {
            midas.loadDialog('loginOverride', resp.dialog);
            midas.showDialog(resp.title, false, resp.options);
        }
        else {
            midas.createNotice(resp.message, 5000, 'error');
        }
    }
    catch (e) {
        midas.createNotice('An internal error occured, please contact your administrator',
            5000, 'error');
    }
};

$(document).ready(function () {
    'use strict';
    $('form#rsnaLoginForm').ajaxForm({
        beforeSubmit: midas.user.login.rsnavalidateLoginForm,
        success: midas.user.login.rsnaloginResult
    });

    // Deal with password recovery
    if ($('a#rsnaforgotPasswordLink').length) {
        $('a#rsnaforgotPasswordLink').click(function () {
            midas.loadDialog("forgotpassword", "/user/recoverpassword");
            midas.showDialog("Recover Password");
        });
    }
});
