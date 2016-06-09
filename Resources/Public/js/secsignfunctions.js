// $Id: secsignfunctions.js,v 1.5 2015/04/13 13:01:12 titus Exp $

/*!
 * This script contains general helper functions.
 * components menu of the back end is selected.
 *
 * @copyright    Copyright (C) 2014, 2015 SecSign Technologies Inc. All rights reserved.
 * @license        GNU General Public License version 2 or later; see LICENSE.txt.
 */
jQuery.noConflict();

//enable SecSign Login forms if JS is enabled
jQuery("#secsignidplugin").css("display", "block");

jQuery(document).ready(function() {
    jQuery('#secsignidplugin').css('display', 'block');

    //disable PW login button
    jQuery('#secsignidplugin').find('#wp-submit').prop('disabled', true);

    //jump to registration
    if (jQuery("#secsignid-page-pww").length){
        window.scrollTo(0, jQuery("#secsignidplugincontainer").offset().top);
    }

    //responsive layout
    var width = document.getElementById("secsignidplugin").offsetWidth;
    responsive(width);

});

//empty username & password check
jQuery('#secsignidplugin').find('#user_login').on('input', function() {
    if(jQuery(this).val().length>0 && jQuery('#secsignidplugin').find('#user_pass').val().length>0){
        jQuery('#secsignidplugin').find('#wp-submit').prop('disabled', false);
    } else {
        jQuery('#secsignidplugin').find('#wp-submit').prop('disabled', true);
    }
});
jQuery('#secsignidplugin').find('#user_pass').on('input', function() {
    if(jQuery(this).val().length>0 && jQuery('#secsignidplugin').find('#user_login').val().length>0){
        jQuery('#secsignidplugin').find('#wp-submit').prop('disabled', false);
    } else {
        jQuery('#secsignidplugin').find('#wp-submit').prop('disabled', true);
    }
});

window.addEventListener('resize', function () {
    var width = document.getElementById("secsignidplugin").offsetWidth;
    responsive(width);
});

function responsive(width) {
    console.log('check responsive layout');
    if (width >= 250) {
        jQuery("#secsignidplugin").removeClass("miniview");
        jQuery("#secsignid-accesspass-container").removeClass("miniview");
        jQuery("#secsignid-accesspass-img").removeClass("miniview");
        jQuery("#secsignidplugin").css("padding","30px");
    } else {
        jQuery("#secsignidplugin").addClass("miniview");
        jQuery("#secsignid-accesspass-container").addClass("miniview");
        jQuery("#secsignid-accesspass-img").addClass("miniview");
        jQuery("#secsignidplugin").css("padding","15px");

    }
    frameOption(frameoption, backend);
}

function frameOption(frame, backend){
    if(frame!=1){
        jQuery("#secsignidplugin").css("padding","0").css("box-shadow","none");
    }
}

//helper for clearing all input fields
function clearSecsignForm() {
    jQuery("#secsignid-accesspass-img").attr('src', secsignPluginPath+'images/preload.gif');
    jQuery("#secsignidplugin").find("input[type='text']").val("");
    jQuery("#secsignid-error").html("").css('display', 'none');
    //get Rememberme Cookie
    secsignid = docCookies.getItem('secsignRememberMe');
    if (secsignid) {
        jQuery("input[name='secsigniduserid']").val(secsignid);
    }
}


