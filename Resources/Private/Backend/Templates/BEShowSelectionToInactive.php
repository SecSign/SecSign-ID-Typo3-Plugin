<f:layout name="Login" />         
         
<f:section name="loginFormFields">   
 <link rel="stylesheet" href="/typo3conf/ext/secsign/Resources/Public/css/secsignid_layout.css"/>
    
    
</form>
    <div id="dialog" title="Basic dialog">
        <div id="content">
            <div class="login-form-container ">
                <div class="login-form">
                    <h1 id="title"><f:translate key="LLL:EXT:secsign/Resources/Private/Language/locallang.xlf:secsignid.login.methodselect.inactive.title"/></h1>
                    <p id="description"><f:translate key="LLL:EXT:secsign/Resources/Private/Language/locallang.xlf:secsignid.login.methodselect.inactive.subtitle"/></p>

                    <form id="selection-form" method="POST">
                        <input id="selected-method" type="hidden" name="selected-method" value="none">
                        <input type="hidden" name="secsign_method" value="secsign_change_to_inactive">
                        <input type="hidden" name="isFirst" value="{isFirst}" />
                        <input type="hidden" name="secsign_username" value="{secsign_username}">
                        <input type="hidden" name="returnURL" value="{RETURN_URL}" />

                        <div >
                            <div id="dialog-select">
                                <div class="column">
                                    <f:if condition="{secsignid}">
                                        <div id="method-active-id" class="column method-item-be selectable-item">
                                            <h1><f:translate key="LLL:EXT:secsign/Resources/Private/Language/locallang.xlf:secsignid.login.methodselect.id.title"/></h1>
                                            <p><f:translate key="LLL:EXT:secsign/Resources/Private/Language/locallang.xlf:secsignid.login.methodselect.id.description"/></p>
                                        </div>
                                    </f:if>
                                    <f:if condition="{fido}">
                                        <div id="method-active-fido" class="column method-item-be selectable-item">
                                            <h1><f:translate key="LLL:EXT:secsign/Resources/Private/Language/locallang.xlf:secsignid.login.methodselect.fido.title"/></h1>
                                            <p><f:translate key="LLL:EXT:secsign/Resources/Private/Language/locallang.xlf:secsignid.login.methodselect.fido.description"/></p>
                                        </div>
                                    </f:if>
                                    <f:if condition="{totp}">    
                                        <div id="method-active-totp" class="column method-item-be selectable-item">
                                            <h1><f:translate key="LLL:EXT:secsign/Resources/Private/Language/locallang.xlf:secsignid.login.methodselect.totp.title"/></h1>
                                            <p><f:translate key="LLL:EXT:secsign/Resources/Private/Language/locallang.xlf:secsignid.login.methodselect.totp.description"/></p>
                                        </div>
                                    </f:if>
                                    <f:if condition="{mailotp}">    
                                        <div id="method-active-mail-otp" class="column method-item-be selectable-item">
                                            <h1><f:translate key="LLL:EXT:secsign/Resources/Private/Language/locallang.xlf:secsignid.login.methodselect.mail.title"/></h1>
                                            <p><f:translate key="LLL:EXT:secsign/Resources/Private/Language/locallang.xlf:secsignid.login.methodselect.mail.description"/></p>
                                        </div>
                                    </f:if>
                                </div>    

                            </div>




                        </div>
                    </form>
                    <form id="back-form" method="POST">
                        <input type="hidden" name="secsign_method" value="secsign_back_from_change_to_inactive">
                        <input type="hidden" name="secsign_username" value="{secsign_username}">
                        <input type="hidden" name="returnURL" value="{RETURN_URL}" />
                        <div class="button-container">
                            <button id="button-inactive-back"
                                class="submit-button submit-button-small submit-button-cancel">
                                <span
                                    class="submit-button-text"><f:translate key="LLL:EXT:secsign/Resources/Private/Language/locallang.xlf:secsign.button.cancel"/></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>

    <script>
        $(document).ready(function (event) {
            
            $(".typo3-login-wrap").css('max-width','600px');
            $("#t3-login-submit").remove();
            $("#login").css('overflow-y','scroll');
            $(".typo3-login").css('position','relative');

            $("#method-active-id").click(function()
            {
                $("#selected-method").val('secsignid');
                $("#selection-form").submit();
            });
            $("#method-active-fido").click(function()
            {
                $("#selected-method").val('fido');
                $("#selection-form").submit();
            });
            $("#method-active-totp").click(function()
            {
                $("#selected-method").val('totp');
                $("#selection-form").submit();
            });
            $("#method-active-mail-otp").click(function()
            {
                $("#selected-method").val('mailotp');
                $("#selection-form").submit();
            });

            
        });

    </script>
</f:section >
