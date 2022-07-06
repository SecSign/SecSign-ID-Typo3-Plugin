<f:layout name="Login" /> 
<f:section name="loginFormFields">       
    
</form>
       
   
         <link rel="stylesheet" href="/typo3conf/ext/secsign/Resources/Public/css/secsignid_layout.css"/> 

        <div id="secsign-content">
            
        
            <div class="login-form-container">
                <div class="login-form">
                    <h2><f:translate key="LLL:EXT:secsign/Resources/Private/Language/locallang.xlf:secsign.login.mailotp.heading"/></h2>

                    <form id="login-form" method="POST">
                        <input type="hidden" name="secsign_method" value="secsign_checkMailOTP" />
                        <input type="hidden" name="returnURL" value="<f:format.raw>{RETURN_URL}</f:format.raw>" />
                        <input type="hidden" name="secsigniduserid" value="<f:format.raw>{secsignid}</f:format.raw>" />
                        <input type="hidden" name="secsign_username" value="<f:format.raw>{secsign_username}</f:format.raw>" />
                        <h4><f:translate key="LLL:EXT:secsign/Resources/Private/Language/locallang.xlf:secsign.login.mailotp.text"/></h4>
                        <center><h5><f:format.raw>{secsignid}</f:format.raw></h5></center>
                        <div id="login-form-inner">
                            <f:format.raw>{ERROR_MSG}</f:format.raw>
                            <div class="login-form-field">
                                <label class="login-form-field-label">
                                    <f:translate key="LLL:EXT:secsign/Resources/Private/Language/locallang.xlf:secsign.login.mailotp.code.label"/>
                                </label>
                                <div class="login-form-field-input">
                                    <input required id="input-totp" name="secsign_mailotp" type="text">
                                </div>
                            </div>
                            
                      

                            <button class="submit-button">
                                <span class="submit-button-text">
                                    <f:translate key="LLL:EXT:secsign/Resources/Private/Language/locallang.xlf:secsign.login.button.text"/>
                                </span>
                            </button>
                            
                            <a target="_blank" href="https://secsign.com">
                                <div id="footer-image"></div>
                            </a>
                        </div>

                        

						

                    </form>

                </div>
            </div>
			
    			
        </div>
        <script>
            $(document).ready(function ($) {
                
                $(".typo3-login-wrap").css("max-width","450px");
                $("#t3-login-submit-section").remove();
                $(".typo3-login").css("position","inherit");
            });
        </script>
    
     <f:if condition="<f:format.raw>{switchAllowed}</f:format.raw>"> 
        <script>
            $(document).ready(function (event) {

                $("#secsignid-switch-link").insertAfter($(".login-form-field"));
                $("#login-form").append("<input type='hidden' name='secsignidswitchallowed' value='true'/>");
                $("#change").click(function ()
                {
                    $("#secsignid-switch-form").submit();
                });
            });


        </script>
        <form style="display:none" class="login-form" name="accesspassAuthSecsign" method="post" id="secsignid-switch-form">
            <input type="hidden" name="secsign_method" value="secsign_changeMethod"/>

            <input type="hidden" name="secsign_changeFrom" value="mailotp"/>

            <input type="hidden" name="secsigniduserid" value="<f:format.raw>{secsignid}</f:format.raw>"/>
            <input type="hidden" name="returnURL" value="<f:format.raw>{RETURN_URL}</f:format.raw>" />
            <input type="hidden" name="secsign_username" value="<f:format.raw>{secsign_username}</f:format.raw>" />



        </form>
        <center id="secsignid-switch-link">
                <a id="change" class="link">
                    Switch Method
                </a>
        </center>
     </f:if>
    
   

</f:section>
