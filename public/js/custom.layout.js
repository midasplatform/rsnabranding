/*global $*/
/*global document*/
/*global window*/
/*global json*/

$(document).ready(function () {
    'use strict';
    $('div.HeaderLogo').unbind('click').click(function () {
        window.location = json.global.webroot + '/community';
    });

});
