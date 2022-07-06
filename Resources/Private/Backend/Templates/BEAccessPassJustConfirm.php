<f:layout name="Login" />

      
           
         
 <f:section name="loginFormFields">   
         
 </form>
<link rel="stylesheet" href="/typo3conf/ext/secsign/Resources/Public/css/secsignid_layout.css"/>
    
    <div id="secsign-content">
            <div class="login-form-container">
                <div id="login-form-inner">
                    <form class="login-form" name="accesspassAuthSecsign" method="post" id="secsignid-accesspass-form">
                        <input type="hidden" name="secsign_method" value="secsign_checkAccessPass"/>
                        <input type="hidden" name="secsigniduserid" value="{secsigniduserid}"/>
                        <input type="hidden" name="secsignidauthsessionid" value="{secsignidauthsessionid}"/>
                        <input type="hidden" name="secsignidserviceaddress" value="{secsignidserviceaddress}"/>
                        <input type="hidden" name="returnURL" value="{RETURN_URL}" />
                        <input type="hidden" name="secsign_username" value="{secsign_username}" />

                        <div id="secsign-accesspass-text" >Please confirm authentication for: {secsigniduserid}</div>
                        
                    </form>
                    <form name="cancelAccessPass" method="post" id="secsignid-accesspass-cancel-form">
                        <button class="submit-button-be btn-login" name="secsignid-cancel-button">
                            <span class="submit-button-text-be"><f:translate key="LLL:EXT:secsign/Resources/Private/Language/locallang.xlf:secsign.button.cancel" /></span>
                        </button>
                        <input type="hidden" name="secsign_method" value="secsign_cancelAccessPass"/>
                        <input type="hidden" name="secsigniduserid" value="{secsigniduserid}"/>
                        <input type="hidden" name="secsignidauthsessionid" value="{secsignidauthsessionid}"/>
                        <input type="hidden" name="secsignidserviceaddress" value="{secsignidserviceaddress}"/>
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

            var timeoutObject=window.setTimeout(function()
            {

                $("#secsignid-accesspass-form").submit();
            }, 3000);
            $("#secsignid-cancelbutton").click(function( )
            {
                clearTimeout(timeoutObject);
            });
            <f:if condition="{switchAllowed<}">
                $("#secsignid-switch-link").insertAfter($("#secsignid-accesspass-form"));
                $("#secsignid-accesspass-form").append("<input type='hidden' name='secsignidswitchallowed' value='true'/>");
                $("#change").click(function ()
                {
                    clearTimeout(timeoutObject);
                    $("#secsignid-switch-form").submit();
                });
            </f:if>
        });



    </script>
    <f:if condition="{switchAllowed}">
        <form style="display:none" class="login-form" name="accesspassAuthSecsign" method="post" id="secsignid-switch-form">
            <input type="hidden" name="secsign_method" value="secsign_changeMethod"/>
            <input type="hidden" name="secsign_changeFrom" value="secsignid"/>

            <input type="hidden" name="secsigniduserid" value="{secsigniduserid}"/>
            <input type="hidden" name="secsignidauthsessionid" value="{secsignidauthsessionid}"/>
            <input type="hidden" name="secsignidserviceaddress" value="{secsignidserviceaddress}"/>
            <input type="hidden" name="returnURL" value="{RETURN_URL}" />




        </form>
        <center id="secsignid-switch-link">
                <a id="change" class="link">
                    Switch Method
                </a>
        </center>
    </f:if>
</f:section>