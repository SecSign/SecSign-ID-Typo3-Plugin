<!-- ###PAGE### begin -->
<script type="text/javascript">
    //get cookie if pw or secsign form
    function getCookie(name) {
        function escape(s) {
            return s.replace(/([.*+?\^${}()|\[\]\/\\])/g, '\\$1');
        }
        var match = document.cookie.match(RegExp('(?:^|;\\s*)' + escape(name) + '=([^;]*)'));
        return match ? match[1] : null;
    }

    function deleteCookie(name) {
        document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:01 GMT;';
    }

    var showPW = getCookie('secsignLoginPw');

    if (showPW != 'true') {
        showPW = false;
        //Parameters
        var url = document.URL;
        var siteurl = document.URL;
        var title = document.title;
        var secsignPluginPath = "../typo3conf/ext/secsign/Resources/Public/";
        var apiurl = secsignPluginPath + "SecSignIDApi/signin-bridge.php";
        var errormsg = "Your login session has expired, was canceled, or was denied.";
        var noresponse = "The authentication server sent no response or you are not connected to the internet.";
        var nosecsignid = "Invalid SecSignID.";
        var secsignid = "";
        var frameoption = 1;
        var formname = "";
        var form_name_accesspass = formname;
        var backend = true;

        //get jQuery & all SecSign ID scripts
        (function () {
            var startingTime = new Date().getTime();
            // Load the script
            var script = document.createElement("SCRIPT");
            script.src = 'https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js';
            script.type = 'text/javascript';
            document.getElementsByTagName("head")[0].appendChild(script);

            // Poll for jQuery to come into existance
            var checkReady = function (callback) {
                if (window.jQuery) {
                    callback(jQuery);
                }
                else {
                    window.setTimeout(function () {
                        checkReady(callback);
                    }, 20);
                }
            };

            // Start polling for jQuery
            checkReady(function ($) {
                $(function () {
                    var endingTime = new Date().getTime();
                    var tookTime = endingTime - startingTime;
                    console.log('jQuery loaded after ' + tookTime + ' seconds');
                    jQuery.getScript(secsignPluginPath + "SecSignIDApi/SecSignIDApi.js", function () {
                        jQuery.getScript(secsignPluginPath + "js/secsignfunctions.js", function () {
                            console.log('secsign loaded successfully');
                            if (window.location.search.indexOf('err=1') > -1) {
                                jQuery("#secsignid-error").html("No Typo3 BE user found for the given SecSign ID.").show();
                                history.pushState(null, null, url.replace('?err=1', ''));
                            }
                            if (window.location.search.indexOf('err=2') > -1) {
                                jQuery("#secsignid-error").html("The Typo3 password login is disabled. Reload the page and use your SecSign ID to log in.").show();
                                history.pushState(null, null, url.replace('?err=2', ''));
                            }
                        });
                    });
                });
            });
        })();
    } else {
        // init typo3
        TYPO3BackendLogin = {
            start: function () {
            },
            preloadImages: function () {
            },
            registerEventListeners: function () {
            },
            observeEvents: function (element, events, handler) {
            },
            setVisibilityOfClearIcon: function (formField, clearIcon) {
            },
            showCapsLockWarning: function (alertIcon, event) {
            },
            clearInputField: function (formField) {
            },
            switchToOpenId: function () {
            },
            switchToDefault: function () {
            },
            checkCookieSupport: function () {
            },
            showCookieWarning: function () {
            },
            hideCookieWarning: function () {
            },
            setLogintypeCookie: function (type) {
            },
            checkForLogintypeCookie: function () {
            },
            interfaceSelectorChanged: function (event) {
            },
            checkForInterfaceCookie: function () {
            },
            showLoginProcess: function () {
            }
        };

        function gotoSecSignLogin(){
            deleteCookie('secsignLoginPw');
            location.reload();
        }

        function disableBtn() {
            //document.getElementById("t3-login-submit").disabled = true;
        }
    }
</script>

