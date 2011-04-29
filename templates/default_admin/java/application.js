
// Клиентский код веб-приложения Админ-панели

(function(window, $){

	var document = window.document;

	window.AdminFn = function( AdminFile, Ajax ){
		this.AdminFile = AdminFile;
		this.Ajax = Ajax;
	}

	window.AdminFn.prototype = {

		CheckButton: function( BtnNo, event ){
			e = event || window.event || window.Event;
			return (e.which == BtnNo);
		},

		// Загрузка страницы Админ-панели
		LoadPage: function( Url, event ){
			if(this.CheckButton(1, event)){ // Только левая кнопка мыши
				document.location = Url;
				return true;
			}else{
				return false;
			}
		},

		// Переход по внешней ссылке
		Leave: function( Url, Blank, event ){
			if(Blank){
				window.open(Url);
			}else{
				location = Url;
			}
			return false;
		}
	};

	window.Admin = new AdminFn('admin.php');

})(window, jQuery);

function MailToMenu(){
	window.open(
		'index.php?name=plugins&p=mail',
		'Mail',
		'resizable=yes,scrollbars=yes,menubar=no,status=no,location=no,width=700,height=580,screenX=300,screenY=50'
	);
	return false;
}

function SpeedConfirmButtonClick( Confirm, Object ){
	return confirm(Confirm);
}

function SpeedStatusButtonClick( EnabledTitle, DisabledTitle, EnabledImage, DisabledImage, AjaxQueryUrl, Object ){
	var img = $(Object).children("img:first").get(0);
	var src, title;
	if($(img).attr("src") == EnabledImage){
		src = DisabledImage;
		title = DisabledTitle;
	}else{
		src = EnabledImage;
		title = EnabledTitle;
	}
	$(img).attr("src", 'images/ajax-loader.gif');
	$(img).attr("title", 'Обновление статуса');

	$(".ajax_indicator").ajaxStart(GlobalAjaxStart).ajaxStop(GlobalAjaxStop);
	$.ajax({
		url: AjaxQueryUrl,
		dataType: "text",
		success: function(){
			$(img).attr("src", src);
			$(img).attr("title", title);
		},
		cache: false
	});
	return false;
}