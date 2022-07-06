<f:layout name="Login" />

      
           
         
 <f:section name="loginFormFields">   
         
 </form>
            <link rel="stylesheet" href="/typo3conf/ext/secsign/Resources/Public/css/secsignid_layout.css"/>
            <div id="dialog" title="Basic dialog">
                <div id="content">
                    <div class="login-form-container">
                        <div id="login-form" class="login-form">
      
                            <div id="secsign-create" style="display:{show-create}">
                                <p><f:translate key="LLL:EXT:secsign/Resources/Private/Language/locallang.xlf:secsign.create.text.1"/></p>
                                <p><f:translate key="LLL:EXT:secsign/Resources/Private/Language/locallang.xlf:secsign.create.text.2"/></p>
                                <p><f:translate key="LLL:EXT:secsign/Resources/Private/Language/locallang.xlf:secsign.create.text.3"/></p>

                                <form id="secsign-create-form" method="POST">

                                    <a id="add-existing" class="button-link" style="display:{add-visible}">
                                        <p><f:translate key="LLL:EXT:secsign/Resources/Private/Language/locallang.xlf:secsign.create.already.link"/></p>
                                    </a>

                                    <input type="hidden" name="secsign_method" value="secsign_free_create" />
                                    <input type="hidden" name="returnURL" value="{RETURN_URL}" />
                                    <input type="hidden" name="secsign_username" value="{secsign_username}"/>
                                    <div class="secsignid-error" style="display:none">{ERROR_MSG}</div>
                                    
                                    <div class="login-form-field">
                                        <label class="login-form-field-label"><f:translate key="LLL:EXT:secsign/Resources/Private/Language/locallang.xlf:secsign.create.new.field"/></label>
                                        <div class="login-form-field-input">
                                            <input required id="input-wishid" name="wishID" type="text" value="">
                                        </div>
                                    </div>

                                    <button id="secsign-create-submit-button" class="submit-button-be btn-login">
                                        <span class="submit-button-text-be">
                                            <f:translate key="LLL:EXT:secsign/Resources/Private/Language/locallang.xlf:secsign.create.new.button"/>
                                        </span>
                                    </button>

                                    <button class="submit-button-be btn-login" id="free-create-cancel-button">
                                        <span class="submit-button-text-be">
                                            <f:translate key="LLL:EXT:secsign/Resources/Private/Language/locallang.xlf:secsign.button.cancel"/>
                                        </span>
                                    </button>


                                </form>
                            </div>
                           
                            <div id="secsign-add" style="display:{show-add}">
                                <p><f:translate key="LLL:EXT:secsign/Resources/Private/Language/locallang.xlf:secsign.add.text.1"/></p>
                                <p><f:translate key="LLL:EXT:secsign/Resources/Private/Language/locallang.xlf:secsign.add.text.2"/></p>
                                <p><f:translate key="LLL:EXT:secsign/Resources/Private/Language/locallang.xlf:secsign.add.text.3"/></p>

                                <form id="secsign-add-form" method="POST">

                                    <a id="create-new" class="button-link" style="display:{create-visible}">
                                        <p><f:translate key="LLL:EXT:secsign/Resources/Private/Language/locallang.xlf:secsign.add.create.link"/></p>
                                    </a>
                                    
                                    <input type="hidden" name="secsign_method" value="secsign_existing_create" />
                                    <input type="hidden" name="returnURL" value="{RETURN_URL}" />
                                    <input type="hidden" name="secsign_username" value="{secsign_username}"/>
                                    <div class="secsignid-error" style="display:none">{ERROR_MSG}</div>
                                    <div class="login-form-field">
                                        <label class="login-form-field-label">
                                            <f:translate key="LLL:EXT:secsign/Resources/Private/Language/locallang.xlf:secsign.add.add.field"/>
                                        </label>
                                        <div class="login-form-field-input">
                                            <input required id="input-addid" name="existingID" type="text">
                                        </div>
                                    </div>

                                    <button id="secsign-add-submit-button" class="submit-button-be btn-login" >
                                        <span class="submit-button-text-be"><f:translate key="LLL:EXT:secsign/Resources/Private/Language/locallang.xlf:secsign.add.add.button"/></span>
                                    </button>

                                    <button class="submit-button-be btn-login" id="add-create-cancel-button" >
                                        <span class="submit-button-text-be"> <f:translate key="LLL:EXT:secsign/Resources/Private/Language/locallang.xlf:secsign.button.cancel"/></span>
                                    </button>
                                
                                </form>
                            </div> 
                        </div>
                    </div>
                </div>
                
            </div>
            
            <script>
                $(document).ready(function (event) {
                    
                    if($(".secsignid-error").text()!=="")
                   {
                       $(".secsignid-error").css("display","block");
                   }
                   
                   $(".typo3-login-wrap").css("max-width","600px");
                   $(".typo3-login").css("position","inherit")
                   
                   $("#t3-login-submit-section").remove();
                   $("#free-create-cancel-button").click(function( )
                    {
                        window.location.href=window.location.href;
                    });
                    
                    $("#add-create-cancel-button").click(function( )
                    {
                        window.location.href=window.location.href;
                    });
                    
                    $("#add-existing").click(function( )
                    {
                        $("#secsign-add").show();
                        $("#secsign-create").hide();
                    });
                   
                   $("#create-new").click(function( )
                    {
                        $("#secsign-add").hide();
                        $("#secsign-create").show();
                    });
                    
                });
                   
                    

            </script>

    
   </f:section>   