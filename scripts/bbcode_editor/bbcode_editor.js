
function BBCodeButton(title, image, event){
	return '<div class="bbcode_button"><img src="'+ image +'" onClick="'+ event +'" title="'+ title +'" alt="'+ title +'" /></div>';
}

function BBCodeToolBar(id_name){
	document.write("<div class=\"bbcode_toolbar\">");

	document.write(BBCodeButton('Жирный', 'scripts/bbcode_editor/images/bold.png', "BBCodeAddTag('[b]','[/b]','" + id_name + "')"));
	document.write(BBCodeButton('Курсив', 'scripts/bbcode_editor/images/italic.png', "BBCodeAddTag('[i]','[/i]','" + id_name + "')"));
	document.write(BBCodeButton('Подчеркнутый', 'scripts/bbcode_editor/images/underline.png', "BBCodeAddTag('[u]','[/u]','" + id_name + "')"));
	document.write(BBCodeButton('Зачеркнутый', 'scripts/bbcode_editor/images/strike.png', "BBCodeAddTag('[s]','[/s]','" + id_name + "')"));

	document.write(BBCodeButton('По левому краю', 'scripts/bbcode_editor/images/left.png', "BBCodeAddTag('[left]','[/left]','" + id_name + "')"));
	document.write(BBCodeButton('По центру', 'scripts/bbcode_editor/images/center.png', "BBCodeAddTag('[center]','[/center]','" + id_name + "')"));
	document.write(BBCodeButton('По правому краю', 'scripts/bbcode_editor/images/right.png', "BBCodeAddTag('[right]','[/right]','" + id_name + "')"));
	document.write(BBCodeButton('По ширине', 'scripts/bbcode_editor/images/justify.png', "BBCodeAddTag('[justify]','[/justify]','" + id_name + "')"));

	document.write("<select class=\"bbcode_select\" id=\"boxh\" name=\"boxh\" onchange=\"BBCodeAddTagSelect('boxh','" + id_name + "');\" title=\"Заголовки\" tabIndex=\"100\">");
	document.write("<option value=\"\" >Заголовок</option>\n\
			<option value=\"h1\">H1</option>\n\
			<option value=\"h2\">H2</option>\n\
			<option value=\"h3\">H3</option>\n\
			<option value=\"h4\">H4</option>\n\
			<option value=\"h5\">H5</option>\n\
			<option value=\"h6\">H6</option>");
	document.write("</select>");

	document.write("<select class=\"bbcode_select\" id=\"boxfz\" name=\"boxfz\" onchange=\"BBCodeAddTagSelectFontSize('boxfz','" + id_name + "');\" title=\"Размер шрифта\"  tabIndex=\"101\"><option value=\"\" >Размер шрифта</option><option value=\"8\" >8</option><option value=\"10\">10</option><option value=\"12\">12</option><option value=\"14\">14</option><option value=\"16\">16</option><option value=\"18\">18</option><option value=\"20\">20</option><option value=\"22\">22</option><option value=\"24\">24</option></select>");

	document.write("</div>");
	document.write("<div class=\"bbcode_toolbar\">");

	document.write(BBCodeButton('Цитата', 'scripts/bbcode_editor/images/quote.png', "BBCodeAddTag('[quote]','[/quote]','" + id_name + "')"));
	document.write(BBCodeButton('Код', 'scripts/bbcode_editor/images/code.png', "BBCodeAddTag('[code]','[/code]','" + id_name + "')"));
	document.write(BBCodeButton('PHP код', 'scripts/bbcode_editor/images/php.png', "BBCodeAddTag('[php]','[/php]','" + id_name + "')"));
	document.write(BBCodeButton('Изображение', 'scripts/bbcode_editor/images/image.png', "BBCodeAddTagImage('" + id_name + "')"));

	document.write(BBCodeButton('Код видео (Youtube/Rutube)', 'scripts/bbcode_editor/images/video.png', "BBCodeAddTagVideo('" + id_name + "')"));
	document.write(BBCodeButton('Ссылка', 'scripts/bbcode_editor/images/link.png', "BBCodeAddTagUrl('" + id_name + "')"));
	document.write(BBCodeButton('E-Mail адрес', 'scripts/bbcode_editor/images/email.png', "BBCodeAddTagEmail('" + id_name + "')"));
	document.write(BBCodeButton('Скрытый текст', 'scripts/bbcode_editor/images/hide.png', "BBCodeAddTag('[hide]','[/hide]','" + id_name + "')"));
	document.write(BBCodeButton('Горизонтальная линия', 'scripts/bbcode_editor/images/hr.png', "BBCodeAddTag('[hr]','','" + id_name + "')"));
	document.write(BBCodeButton('Маркированный список', 'scripts/bbcode_editor/images/li.png', "BBCodeAddTag('[*]','','" + id_name + "')"));

	document.write(BBCodeButton('Цвет выделеного текста', 'scripts/bbcode_editor/images/color.png', "ShowHide('fontcolor')"));

	document.write(BBCodeButton('Увеличить размер поля', 'scripts/bbcode_editor/images/down.png', "Resize('" + id_name + "', true)"));
	document.write(BBCodeButton('Уменьшить размер поля', 'scripts/bbcode_editor/images/up.png', "Resize('" + id_name + "', false)"));

	document.write("</div>");

	document.write("<div id=\"fontcolor\" class=\"bbcode_colorbar\" style=\"visibility: hidden; display: none;\">\n\
			<div class=\"bbcode_color\" style=\"background-color: #FF0000;\" onclick=\"BBCodeAddTagFontColor('#FF0000','" + id_name + "');\"></div>\n\
			<div class=\"bbcode_color\" style=\"background-color: #FFFF00;\" onclick=\"BBCodeAddTagFontColor('#FFFF00','" + id_name + "');\"></div>\n\
			<div class=\"bbcode_color\" style=\"background-color: #6600FF;\" onclick=\"BBCodeAddTagFontColor('#6600FF','" + id_name + "');\"></div>\n\
			<div class=\"bbcode_color\" style=\"background-color: #FFCC00;\" onclick=\"BBCodeAddTagFontColor('#FFCC00','" + id_name + "');\"></div>\n\
			<div class=\"bbcode_color\" style=\"background-color: #755a5c;\" onclick=\"BBCodeAddTagFontColor('#755a5c','" + id_name + "');\"></div>\n\
			<div class=\"bbcode_color\" style=\"background-color: #a9b5ef;\" onclick=\"BBCodeAddTagFontColor('#a9b5ef','" + id_name + "');\"></div>\n\
			<div class=\"bbcode_color\" style=\"background-color: #d65a20;\" onclick=\"BBCodeAddTagFontColor('#d65a20','" + id_name + "');\"></div>\n\
			<div class=\"bbcode_color\" style=\"background-color: #e39230;\" onclick=\"BBCodeAddTagFontColor('#e39230','" + id_name + "');\"></div>\n\
			<div class=\"bbcode_color\" style=\"background-color: #a71334;\" onclick=\"BBCodeAddTagFontColor('#a71334','" + id_name + "');\"></div>\n\
			<div class=\"bbcode_color\" style=\"background-color: #590099;\" onclick=\"BBCodeAddTagFontColor('#590099','" + id_name + "');\"></div>\n\
			<div class=\"bbcode_color\" style=\"background-color: #d40088;\" onclick=\"BBCodeAddTagFontColor('#d40088','" + id_name + "');\"></div>\n\
			<div class=\"bbcode_color\" style=\"background-color: #0030ac;\" onclick=\"BBCodeAddTagFontColor('#0030ac','" + id_name + "');\"></div>\n\
			<div class=\"bbcode_color\" style=\"background-color: #676f11;\" onclick=\"BBCodeAddTagFontColor('#676f11','" + id_name + "');\"></div>\n\
			<div class=\"bbcode_color\" style=\"background-color: #769321;\" onclick=\"BBCodeAddTagFontColor('#769321','" + id_name + "');\"></div>\n\
			<div class=\"bbcode_color\" style=\"background-color: #33CC00;\" onclick=\"BBCodeAddTagFontColor('#33CC00','" + id_name + "');\"></div>\n\
			</div>");
}

