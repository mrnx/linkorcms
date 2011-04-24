

function CheckTopicForm(f){
	if(f.topic_title.value != '' && f.text.value != ''){
        return true;
	}else if(f.topic_title.value == ''){
    	alert('Вы не ввели название темы!');
    	return false;
	}else if(f.text.value == ''){
    	alert('Вы не ввели сообщение!');
    	return false;
	}
	return false;
}

function DeletePost(post,topic, page){
	var en = confirm("Вы уверены в том, что желаете удалить сообщение ?");
	if(en){
		window.location = "index.php?name=forum&op=deletepost&topic="+topic+"&post="+post+"&page="+page+"&ok=1";
	}
}

function DeleteTopic(topic, page){
	var en = confirm("Вы уверены в том, что желаете удалить тему ?");
	if(en){
		window.location = "index.php?name=forum&op=deletetopic&topic="+topic+"&page="+page+"&ok=1";
	}
}

function ShowPostYelow(divId,check,divId1){
	var icount = 0;
	for(var i = 0; i < divId.elements.length; i++){
		elm = divId.elements[i];
		if (elm.type == "checkbox" && elm.checked == true){
			icount++;
		}
	}
	var cheked= document.getElementById(check).checked;
	var ddp =document.getElementById(divId1);
	ddp.style.backgroundColor=(cheked?'#FFFFCC':'#fff');
	var go = document.getElementById('go');
	var go2 = document.getElementById('count');
	if(icount > 0){
		go.value = 'Применить ('+ icount+ ')';
	}else{
		go.value = 'Применить';
	}
	go2.innerHTML = 'всего ' + icount;
}

function forum_insert_text(m, mvalue)
{
	if(document.selection){
		m.focus();
		var sel = document.selection.createRange();
		sel.text = mvalue;
	}else if(m.selectionStart || m.selectionStart=="0"){
		var start = m.selectionStart;
		var end = m.selectionEnd;
		var scrollTop = m.scrollTop;
		var scrollLeft = m.scrollLeft;
		m.value = m.value.substring(0, start) + mvalue + m.value.substring(end, m.value.length);
		m.scrollTop = scrollTop;
		m.scrollLeft = scrollLeft;
		m.focus();
	}else{
		m.value += mvalue;
	}
}

// Вставить цитату
function insquote(edit, formid, post, name){
	form = document.getElementById(formid);
	form.style.display = "block";
	form.style.visibility = "visible"; // Показать форму
	m = document.getElementById(edit); // Поле текста сообщения
	if(!m){
		return false;
	}
	// Получаем выделенный текст
	var sel = "";
	if(window.getSelection){
		sel = window.getSelection().toString();
	}else if(document.getSelection){
		sel = document.getSelection();
	}else if(document.selection){
		sel = document.selection.createRange().text;
	}else{
		sel = "";
	}
	var mvalue = '';
	if(sel == ''){
		post = document.getElementById(post);
		if(post.innerText){
			var text = post.innerText;
		}else{
			var text = post.textContent;
		}
		mvalue = '[b]' + name + ':[/b]' + '[quote]' + text + '[/quote]';
	}else{
		mvalue = '[b]' + name + ':[/b]' + '[quote]' + sel + '[/quote]';
	}
	forum_insert_text(m, mvalue);
	m.focus();
	return false;
}

// Вставить имя
function insname(edit, id, name){
	text = document.getElementById(edit);
	ddp = document.getElementById(id);
	ddp.style.display = "block";
	ddp.style.visibility = "visible";
	if(!text){
		return;
	}
	forum_insert_text(text, '[b]' +  name + '[/b]');
	text.focus();
}

function link_post(plink){
	msg = window.open("","msg","height=100,width=400,left=200,top=200");
	msg.document.write("<html><title>Ссылка на сообщение</title>");
	msg.document.write("<body bgcolor='white' onblur=window.close()>");
	msg.document.write("<center>Ссылка на это сообщение.<BR><INPUT TYPE=\"text\" maxlength=\"255\" style=\" width:100%;\" value="+plink+"></center>");
	msg.document.write("</body></html><p>");
}