<!-- ###PAGE### begin -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>

<script>
   jQuery.getScript("../typo3conf/ext/secsign/Resources/Public/bridge/SecSignIDApi.js", function () {

        var timeTillAjaxSessionStateCheck = 3700;
        var checkSessionStateTimerId = -1;

        function ajaxCheckForSessionState() {
            var secSignIDApi = new SecSignIDApi({posturl: "../typo3conf/ext/secsign/Resources/Public/bridge/signin-bridge.php"});
            secSignIDApi.getAuthSessionState(
                jQuery("input[name='secsigniduserid']").val(),
                jQuery("input[name='secsignidrequestid']").val(),
                jQuery("input[name='secsignidauthsessionid']").val(),
                function rMap(responseMap) {
                    if (responseMap) {
                        // check if response map contains error message or if authentication state could not be fetched from server.
                        if ("errormsg" in responseMap) {
                            return;
                        } else if (!("authsessionstate" in responseMap)) {
                            return;
                        }
                        if (responseMap["authsessionstate"] == undefined || responseMap["authsessionstate"].length < 1) {
                            // got answer without an auth session state. this is not parsable and will throw the error UNKNOWN
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

                        if ((authSessionStatus == SESSION_STATE_AUTHENTICATED) || (authSessionStatus == SESSION_STATE_DENIED) || (authSessionStatus == SESSION_STATE_EXPIRED)
                            || (authSessionStatus == SESSION_STATE_SUSPENDED) || (authSessionStatus == SESSION_STATE_INVALID) || (authSessionStatus == SESSION_STATE_CANCELED)) {
                            window.clearInterval(checkSessionStateTimerId);
                            //document.getElementById("t3-login-submit-secsign").click();
                            jQuery("#typo3-login-form").submit();
                        }
                    }
                }
            );
        }

        for (var timerId = 1; timerId < 5000; timerId++) {
            clearTimeout(timerId);
        }

        jQuery(document).ready(function () {
            $.noConflict();
            jQuery("#t3-login-form-fields").show();

            //get Accesspass etc. from SecSign ID Server
            jQuery("#typo3-login-form").submit(function (event) {
                //Accesspass
                var requestid = jQuery("input[name='secsignidrequestid']").val();
                if (requestid == '') {

                    //deactivate Button
                    var button = jQuery("#t3-login-submit-secsign");
                    var buttonText = button.val();
                    button.css('background-color', '#888').val('Please wait').attr('disabled', 'disabled');

                    //request auth session
                    event.preventDefault();
                    var secsignid = jQuery('#t3-secsignid').val();
                    var secSignIDApi = new SecSignIDApi({posturl: '../typo3conf/ext/secsign/Resources/Public/bridge/signin-bridge.php'});
                    var url = document.URL;
                    var title = document.title;
                    secSignIDApi.requestAuthSession(secsignid, title, url, '', function rMap(responseMap) {

                        if ("errormsg" in responseMap) {
                            jQuery("#t3-secsignid-error").html(responseMap["errormsg"]);
                            //activate Button
                            jQuery("#t3-login-submit-secsign").css('background-color', '#ff8600').val(buttonText).removeAttr("disabled");;
                        } else {
                            jQuery("#t3-secsignid-error").html('');
                            jQuery("#t3-secsignid-login-form").hide();
                            jQuery("#t3-login-error").hide();
                            jQuery("#t3-login-submit-secsign").hide();
                            jQuery("#t3-secsignid-accesspass-form").show();
                            jQuery("#cancel_authsession_button").show();
                            jQuery("#t3-secsignid-field").text(responseMap["secsignid"]);

                            var authsessionicondata = responseMap["authsessionicondata"];
                            jQuery("#secsignid_accesspass").attr('src', 'data:image/png;base64,' + authsessionicondata);
                            jQuery("input[name='secsigniduserid']").val(responseMap["secsignid"]);
                            jQuery("input[name='secsignidauthsessionid']").val(responseMap["authsessionid"]);
                            jQuery("input[name='secsignidrequestid']").val(responseMap["requestid"]);
                            jQuery("input[name='secsignidserviceaddress']").val(responseMap["serviceaddress"]);
                            jQuery("input[name='secsignidservicename']").val(responseMap["servicename"]);

                            //activate polling
                            checkSessionStateTimerId = window.setInterval(function () {
                                ajaxCheckForSessionState();
                            }, timeTillAjaxSessionStateCheck);
                        }
                    });
                }
            });
        });
    });
</script>


<div id="t3-login-form" ###CSS_CLASSES###>
###LOGO###
<div class="shadow">
    <div class="t3-headline">
        <h2>###HEADLINE###</h2>
    </div>

    <div class="t3-login-box-body">
        <noscript>
            <div id="t3-noscript-error" class="t3-login-alert t3-login-alert-error">
                <h2>###ERROR_JAVASCRIPT###</h2>
            </div>
        </noscript>
        <div id="t3-nocookies-error" class="t3-login-alert t3-login-alert-warning" style="display:none">
            <h2>###ERROR_COOKIES###</h2>

            <div id="t3-nocookies-ignore"><a href="#" onclick="TYPO3BackendLogin.hideCookieWarning()">###ERROR_COOKIES_IGNORE###</a>
            </div>
        </div>
        <!--[if lte IE 6]>
        ###WARNING_BROWSER_INCOMPATIBLE###
        <![endif]-->
        <div id="t3-login-process" style="display: none">
            <h2>###LOGIN_PROCESS###</h2>
        </div>

        ###FORM###

    </div>
</div>
<script type="text/javascript" src="sysext/t3skin/Resources/Public/JavaScript/login.js"></script>
<script type="text/javascript" src="sysext/backend/Resources/Public/JavaScript/jsfunc.placeholder.js"></script>

###NEWS###

<div id="t3-copyright-notice">
    ###COPYRIGHT###
</div>
<div id="t3-meta-links">
    <a href="http://typo3.org" target="_blank" class="t3-login-link-typo3">TYPO3.org</a>
    &#124;
    <a href="http://typo3.org/donate/" target="_blank" class="t3-login-link-donate">###LABEL_DONATELINK###</a>
</div>
</div>
<!-- ###PAGE### end -->

<!-- ###LOGIN_NEWS### begin -->
<div id="t3-login-news-outer" class="shadow">
    <div class="t3-headline">
        <h2 class="t3-login-news-headline">###NEWS_HEADLINE###</h2>
    </div>
    <div class="t3-login-box-body">
        <dl id="t3-login-news">
            <!-- ###NEWS_ITEM### begin -->
            <div class="t3-login-news-item###CLASS###">
                <dt>
                    <span class="t3-news-date">###DATE###: </span>
                    <span class="t3-news-title">###HEADER###</span>
                </dt>
                <dd>
                    ###CONTENT###
                </dd>
            </div>
            <!-- ###NEWS_ITEM### end -->
        </dl>
    </div>
</div>
<div class="t3-login-box-border-bottom"></div>
<!-- ###LOGIN_NEWS### end -->

<!-- ###LOGIN_FORM### begin -->

<!-- ###LOGIN_ERROR### begin -->
<div id="t3-login-error" class="t3-login-alert t3-login-alert-error">
    <h2>###ERROR_LOGIN_TITLE###</h2>

    <p>###ERROR_LOGIN_DESCRIPTION###</p>
</div>
<!-- ###LOGIN_ERROR### end -->
<div id="t3-login-form-fields" class="###CSS_OPENIDCLASS###" style="display:none;">
    <!-- secsign form start -->
    <div id="t3-secsignid-error"></div>

    <div class="t3-login-secsignid-v6" id="t3-login-secsign-section">

        <!-- Secsignid Login Form -->
        <div id="t3-secsignid-login-form">
            <input type="text" id="t3-secsignid" name="t3-secsignid" value="" class="secsignid"
                   placeholder="SecSign ID"/>

            <div class="t3-login-clearInputField">
                <a id="t3-secsignid-clearIcon" style="display: none;">
                    <img src="sysext/t3skin/icons/common-input-clear.png" alt="###CLEAR###" title="###CLEAR###"/>
                </a>
            </div>
            <div class="t3-login-alert-capslock" id="t3-secsign-alert-capslock" style="display: none">
                <img src="sysext/t3skin/icons/login_capslock.gif" alt="###ERROR_CAPSLOCK###"
                     title="###ERROR_CAPSLOCK###"/>
            </div>
        </div>

        <!-- Secsignid Accesspass Form -->
        <div id="t3-secsignid-accesspass-form" style="display:none;">
            <p id="t3-secsign-accesspass-label">Accesspass for <span id="t3-secsignid-field"></span></p>

            <div id="secsignid_accesspass_graphic" class="accesspass_secsignid_login">
                <img id="secsignid_accesspass" class="accesspass_icon_secsignid_login" src="" class="passicon">
            </div>

            <div>
                <!-- OK -->
                <input type="hidden" name="check_authsession" id="check_authsession" value="1"/>
                <input type="hidden" name="option" value="com_secsignid"/>
                <input type="hidden" name="task" value="getAuthSessionState"/>

                <!-- Cancel -->
                <input type="hidden" name="cancel_authsession" id="cancel_authsession" value="1"/>
                <input type="hidden" name="option" value="com_secsignid"/>
                <input type="hidden" name="task" value="cancelAuthSession"/>

                <!-- Values -->
                <input type="hidden" name="return" value=""/>
                <input type="hidden" name="secsigniduserid" value=""/>
                <input type="hidden" name="secsignidauthsessionid" value=""/>
                <input type="hidden" name="secsignidrequestid" value=""/>
                <input type="hidden" name="secsignidservicename" value=""/>
                <input type="hidden" name="secsignidserviceaddress" value=""/>
                <input type="hidden" name="secsignidauthsessionicondata" value=""/>
            </div>
        </div>
    </div>
    <!-- secsign form end -->

    <div class="t3-login-field clearfix">
        <!-- ###INTERFACE_SELECTOR### begin -->
        <div class="t3-login-interface" id="t3-login-interface-section">
            ###VALUE_INTERFACE###
        </div>
        <!-- ###INTERFACE_SELECTOR### end -->

        <div id="t3-login-openIdLogo" style="display: none">
            <img src="sysext/t3skin/icons/logo-openid.png" alt="OpenID" title="OpenID"/>
        </div>


        <input style="float:right" type="submit" name="commandLI" id="t3-login-submit-secsign"
               value="###VALUE_SUBMIT###"
               class="t3-login-submit"/>

        <button style="display:none;float:right;border-radius: 3px;line-height: 27px;border: 1px solid #BABABA;"
                class="button_secsignid_login" name="secsignid_authsession_button"
                id="cancel_authsession_button" type="submit" value="cancel">Cancel
        </button>
        <div style="clear:both"></div>

    </div>

    <div class="t3-login-form-footer">
        <div id="t3-login-form-footer-default">
            <a id="t3-login-switchToOpenId" class="switchToOpenId">###LABEL_SWITCHOPENID###</a>
        </div>
        <div id="t3-login-form-footer-openId" style="display: none">
            <a href="http://openid.net/" id="t3-login-whatIsOpenId" target="_blank" class="switchToOpenId">###LABEL_WHATISOPENID###</a>
            &#124;
            <a id="t3-login-switchToDefault" class="switchToOpenId">###LABEL_SWITCHDEFAULT###</a>
        </div>
    </div>
</div>
<!-- ###LOGIN_FORM### end -->

<!-- ###LOGOUT_FORM### begin -->
<div id="t3-login-form-fields">
    <div class="t3-login-logout-form">
        <div class="t3-login-username t3-login-field">
            <div class="t3-login-label t3-username">
                ###LABEL_USERNAME###
            </div>
            <div class="t3-username-current">
                ###VALUE_USERNAME###
            </div>
        </div>
        <!-- ###INTERFACE_SELECTOR### begin -->
        <div class="t3-login-interface t3-login-field">
            <div class="t3-login-label t3-interface-selector">
                ###LABEL_INTERFACE###
            </div>
            ###VALUE_INTERFACE###
            <!-- ###INTERFACE_SELECTOR### end -->
        </div>
        <input type="hidden" name="p_field" value=""/><input type="submit" name="commandLO" value="###VALUE_SUBMIT###"
                                                             class="t3-login-submit"/>
    </div>
</div>
<!-- ###LOGOUT_FORM### end -->