function BBCodeAddTagUrl(id_name){
	var url_link = prompt('Адрес ссылки (URL):','http://');
	if(url_link.length > 0){
		var link_name = prompt('Название ссылки (можно не указывать):');
		if(link_name.length > 0){
			var tag1 = '[url='+url_link+']';
			var tag2 = '[/url]';
			BBCodeAddTag(tag1, tag2, id_name, link_name);
		}else{
			BBCodeAddTag('[url]', '[/url]', id_name, url_link);
		}
	}
}

function BBCodeAddTagFontColor(color, id_name){
	text = document.getElementById(id_name);
	var tag1 = '[color='+color+']' ;
	var tag2 = '[/color]';
	BBCodeAddTag(tag1 , tag2 , id_name);
	ShowHide('fontcolor');
}

function BBCodeAddTagSelectFontSize(box, id_name){
	text = document.getElementById(id_name);
	boxs = document.getElementById(box);
	var tag1 = '[size='+boxs.value+']' ;
	var tag2 = '[/size]';
	boxs.options[0].selected = true;
	BBCodeAddTag(tag1 , tag2 , id_name);
}

function BBCodeAddTagSelect(box, id_name){
	text = document.getElementById(id_name);
	boxs = document.getElementById(box);
	var tag1 = '['+boxs.value+']' ;
	var tag2 = '[/'+boxs.value+']';
	boxs.options[0].selected = true;
	BBCodeAddTag(tag1, tag2, id_name);
}


