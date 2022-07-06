<f:layout name="Login" />



<f:section name="loginFormFields">
<script type="text/javascript" src="https://code.jquery.com/jquery-3.6.0.js"></script>
<link rel="stylesheet" href="/typo3conf/ext/secsign/Resources/Public/css/secsignid_layout.css"/>

    <script  type="text/javascript">
        jQuery(document).ready(function (event) {
            $("#typo3-login-form").attr("action",""); 
            $("input[name='login_status']").remove(); 
            $(".typo3-login").css("position","inherit");
            if($(".secsignid-error").text()!=="")
            {
                $(".secsignid-error").css("display","block");
            }
            
            $("#footer-link").insertAfter($("#t3-login-submit-section")); 
        });

    </script>

    
<div id="t3loginformfields">             
    <input type="hidden" name="secsign_method" value="secsign_checkLogin" />
    <div class="secsignid-error" style="display:none">{ERROR_MSG}</div>
    <div class="form-group t3js-login-username-section" id="t3-login-username-section">
        <div class="form-control-wrap">
            <div class="form-control-holder">
                <input name="secsign_username" type="text" placeholder="Username" class="form-control input-login t3js-clearable" autofocus="autofocus" required="required" />
            </div>
        </div>
    </div>
    <div class="form-group t3js-login-password-section" id="t3-login-password-section">
        <div class="form-control-wrap">
            <div class="form-control-holder">
                <input name="secsign_password" type="password" placeholder="Password" class="form-control input-login t3js-clearable" required="required" />
            </div>
        </div>
    </div>
    <a id="footer-link" target="_blank" href="https://secsign.com">
        <div id="footer-image-be"></div>
    </a>
</div>       
    
         



</f:section>















