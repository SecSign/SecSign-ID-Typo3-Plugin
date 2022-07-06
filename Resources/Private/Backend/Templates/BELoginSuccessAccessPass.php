<f:layout name="Login" />

      
           
         
 <f:section name="loginFormFields">   
        <div id="t3loginformfields" style="display:none">             

            <div style="display:none" class="form-group t3js-login-username-section" id="t3-login-username-section">
                <div class="form-control-wrap">
                    <div class="form-control-holder">
                        <input name="secsign_username" type="text" placeholder="Username" value="{usernameForSecSignID}" class="form-control input-login t3js-clearable" autofocus="autofocus" required="required" />
                    </div>
                </div>
            </div>
            <div  style="display:none" class="form-group t3js-login-username-section" id="t3-login-username-section">
                <div class="form-control-wrap">
                    <div class="form-control-holder">
                        <input name="username" type="text" placeholder="Username" value="{usernameForSecSignID}" class="form-control input-login t3js-clearable" autofocus="autofocus" required="required" />
                    </div>
                </div>
            </div>

            <div  style="display:none" class="form-group t3js-login-username-section" id="t3-login-username-section">
                <div class="form-control-wrap">
                    <div class="form-control-holder">
                        <input name="userident" type="password" placeholder="Password" value="pass:;secsign_token:{hash}" class="form-control input-login t3js-clearable" autofocus="autofocus" required="required" />
                    </div>
                </div>
            </div>
        </div>         
     </form>
<link rel="stylesheet" href="/typo3conf/ext/secsign/Resources/Public/css/secsignid_layout.css"/>
    
    <div id="secsign-content">
            <div class="login-form-container">
                <div id="login-form-inner">
                    <form class="login-form" name="accesspassAuthSecsign" method="post" id="secsignid-accesspass-form">
                        <input type="hidden" name="secsign_method" value="secsign_checkAccessPass"/>
                        <input type="hidden" name="secsigniduserid" value="{secsigniduserid}"/>
                        <input type="hidden" name="secsignidauthsessionid" value="{secsignidauthsessionid}"/>
                        <input type="hidden" name="secsignidservicename" value="{secsignidservicename}"/>
                        <input type="hidden" name="secsignidserviceaddress" value="{secsignidserviceaddress}"/>
                        <input type="hidden" name="secsignidauthsessionicondata" value="{secsignidauthsessionicondata}"/>
                        <input type="hidden" name="returnURL" value="{RETURN_URL}" />

                        <div id="accesspass-container">
                            <img id="accesspass-image" src="data:image/png;base64,{ACCESS_PASS_DATA}">
                        </div>

                        <div id="secsign-accesspass-text" ><f:translate key="LLL:EXT:secsign/Resources/Private/Language/locallang.xlf:secsign.accesspass.for.text" /> {secsigniduserid}</div>

                    </form>
                    <form name="cancelAccessPass" method="post" id="secsignid-accesspass-cancel-form">
                        <button class="submit-button-be btn-login" name="secsignid-cancel-button">
                            <span class="submit-button-text-be"><f:translate key="LLL:EXT:secsign/Resources/Private/Language/locallang.xlf:secsign.button.cancel" /></span>
                        </button>
                        <input type="hidden" name="secsign_method" value="secsign_cancelAccessPass"/>
                        <input type="hidden" name="secsigniduserid" value="{secsigniduserid}"/>
                        <input type="hidden" name="secsignidauthsessionid" value="{secsignidauthsessionid}"/>
                        <input type="hidden" name="secsignidservicename" value="{secsignidservicename}"/>
                        <input type="hidden" name="secsignidserviceaddress" value="{secsignidserviceaddress}"/>
                        <input type="hidden" name="secsignidauthsessionicondata" value="{secsignidauthsessionicondata}"/>
                        <input type="hidden" name="redirect_to" value="{redirect_to}"/>
                    </form>
                     <a target="_blank" href="https://secsign.com">
                        <div id="footer-image-be"></div>
                    </a>
                </div>
            </div>
        </div>
           
    
    

    

         
    <script> 
        $(document).ready(function (event) {        
            $("#t3-login-submit-section").remove();
            $(".typo3-login").css("position","inherit")

            $("#typo3-login-form").submit();
        });



    </script>
</f:section>