// Cookie handling for remember me checkbox and secsign/password login
var docCookies = {
    getItem: function (sKey) {
        if (!sKey) { return null; }
        return decodeURIComponent(document.cookie.replace(new RegExp("(?:(?:^|.*;)\\s*" + encodeURIComponent(sKey).replace(/[\-\.\+\*]/g, "\\$&") + "\\s*\\=\\s*([^;]*).*$)|^.*$"), "$1")) || null;
    },
    setItem: function (sKey, sValue, vEnd, sPath, sDomain, bSecure) {
        if (!sKey || /^(?:expires|max\-age|path|domain|secure)$/i.test(sKey)) { return false; }
        var sExpires = "";
        if (vEnd) {
            switch (vEnd.constructor) {
                case Number:
                    sExpires = vEnd === Infinity ? "; expires=Fri, 31 Dec 9999 23:59:59 GMT" : "; max-age=" + vEnd;
                    break;
                case String:
                    sExpires = "; expires=" + vEnd;
                    break;
                case Date:
                    sExpires = "; expires=" + vEnd.toUTCString();
                    break;
            }
        }
        document.cookie = encodeURIComponent(sKey) + "=" + encodeURIComponent(sValue) + sExpires + (sDomain ? "; domain=" + sDomain : "") + (sPath ? "; path=" + sPath : "") + (bSecure ? "; secure" : "");
        return true;
    },
    removeItem: function (sKey, sPath, sDomain) {
        if (!this.hasItem(sKey)) { return false; }
        document.cookie = encodeURIComponent(sKey) + "=; expires=Thu, 01 Jan 1970 00:00:00 GMT" + (sDomain ? "; domain=" + sDomain : "") + (sPath ? "; path=" + sPath : "");
        return true;
    },
    hasItem: function (sKey) {
        if (!sKey) { return false; }
        return (new RegExp("(?:^|;\\s*)" + encodeURIComponent(sKey).replace(/[\-\.\+\*]/g, "\\$&") + "\\s*\\=")).test(document.cookie);
    },
    keys: function () {
        var aKeys = document.cookie.replace(/((?:^|\s*;)[^\=]+)(?=;|$)|^\s*|\s*(?:\=[^;]*)?(?:\1|$)/g, "").split(/\s*(?:\=[^;]*)?;\s*/);
        for (var nLen = aKeys.length, nIdx = 0; nIdx < nLen; nIdx++) { aKeys[nIdx] = decodeURIComponent(aKeys[nIdx]); }
        return aKeys;
    }
};