<div id="t3-login-form" ###CSS_CLASSES###>
    ###LOGO###

    <div id="pwform">
        <div id="secsignidplugincontainer">
            <div style="display:block;" id="secsignidplugin">
                <div style="display:block;" id="secsignid-page-pw">
                    <div class="secsignidlogo"></div>
                    ###FORM###
                </div>
            </div>
        </div>
    </div>

    <div id="secsignidform">
        <div id="secsignidplugincontainer">
            <noscript>
                <div class="secsignidlogo"></div>
                <p>It appears that your browser has JavaScript disabled. The SecSign ID login requires your browser to
                    be JavaScript enabled.</p>
                <a style="color: #fff; text-decoration: none;" id="noscriptbtn"
                   href="https://www.secsign.com/support/" target="_blank">SecSign Support</a>
            </noscript>
            <div style="display:none;" id="secsignidplugin">
                <!-- Page Login -->
                <div id="secsignid-page-login">
                    <div class="secsignidlogo"></div>
                    <div id="secsignid-error"></div>
                    <div id="secsignid-loginform">
                        <div class="form-group">
                            <input type="text" class="form-control login-field" value="" placeholder="SecSign ID"
                                   id="login-secsignid" name="secsigniduserid" autofocus="autofocus" autocapitalize="off" autocorrect="off">
                            <label class="login-field-icon fui-user" for="login-secsignid"></label>
                        </div>

                        <div id="secsignid-checkbox">
		        <span>
	                <input id="rememberme" name="rememberme" type="checkbox" value="rememberme" checked>
	                <label for="rememberme">Remember my SecSign ID</label>
	            </span>
                        </div>
                        <button id="secloginbtn" type="submit">Log in</button>
                    </div>
                    <div class="secsignid-login-footer">
                        <a href="#" class="infobutton" id="secsignid-infobutton">Info</a>
                        <a href="#" class="linktext" id="secsignid-pw">Log in with a password</a>

                        <div class="clear"></div>
                    </div>
                </div>

                <!-- Page Password Login -->
                <div id="secsignid-page-pw">
                    <div class="secsignidlogo"></div>
                    <div id="login-form">
                        <input type="text" id="t3-username" name="username" value="" placeholder="Username"
                               class="t3-username" autofocus="autofocus">
                        <input type="password" id="t3-password" name="p_field" value="" placeholder="Password"
                               class="t3-password">
                        <!-- ###INTERFACE_SELECTOR### begin -->
                        <div class="t3-login-interface" id="t3-login-interface-section">

                            <select id="t3-interfaceselector" name="interface" class="c-interfaceselector" tabindex="3">
                                <option value="backend">Backend</option>
                                <option value="frontend">Frontend</option>
                            </select>
                        </div>
                        <!-- ###INTERFACE_SELECTOR### end -->
                        <input type="submit" name="commandLI" id="t3-login-submit" value="Login"
                               class="t3-login-submit">
                    </div>

                    <div class="secsignid-login-footer">
                        <a class="linktext" href="#" id="secsignid-login-secsignid">Log in with SecSign ID</a>

                        <div class="clear"></div>
                    </div>
                </div>

                <!-- Page Info SecSign Login -->
                <div id="secsignid-page-info">
                    <div class="secsignidlogo secsignidlogo-left"></div>
                    <h3 id="headinginfo">Eliminate Passwords and Password Theft.</h3>

                    <div class="clear"></div>
                    <p>Protect your organization and your sensitive data with two-factor authentication.</p>
                    <a id="secsignid-learnmore" href="https://www.secsign.com/products/secsign-id/" target="_blank">Learn
                        more</a>

                    <img style="margin: 0 auto;width: 100%;display: block;max-width: 200px;"
                         src="../typo3conf/ext/secsign/Resources/Public/images/secsignhelp.png">

                    <a class="linktext" id="secsignid-info-secsignid" href="#">&lt; Go back to the login screen</a>

                    <a style="color: #fff; text-decoration: none;"
                       href="https://www.secsign.com/try-it/#login" target="_blank"
                       id="secsignidapp1">See how it works</a>

                    <div class="clear"></div>
                </div>

                <!-- Page Accesspass -->
                <div id="secsignid-page-accesspass">
                    <div class="secsignidlogo"></div>

                    <div id="secsignid-accesspass-container">
                        <img id="secsignid-accesspass-img"
                             src="../typo3conf/ext/secsign/Resources/Public/images/preload.gif">
                    </div>

                    <div id="secsignid-accesspass-info">
                        <a href="#" class="infobutton" id="secsignid-questionbutton">Info</a>

                        <p class="accesspass-id">Access pass for <b id="accesspass-secsignid"></b></p>

                        <div class="clear"></div>
                    </div>

                    <div id="secsignid-accesspass-form">
                        <button id="secsignid-cancelbutton" type="submit">Cancel</button>

                        <!-- OK -->
                        <input type="hidden" name="check_authsession" id="check_authsession" value="1"/>
                        <input type="hidden" name="option" value="com_secsignid"/>
                        <input type="hidden" name="task" value="getAuthSessionState"/>

                        <!-- Cancel
                        <input type="hidden" name="cancel_authsession" id="cancel_authsession" value="0"/>
                        -->

                        <!-- Values -->
                        <input type="hidden" name="return" value=""/>
                        <input type="hidden" name="secsigniduserid" value=""/>
                        <input type="hidden" name="secsignidauthsessionid" value=""/>
                        <input type="hidden" name="secsignidrequestid" value=""/>
                        <input type="hidden" name="secsignidservicename" value=""/>
                        <input type="hidden" name="secsignidserviceaddress" value=""/>
                        <input type="hidden" name="secsignidauthsessionicondata" value=""/>
                        <input type="hidden" name="redirect_to" value=""/>

                    </div>
                </div>

                <!-- Page Question SecSign Accesspass -->
                <div id="secsignid-page-question">
                    <div class="secsignidlogo secsignidlogo-left"></div>
                    <h3 id="headingquestion">How to sign in with SecSign ID</h3>

                    <div class="clear"></div>
                    <p>In order to log in using your SecSign ID, you need to follow the following steps:</p>
                    <ol>
                        <li>Open the SecSign ID app on your mobile device</li>
                        <li>Tap your ID</li>
                        <li>Enter your PIN or passcode or scan your fingerprint</li>
                        <li>Select the correct access symbol</li>
                    </ol>

                    <a class="linktext" id="secsignid-question-secsignid" href="#">&lt; Go back to the Access Pass
                        verification</a>

                    <a style="color: #fff; text-decoration: none;" class="button-secsign blue"
                       href="https://www.secsign.com/try-it/#account" target="_blank" id="secsignidapp2">Get the SecSign
                        ID App</a>

                    <div class="clear"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
	window.onload = function(){
		if (showPW == 'true') {
			el = document.getElementById('secsignidform');
			el.parentNode.removeChild(el);
		} else {
			el = document.getElementById('pwform');
			el.parentNode.removeChild(el);
		
			document.getElementById('login-secsignid').focus();
		}
    }
