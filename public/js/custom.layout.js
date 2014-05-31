/*global $*/
/*global document*/
/*global window*/
/*global json*/

$(document).ready(function () {
    'use strict';

    var hideDownloadsForAnon = function () {
        if(json.global.logged !== "1") {
            $('.downloadObject').hide();
        }
    };

    // Go to the landing page.
    $('div.HeaderLogo').unbind('click').click(function () {
        window.location = json.global.webroot;
    });

    // Remove download links if the user is not logged into the system.
    hideDownloadsForAnon();
    midas.registerCallback('CALLBACK_CORE_RESOURCE_HIGHLIGHTED',
                           'rsnabranding', hideDownloadsForAnon);

});
