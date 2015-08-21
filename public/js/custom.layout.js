// Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

$(document).ready(function () {
    'use strict';

    var hideDownloadsForAnon = function () {
        if (json.global.logged !== "1") {
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

    $('.uploadFile-top')
        .empty()
        .unbind()
        .html("<div style=\"color: white; font-size: 14pt; padding-top: 2px;\">Request Upload</div>")
        .click(function (evt) {
            if (evt.originalEvent) {
                window.location = "https://www.rsna.org/QIDW-Contributor-Request/";
            }
        });
});