</script>
<!-- ###PAGE### end -->


<!-- ###LOGIN_FORM### begin -->

<!-- ###LOGIN_ERROR### begin -->
<div id="t3-login-error" class="t3-login-alert t3-login-alert-error">
    <h2>###ERROR_LOGIN_TITLE###</h2>

    <p>###ERROR_LOGIN_DESCRIPTION###</p>
</div>
<!-- ###LOGIN_ERROR### end -->


<input type="text" id="login-user" name="username" value="###VALUE_USERNAME###" placeholder="###LABEL_USERNAME###"
       class="t3-username" autofocus="autofocus"/>
<input type="password" id="login-pw" name="p_field" value="###VALUE_PASSWORD###" placeholder="###LABEL_PASSWORD###"
       class="t3-password"/>

<!-- ###INTERFACE_SELECTOR### begin -->
<div class="t3-login-interface" id="t3-login-interface-section">
    ###VALUE_INTERFACE###
</div>
<!-- ###INTERFACE_SELECTOR### end -->

<input type="submit" onclick="disableBtn();return true;" name="commandLI" id="t3-login-submit" value="###VALUE_SUBMIT###" class="t3-login-submit"/>
<a href="#" onclick="gotoSecSignLogin();return false;" style="margin-top:20px" id="secsignid-login-secsignid">Log in with SecSign ID</a>

<!-- ###LOGIN_FORM### end -->





















