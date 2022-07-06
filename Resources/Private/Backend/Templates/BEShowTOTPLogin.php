<f:layout name="Login" />

      
           
         
 <f:section name="loginFormFields">   
         
 </form>
<link rel="stylesheet" href="/typo3conf/ext/secsign/Resources/Public/css/secsignid_layout.css"/>
    
        

        <div id="secsign-content">
            
        
            <div class="login-form-container">
                <div class="login-form">
                    <h2><f:translate key="LLL:EXT:secsign/Resources/Private/Language/locallang.xlf:secsign.login.totp.heading"/></h2>

                    <form id="login-form" method="POST">
                        <input type="hidden" name="secsign_method" value="secsign_checkTOTP" />
                        
                        <input type="hidden" name="returnURL" value="{RETURN_URL}" />
                        <input type="hidden" name="secsigniduserid" value="{secsigniduserid}" />
                        <input type="hidden" name="secsign_username" value="{secsign_username}" />
                        <h4><f:translate key="LLL:EXT:secsign/Resources/Private/Language/locallang.xlf:secsign.login.totp.text"/></h4>
                        <center><h5>{secsigniduserid}</h5></center>
                        <div id="login-form-inner">
                            {ERROR_MSG}
                            <div class="login-form-field">
                                <label class="login-form-field-label">
                                    <f:translate key="LLL:EXT:secsign/Resources/Private/Language/locallang.xlf:secsign.login.totp.code.label"/>
                                </label>
                                <div class="login-form-field-input">
                                    <input required id="input-totp" name="secsign_totp" type="text">
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
        <f:if condition="{switchAllowed<}">
            <form style="display:none" class="login-form" name="accesspassAuthSecsign" method="post" id="secsignid-switch-form">
                <input type="hidden" name="secsign_method" value="secsign_changeMethod"/>

                <input type="hidden" name="secsign_changeFrom" value="totp"/>

                <input type="hidden" name="secsigniduserid" value="<f:format.raw>{secsigniduserid}</f:format.raw>"/>
                <input type="hidden" name="returnURL" value="<f:format.raw>{RETURN_URL}</f:format.raw>" />
                <input type="hidden" name="secsign_username" value="<f:format.raw>{secsign_username}</f:format.raw>" />



            </form>
            <center id="secsignid-switch-link">
                    <a id="change" class="link">
                        Switch Method
                    </a>
            </center>
        </f:if>
    
        <script>
            $(document).ready(function (event) {
                
                $("#t3-login-submit").remove();
                $("#login").css('overflow-y','scroll');
                $(".typo3-login").css('position','relative');

                <f:if condition="{switchAllowed<}">
                    $("#secsignid-switch-link").insertAfter($(".login-form-field"));
                    $("#login-form").append("<input type='hidden' name='secsignidswitchallowed' value='true'/>");
                    $("#change").click(function ()
                    {

                        $("#secsignid-switch-form").submit();
                    });
                </f:if>
            });
            
            
        </script>
    
    
</f:section >