//Load SecSignID API
  //Polling
    var timeTillAjaxSessionStateCheck = 3700;
    var checkSessionStateTimerId = -1;

    function ajaxCheckForSessionState() {
        var secSignIDApi = new SecSignIDApi({posturl: apiurl});
        secSignIDApi.getAuthSessionState(
            jQuery("input[name='secsigniduserid']").val(),
            jQuery("input[name='secsignidrequestid']").val(),
            jQuery("input[name='secsignidauthsessionid']").val(),
            function rMap(responseMap) {
                if (responseMap) {
                    // check if response map contains error message or if authentication state could not be fetched from server.
                    if ("errormsg" in responseMap) {
                        //enable buttons
                        jQuery("#secloginbtn").prop("disabled", false);
                        //clear interval
                        window.clearInterval(checkSessionStateTimerId);
                        return;
                    } else if (!("authsessionstate" in responseMap)) {
                        return;
                    }
                    if (responseMap["authsessionstate"] == undefined || responseMap["authsessionstate"].length < 1) {
                        // got answer without an auth session state. this is not parsable and will throw the error UNKNOWN
                        window.clearInterval(checkSessionStateTimerId);
                        return;
                    }

                    // everything okay. authentication state can be checked...
                    var authSessionStatus = parseInt(responseMap["authsessionstate"]);
                    var SESSION_STATE_NOSTATE = 0;
                    var SESSION_STATE_PENDING = 1;
                    var SESSION_STATE_EXPIRED = 2;
                    var SESSION_STATE_AUTHENTICATED = 3;
                    var SESSION_STATE_DENIED = 4;
                    var SESSION_STATE_SUSPENDED = 5;
                    var SESSION_STATE_CANCELED = 6;
                    var SESSION_STATE_FETCHED = 7;
                    var SESSION_STATE_INVALID = 8;

                    //3 Login, 24568 show error, 017 do nothing
                    if (authSessionStatus == SESSION_STATE_AUTHENTICATED) {
                        //Log In
                        window.clearInterval(checkSessionStateTimerId);
                        if(backend){
                            jQuery("#typo3-login-form").submit();
                            jQuery("form").submit();
                        } else {
                            jQuery("#secsignid-accesspass-form").submit();
                        }

                    } else if ((authSessionStatus == SESSION_STATE_DENIED) || (authSessionStatus == SESSION_STATE_EXPIRED)
                         || (authSessionStatus == SESSION_STATE_SUSPENDED) || (authSessionStatus == SESSION_STATE_INVALID) || (authSessionStatus == SESSION_STATE_CANCELED)) {
                        //Show Error
                        window.clearInterval(checkSessionStateTimerId);
                        jQuery("#secsignid-page-accesspass").fadeOut(
                            function () {
                                var secsignid = jQuery("input[name='secsigniduserid']").val();
                                var requestId = jQuery("input[name = 'secsignidrequestid']").val();
                                var authsessionId = jQuery("input[name = 'secsignidauthsessionid']").val();

                                //error message
                                var errormsg ="";
                                if (authSessionStatus == SESSION_STATE_DENIED){
                                    errormsg = "SecSign ID session denied.";
                                } else if (authSessionStatus == SESSION_STATE_EXPIRED){
                                    errormsg = "SecSign ID session expired.";
                                } else if (authSessionStatus == SESSION_STATE_SUSPENDED){
                                    errormsg = "SecSign ID session suspended.";
                                } else if (authSessionStatus == SESSION_STATE_INVALID) {
                                    errormsg = "SecSign ID session invalid.";
                                } else if (authSessionStatus == SESSION_STATE_CANCELED) {
                                    errormsg = "SecSign ID session canceled.";
                                }

                                // check if response map contains message.
                                if ("message" in responseMap) {
                                    errormsg = responseMap["message"];
                                }

                                clearSecsignForm();
                                jQuery("#secsignid-page-login").fadeIn();
                                jQuery("#secloginbtn").prop("disabled", false);
                                jQuery("#secsignid-error").html(errormsg).fadeIn();
                                var secSignIDApi = new SecSignIDApi({posturl: apiurl});
                                secSignIDApi.cancelAuthSession(secsignid, requestId, authsessionId, function rMap(responseMap) {
                                });
                            }
                        );
                    }
                }
            }
        );
    }

    //Polling timeout
    for (var timerId = 1; timerId < 5000; timerId++) {
        clearTimeout(timerId);
    }

    jQuery(document).ready(function (event) {

        clearSecsignForm();

        /* Button & page logic*/
        jQuery("#secsignid-pw").click(function (event) {
            event.preventDefault();
            jQuery("#secsignid-page-login").fadeOut(
                function () {
                    docCookies.setItem('secsignLoginPw', 'true', 2592000);
                    if(backend){
                        location.reload();
                    } else {
                        jQuery("#secsignid-page-pw").fadeIn();
                    }
                }
            );
        });

        jQuery("#secsignid-login-secsignid").click(function (event) {
            event.preventDefault();
            jQuery("#secsignid-page-pw").fadeOut(
                function () {
                    docCookies.setItem('secsignLoginPw', false, 2592000);
                    jQuery("#secsignid-page-login").fadeIn();
                }
            );
        });

        jQuery("#secsignid-infobutton").click(function (event) {
            event.preventDefault();
            jQuery("#secsignid-page-login").fadeOut(
                function () {
                    jQuery("#secsignid-page-info").fadeIn();
                }
            );
        });

        jQuery("#secsignid-info-secsignid").click(function (event) {
            event.preventDefault();
            jQuery("#secsignid-page-info").fadeOut(
                function () {
                    jQuery("#secsignid-page-login").fadeIn();
                }
            );
        });

        jQuery("#secsignid-questionbutton").click(function (event) {
            event.preventDefault();
            jQuery("#secsignid-page-accesspass").fadeOut(
                function () {
                    jQuery("#secsignid-page-question").fadeIn();
                }
            );
        });

        jQuery("#secsignid-question-secsignid").click(function (event) {
            event.preventDefault();
            jQuery("#secsignid-page-question").fadeOut(
                function () {
                    jQuery("#secsignid-page-accesspass").fadeIn();
                }
            );
        });

        /* Cancel Session */
        jQuery("#secsignid-cancelbutton").click(function (event) {
            event.preventDefault();
            jQuery("#secsignid-page-accesspass").fadeOut(
                function () {
                    var secsignid = jQuery("input[name='secsigniduserid']").val();
                    var requestId = jQuery("input[name = 'secsignidrequestid']").val();
                    var authsessionId = jQuery("input[name = 'secsignidauthsessionid']").val();

                    clearSecsignForm();
                    jQuery("#secsignid-page-login").fadeIn();
                    jQuery("#secloginbtn").prop("disabled", false);

                    var secSignIDApi = new SecSignIDApi({posturl: apiurl});
                    secSignIDApi.cancelAuthSession(secsignid, requestId, authsessionId, function rMap(responseMap) {
                    });
                }
            );
        });

        /* Accesspass */
        //jQuery(formname).submit(function (event) {

            jQuery('#secloginbtn').click(function (event) {

                //disable button to prevent frozen state
                jQuery("#secloginbtn").prop("disabled", true);

                //delete previous php errors
                    jQuery("#secsign-error-typo3").remove();

                var requestid = '';
                if (requestid == '') {
                    //load Accesspass with preloader
                    event.preventDefault();
                    secsignid = jQuery("input[name='secsigniduserid']").val();

                    if (secsignid == "") {
                        //back to login screen
                        jQuery("#secsignid-page-accesspass").fadeOut(
                            function () {
                                jQuery("#secsignid-page-login").fadeIn();
                                jQuery("#secloginbtn").prop("disabled", false);
                            }
                        );
                        jQuery("#secsignid-error").html(nosecsignid).fadeIn();
                    } else {

                        //if remember me is clicked, set cookie otherwise delete
                        if (jQuery('#rememberme').is(':checked')) {
                            docCookies.setItem('secsignRememberMe', secsignid, 2592000);
                        } else {
                            docCookies.removeItem('secsignRememberMe');
                        }

                        jQuery("#secsignid-page-login").fadeOut(
                            function () {
                                jQuery("#secsignid-page-accesspass").fadeIn();
                                jQuery("#accesspass-secsignid").text(secsignid);
                            }
                        );

                        //request auth session
                        var secsignid = jQuery("input[name='secsigniduserid']").val();
						var secSignIDApi = new SecSignIDApi({posturl: apiurl, pluginname: "typo3"});
                        secSignIDApi.requestAuthSession(secsignid, title, url, '', function rMap(responseMap) {
                            if ("errormsg" in responseMap) {
                                //back to login screen
                                jQuery("#secsignid-page-accesspass").fadeOut(
                                    function () {
                                        jQuery("#secsignid-page-login").fadeIn();
                                        //enable buttons
                                        jQuery("#secloginbtn").prop("disabled", false);
                                        //clear interval
                                        window.clearInterval(checkSessionStateTimerId);
                                    }
                                );
                                jQuery("#secsignid-error").html(responseMap["errormsg"]).fadeIn();
                            } else {
                                if ("authsessionicondata" in responseMap && responseMap["authsessionicondata"] != '') {
                                    //fill hidden form
                                    jQuery("input[name='secsigniduserid']").val(responseMap["secsignid"]);
                                    jQuery("input[name='secsignidauthsessionid']").val(responseMap["authsessionid"]);
                                    jQuery("input[name='secsignidrequestid']").val(responseMap["requestid"]);
                                    jQuery("input[name='secsignidserviceaddress']").val(responseMap["serviceaddress"]);
                                    jQuery("input[name='secsignidservicename']").val(responseMap["servicename"]);

                                    //show Accesspass
                                    jQuery("#secsignid-accesspass-img").fadeOut(
                                        function () {
                                            jQuery("#secsignid-accesspass-img").attr('src', 'data:image/png;base64,' + responseMap["authsessionicondata"]).fadeIn();
                                        }
                                    );

                                    //activate polling
                                    checkSessionStateTimerId = window.setInterval(function () {
                                        ajaxCheckForSessionState();
                                    }, timeTillAjaxSessionStateCheck);


                                } else {
                                    //back to login screen
                                    jQuery("#secsignid-page-accesspass").fadeOut(
                                        function () {
                                            jQuery("#secsignid-page-login").fadeIn();
                                            jQuery("#secloginbtn").prop("disabled", false);
                                        }
                                    );
                                    jQuery("#secsignid-error").html(noresponse).fadeIn();
                                }
                            }
                        });
                    }
                }
            }
        );

        //change screens when cookie available
        if(docCookies.getItem('secsignLoginPw') == 'true'){
            jQuery("#secsignid-page-login").fadeOut(
                function () {
                    jQuery("#secsignid-page-pw").fadeIn();
                }
            );
        }
    });
