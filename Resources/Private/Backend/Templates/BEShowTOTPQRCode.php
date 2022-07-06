<f:layout name="Login" />

      
           
         
 <f:section name="loginFormFields">   
         
 </form>
    <link rel="stylesheet" href="/typo3conf/ext/secsign/Resources/Public/css/secsignid_layout.css"/>

            <style>
                h4 {
                    margin-bottom: 25px;
                }

                #qr-code {
                    width: 25%;
                    margin-right: 30px;
                    object-fit: contain;
                    float: left;
                }

                #qr-form h1 {
                    margin-bottom: 25px;
                }

                #qr-form h1 p {
                    font-size: 14px;
                }

                #qr-form h2 {
                    margin-top: 15px;
                    margin-bottom: 10px;
                }

                #qr-show-secret {
                    margin-top: 20px;
                }

                .link-container {
                    margin-top: 10px;
                }

                #title {
                    margin-top: 0;
                    margin-bottom: 30px;
                }
            </style>
    
    
            <div id="dialog" title="Basic dialog">
                <div id="content">
                    <div id="qr-form-container" class="login-form-container">
                        
                        <div id="qr-form" class="login-form">
                            <div>
                                <center>
                                    <h1>
                                       <f:translate key="LLL:EXT:secsign/Resources/Private/Language/locallang.xlf:secsign.login.totp.heading"/>
                                    </h1>
                                </center>
                                <div class="qrrow">
                                    <img id="qr-code" src="data:image/png;base64,{totpQRCode}" />
                                    <div class="qrcolumn">
                                        <h2><f:translate key="LLL:EXT:secsign/Resources/Private/Language/locallang.xlf:secsign.login.totp.register.subheading"/></h2>
                                        <p><f:translate key="LLL:EXT:secsign/Resources/Private/Language/locallang.xlf:secsign.login.totp.register.text.1"/></p>
                                        <p><f:translate key="LLL:EXT:secsign/Resources/Private/Language/locallang.xlf:secsign.login.totp.register.text.2"/></p>
                                        <a id="qr-show-secret" style="color:#0000EE">
                                            <f:translate key="LLL:EXT:secsign/Resources/Private/Language/locallang.xlf:secsign.login.totp.register.code.link"/>
                                        </a>
                                        <p id="qr-secret" style="display: none;">
                                            <f:translate key="LLL:EXT:secsign/Resources/Private/Language/locallang.xlf:secsign.login.totp.register.code.text"/>{totpQRSecret}
                                        </p>
                                    </div>
                                
                                

                                    <div class="button-container">
                                        <button id="qr-button-next" class="submit-button submit-button-small">
                                            <span class="submit-button-text"><f:translate key="LLL:EXT:secsign/Resources/Private/Language/locallang.xlf:secsignid.button.next"/></span>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="loginrow" style="display:none">
                                    <form id="login-form" method="POST">
                                        <input type="hidden" name="secsign_method" value="secsign_checkTOTP" />

                                        <input type="hidden" name="returnURL" value="{RETURN_URL}" />
                                        <input type="hidden" name="secsigniduserid" value="{secsignid}" />
                                        <input type="hidden" name="secsign_username" value="{secsign_username}" />
                                        <h4><f:translate key="LLL:EXT:secsign/Resources/Private/Language/locallang.xlf:secsign.login.totp.text"/></h4>
                                        <center><h5>{secsignid}</h5></center>
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
                    </div>
                </div>
            </div>
            
            <script>
                $(document).ready(function (event) {
                    $(".typo3-login-wrap").css('max-width','700px');
                    $("#t3-login-submit").remove();
                    $("#login").css('overflow-y','scroll');
                    $(".typo3-login").css('position','relative');
                    
                    $("#qr-show-secret").click(function() {
                            $("#qr-secret").show();    
                    });
                    
                    $("#qr-button-next").click(function() {
                            $(".qrrow").hide(); 
                            $(".loginrow").show();
                    });
                });
                  

            </script>
    </f:section >
    
     <div id="secsign-content">
            
        
            <div class="login-form-container">
                <div class="login-form">
                    <h2><f:translate key="LLL:EXT:secsign/Resources/Private/Language/locallang.xlf:secsign.login.heading"/></h2>

                    <form id="login-form" method="POST">
                        <input type="hidden" name="secsign_method" value="secsign_checkLogin" />
                        
                        <input type="hidden" name="returnURL" value="{RETURN_URL}" />
                        
                        <div id="login-form-inner">
                            {ERROR_MSG}
                            <div class="login-form-field">
                                <label class="login-form-field-label">
                                    <f:translate key="LLL:EXT:secsign/Resources/Private/Language/locallang.xlf:secsign.login.username.label"/>
                                </label>
                                <div class="login-form-field-input">
                                    <input required id="input-username" name="secsign_username" type="text">
                                </div>
                            </div>
                            <div class="login-form-field">
                                <label class="login-form-field-label">
                                   <f:translate key="LLL:EXT:secsign/Resources/Private/Language/locallang.xlf:secsign.login.password.label"/>
                                </label>
                                <div class="login-form-field-input login-form-field-password">
                                    <input required id="input-password" name="secsign_password" type="password">
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

    
    
