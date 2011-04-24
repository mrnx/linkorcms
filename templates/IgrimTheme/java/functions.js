function ShowHide(id)
{
	ddp = document.getElementById(id);
	if(ddp.style.visibility == "hidden"){
		ddp.style.display = "block";
		ddp.style.visibility = "visible";
	}else{
		ddp.style.display = "none";
		ddp.style.visibility = "hidden";
	}
}

function smilies(id_name, code){

	text = document.getElementById(id_name);
	if(!text){
		return;
	}
	if(document.selection){
		text.focus();
		var sel = document.selection.createRange();
		sel.text = code;
	}else if(text.selectionStart || text.selectionStart=="0"){
		var start = text.selectionStart;
		var end = text.selectionEnd;
		var scrollTop = text.scrollTop;
		var scrollLeft = text.scrollLeft;
		text.value = text.value.substring(0,start) + code + text.value.substring(end, text.value.length);
		text.scrollTop = scrollTop;
		text.scrollLeft = scrollLeft;
		text.focus();
	}else{
		text.value += smile;
	}
	text.focus();

}

function validate_email (email) {
    return /[\w-]+(\.[\w-]+)*@([\w-]+\.)+[a-z]{2,7}/i.test(email);
}

function check_email(email){
    if(!validate_email(email.value)){
		alert('Пожалуйста, укажите действительный адрес контактного Email.');
		return false;
	}
	return true;
}

function check_form(f){
    if(f.name.value != '' && f.email.value != '' && validate_email(f.email.value) && f.subject.value != '' && f.message.value != '' && f.department.value != '0'){
        return true;
    }else if(f.name.value == ''){
    	alert('Пожалуйста, укажите Ваше имя!');
    	return false;
    }else if(f.email.value == '' || !validate_email(f.email.value)){
        alert('Пожалуйста, укажите Ваш действительный адрес E-mail!');
        return false;
    }else if(f.subject.value == ''){
    	alert('Пожалуйста, введите тему сообщения!');
    	return false;
    }else if(f.message.value == ''){
    	alert('Пожалуйста, введите сообщение!');
    	return false;
    }else if(f.department.value == '0'){
    	alert('Пожалуйста, выберите департамент!');
    	return false;
    }else{
        return false;
    }
}

var temp_form_container = null;

function post_reply(container)
{
	var form_container = document.getElementById(container);
	var post_form = document.getElementById('postform');


	post_form.parentNode.removeChild(post_form);
	form_container.appendChild(post_form);

	if(temp_form_container != null){
		ddp = document.getElementById(temp_form_container);
		if(temp_form_container != container && form_container.style.visibility != ddp.style.visibility){
			ShowHide(temp_form_container);
		}
	}
	ShowHide(container);
	temp_form_container = container;

	document.getElementById('postform_parent_id').value = container;

	return false;
}

function CheckFormComment(f) {
	if(f.user_name.value == "" ){
		alert('Не указано имя!');
		return false;
	}
	if(f.user_email.value == ""){
		alert('Не указан e-mail!');
		return false;
	}
	if(f.post_text.value ==""){
		alert('Сообщение слишком короткое!');
		return false;
	}
	if(!check_email(f.user_email)){
		return false;
	}
	return true;
}

