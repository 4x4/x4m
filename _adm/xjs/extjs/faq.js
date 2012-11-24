function processFAQ(async,form_name, cat_id, sendbuttonId, callback){

    var cform = $("form[name='"+form_name+"']");
    var user = new LiveValidation('FAQuser'+cat_id, {validMessage:''});
        user.add(Validate.Presence, {failureMessage:'Обязательно для заполнения'});
    var email = new LiveValidation('FAQemail'+cat_id, {validMessage:''});
        email.add(Validate.Presence, {failureMessage:'Обязательно для заполнения'});
        email.add(Validate.Email, {failureMessage:'Это не адрес электронной почты'});
    var question = new LiveValidation('FAQquestion'+cat_id, {validMessage:''});
        question.add(Validate.Presence, {failureMessage:'Обязательно для заполнения'});
    
    var captcha = new LiveValidation('FAQcaptcha'+cat_id, {validMessage:'Код верный!'});
        captcha.add(Validate.Presence, {failureMessage:'Обязательно для заполнения'});
        captcha.add(Validate.Length, {minimum:4, maximum:4, tooShortMessage:'Должно быть не менее 4 символов!'});
        captcha.add(Validate.Custom, {against: function(code){
                if(code.length == 4){
                    var xcaptcha = new fConnector('faq');
                        xcaptcha.execute({check_captcha_code:{captcha:code, cat_id : cat_id}});
                            if(xcaptcha.result.captcha == true){return true;}
                            else { return false; }
                }
            }, failureMessage: "Неверный код подтверждения!"});
    
    
    $('#'+sendbuttonId).click(function(){
        if (async) {
            var xfaq = new fConnector('faq');
            var cfaq = xoad.html.exportForm('faq_form'+cat_id);
            cfaq.captcha_id = cat_id;
            xfaq.execute({add_question_async:{fdata:cfaq}});
            callback(xfaq.result.send);
        } else {
            document[form_name].submit();
        }
    });
}

function updateCaptchaFAQ (id){ document.getElementById('FAQcaptcha_img'+id).src = 'captcha.php?fid='+id+'&rand='+ Math.floor(Math.random()*1000); }