function BBCodeAddTagVideo(id_name){
	var url = prompt('Вставте код видео (Youtube/Rutube):');
	if (url.length > 0){
		BBCodeAddTag('[video]', '[/video]', id_name, url );
	}
}

function BBCodeAddTagEmail(id_name){
	var url = prompt('Адрес электронной почты:');
	if (url.length > 0){
		BBCodeAddTag('[email]' , '[/email]', id_name, url);
	}
}

function BBCodeAddTagImage(id_name){
	var url_image = prompt('Вставте полный URL изображения:','http://');
	if (url_image.length > 0){
		BBCodeAddTag('[img]', '[/img]', id_name, url_image);
	}
}


function BBCodeAddTag(tag1, tag2, id_name, ins){
	if(!ins) ins='';
	text = document.getElementById(id_name);
	if (document.selection){
		text.focus();
		var sel = document.selection.createRange();
		sel.text = tag1 + ins + sel.text + tag2;
	}else{
		var start = text.selectionStart;
		var end = text.selectionEnd;
		var len = text.value.length;
		var scrollTop = text.scrollTop;
		var scrollLeft = text.scrollLeft;
		var sel2 = tag1 + ins + text.value.substring(start, end) + tag2;
		text.value =  text.value.substring(0,start) + sel2 + text.value.substring(end,len);
		text.scrollTop = scrollTop;
		text.scrollLeft = scrollLeft;
		text.selectionStart = start + tag1.length;
		text.selectionEnd = end + tag1.length;
		text.focus();
	}
}

function GetS( Obj, name ){
	if(Obj.currentStyle){ // IE Api
		return Obj.currentStyle[name];
	}else if(window.getComputedStyle){ // W3C Api
		return window.getComputedStyle(Obj, null)[name];
	}else{
		return '';
	}
}

function Resize(id_name, actions){
	m = document.getElementById(id_name);
	if(actions){
		newheight = parseInt(GetS(m, 'height'), 10) + 50;
		if(newheight >= 60){
			m.style.height = newheight+"px";
		}
	}else{
		newheight=parseInt(GetS(m, 'height'), 10) - 50;
		if(newheight >= 60){
			m.style.height = newheight+"px";
		}
	}
	m.focus();
}
