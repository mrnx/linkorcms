
/**
 * Клиентский код веб-приложения Админ-панели
 */

(function(window, $, undefined){

	/**
	 * @class AdminFn
	 * @param AdminFile Относительное имя файла админ-панели
	 * @param Ajax Использовать ajax при загрузке страниц
	 */
	window.AdminFn = function( AdminFile, Ajax ){
		this.AdminFile = AdminFile;
		this.Ajax = Ajax;
		this.splashShows = 0;
		this.splashShowsMax = 0;
		this.liveWatch = [];
		this.liveTimer = null;
	}

	window.AdminFn.prototype = {

		default_breadcrumb_options: {
			title: 'Title',
			icon: 'scripts/lTreeView/theme/icon.png',
			link: '',
			menu: [] // Элементы меню
		},

		initTopMenu: function(options){
			$("#admin_menu").menu(options);
		},

		initBreadCrumbs: function(options){
			var $ul = $("ul#admin_breadcrumbs");
			var html = '';
			for(var i = 0; i < options.length; i++){
				html = '<a href="'+options[i].link+'" onclick="return Admin.CheckButton(2, event);" onMouseDown="return Admin.LoadPage(\''+options[i].link+'\', event);">';
				if(options[i].icon && options[i].icon != ''){
					html += '<img src="'+options[i].icon+'" class="crumb_icon">';
				}
				html += '<span>'+options[i].title+'</span></a>';
				$('<li>').html(html).appendTo($ul);

				if(i != options.length-1 && options[i].menu){
					var $li = $('<li>').appendTo($ul);
					var $a = $('<a>', {href: "#", click: function(){return false;}, class: "chevron"}).html('<img src="images/chevron.png">').appendTo($li);
					if(options[i].menu){
						$a.menu(options[i].menu, {popup: true});
					}
				}
			}
		},

		/**
		 * Проверка нажатой кнопки мыши
		 * @param BtnNo Номер кнопки: 1 - левая, 2 - средняя, 3 - правая
		 * @param event Событие (необязательно)
		 */
		CheckButton: function( BtnNo, event ){
			var e = event || window.event || window.Event;
			return (e.which == BtnNo);
		},

		/**
		 * Устанавливает URL в браузере
		 * @param Url
		 */
		SetLoc: function( Url ){
			try{
				history.pushState({}, '', '/' + Url);
				return;
			}catch(e){}
			var pc = Url.split('?', 2);
			location.hash = '#' + pc[1];
		},

		/**
		 * Загрузка страницы Админ-панели
		 * @param url Адрес.
		 * @param event Объект события.
		 * @param message Сообщение на экране загрузки.
		 * @param solveBubble Разрешить всплывание события.
		 */
		LoadPage: function( url, event, message, solveBubble ){
			if(event != undefined && !this.CheckButton(1, event)){ // Только левая кнопка мыши
				return false;
			}
			if(this.Ajax){
				slf = this;
				slf.ShowSplashScreen(message);
				$.ajax({
					type: "GET",
					url: url,
					dataType: "json",
					success: function(data){
						slf.SetPageData(data);
					}
				});
			}else{
				document.location = url;
			}
			if(event != undefined && !solveBubble){
				event.cancelBubble = true;
				event.stopPropagation();
			}
			return true;
		},

		/**
		 * Загрузка страницы POST запросом
		 * @param url
		 * @param postData
		 * @param message
		 */
		LoadPagePost: function( url, postData, message ){
			if(postData == undefined){
				postData = {};
			}
			slf = this;
			slf.ShowSplashScreen(message);
			$.ajax({
				type: "POST",
				url: url,
				dataType: "json",
				data: postData,
				success: function(data){
					slf.SetPageData(data);
				}
			});
			return true;
		},

		SetPageData: function( data ){
			slf = this;
			// Загружаем СSS
			ajaxcssjs.loadCSS(data.css, function(){
				// Загружаем JS
				ajaxcssjs.loadJS(data.js, function(){
					if(data.show_sidebar){
						$('#sidebar').html(data.sidebar);
						slf.SideBarShow();
					}else{
						slf.SideBarHide();
					}
					$('#main-content').html(data.content);
					if(data.errors != ''){
						$('#errors').html(data.errors).show();
					}else{
						$('#errors').html('').hide();
					}
					$("#info").html(data.info);
					eval(data.js_inline);
					document.getElementsByTagName('title')[0].innerHTML = data.title.replace('<','&lt;').replace('>','&gt;').replace(' & ',' &amp; ');
					slf.SetLoc(data.uri);
					slf.LiveUpdate();
					slf.HideSplashScreen();
				});
			});
		},

		/**
		 * Переход по внешней ссылке
		 * @param Url
		 * @param Blank
		 * @param event
		 */
		Leave: function( Url, Blank, event ){
			if(!Blank){
				location = Url;
			}else{
				window.open(Url);
			}
			return false;
		},

		SideBarHide: function(){
			$("#sidebar").hide();
			$("#main").removeClass("main").addClass("main_no_blocks");
			$("#footer").removeClass("footer").addClass("footer_no_blocks");
		},

		SideBarShow: function(){
			$("#sidebar").show();
			$("#main").removeClass("main_no_blocks").addClass("main");
			$("#footer").removeClass("footer_no_blocks").addClass("footer");
		},

		/**
		 * Показать Splash Screen при Ajax запросе
		 */
		ShowSplashScreen: function( Message ){
			if(this.splashShows == 0){
				$('div#wrapper').fadeTo(0, 0.5);
				$('div#ajaxsplashscreen').show();
			}
			this.splashShows++;
			this.splashShowsMax++;
			this.SetSplashScreenMessage(Message);
		},

		/**
		 * Скрыть Splash Screen
		 */
		HideSplashScreen: function(){
			if(this.splashShows == 1){
				$('div#wrapper').fadeTo(0, 1);
				$('div#ajaxsplashscreen').hide();
			}
			this.splashShows--;
			if(this.splashShows <= 0){
				this.splashShows = 0;
				this.splashShowsMax = 0;
			}
		},

		/**
		 * Обновить сообщение на SplashScreen
		 */
		SetSplashScreenMessage: function( NewMessage ){
			if(NewMessage == undefined || NewMessage == ''){
				$("#ajaxsplashscreen_message").hide();
			}else{
				$("#ajaxsplashscreen_message").show().text(NewMessage);
			}
		},

		/**
		 * Запуск таймера обработки селекторов
		 */
		_liveRun: function(){
			var self = this;
			this._liveStop();
			this.liveTimer = setInterval(function(){
				self.LiveUpdate();
			}, 200);
		},

		/**
		 * Остановка таймера обработки селекторов
		 */
		_liveStop: function(){
			if(this.liveTimer) clearInterval(this.liveTimer);
		},

		LiveUpdate: function(){
			var length = this.liveWatch.length, w;
			while( length-- ){
				w = this.liveWatch[length];
				var objs = $(w.selector), nobjs = objs.not(w.elms);
				nobjs.each(function(){
					w.fn.apply(this);
				});
			}
		},

		/**
		 * Вновь созданные элементы удовлетворяющие условию селектора будут обработаны заданной функцией
		 * @param selector Селектор для выборки элементов
		 * @param fn Функция обработчик
		 */
		Live: function(selector, fn){
			elms = $(selector);
			this.liveWatch.push({
				selector: selector,
				fn: fn,
				elms: elms
			});
			elms.each(function(){
						fn.apply(this);
					});
		},

		/**
		 * Функции быстрых кнопок
		 */
		Buttons: {

			/**
			 * Кнопка с подтверждением действия
			 * @param ConfirmMsg
			 * @param Object
			 */
			Confirm: function( ConfirmMsg, Url, Object, event, ajax ){
				if(confirm(ConfirmMsg)){
					if(ajax){
						Admin.LoadPage(Url, event);
					}else{
						return true;
					}
				}
				return false;
			},

			/**
			 * Кнопка смены какого-либо статуса объекта
			 * @param EnabledTitle
			 * @param DisabledTitle
			 * @param EnabledImage
			 * @param DisabledImage
			 * @param AjaxQueryUrl
			 * @param Object
			 */
			Status: function( EnabledTitle, DisabledTitle, EnabledImage, DisabledImage, ShowText, ShowImage, AjaxQueryUrl, LinkObject ){
				var src, title;
				var status = $(LinkObject).attr('status') == '1';
				if(ShowImage){
					var img = $(LinkObject).find("img:first").get(0);
				}
				if(ShowText){
					var cap = $(LinkObject).find("span.status_button_title").get(0);
				}

				if(status){
					src = DisabledImage;
					title = DisabledTitle;
				}else{
					src = EnabledImage;
					title = EnabledTitle;
				}
				Admin.ShowSplashScreen();
				$.ajax({
					url: AjaxQueryUrl,
					dataType: "text",
					success: function(){
						if(img){
							$(img).attr("src", src);
							$(img).attr("title", title);
						}
						if(cap){
							$(cap).html(title);
						}
						$(LinkObject).attr('status', (status ? '0' : '1'));
						Admin.HideSplashScreen();
					},
					cache: false
				});
				return false;
			},

			Ajax: function( AjaxUrl, Start, Success, Method, Params, Confirm, link ){
				if(Confirm == '' || confirm(Confirm)){
					Admin.ShowSplashScreen();
					Start(link);
					$.ajax({
						type: Method,
						url: AjaxUrl,
						data: Params,
						success: function(data, textStatus, jqXHR){
							Success.call(this, data, textStatus, jqXHR);
							Admin.HideSplashScreen();
						},
						cache: false
					});
				}

			}
		},

		end: {}
	};

	window.Admin = new AdminFn('admin.php', true); // FIXME: admin.php, ajax ?
	window.Admin._liveRun();

	// Хлебные крошки :)
	$.fn.admin_breadcrumbs = function( adminData, AjaxData ){
		var $breadcrumbs;

		var settings = $.extend({}, options, default_options);
		$breadcrumbs = BreadCrumbs(this, menuData, popup);

		return this;

	}

})(window, jQuery);

function MailToMenu(){
	window.open(
		'index.php?name=plugins&p=mail',
		'Mail',
		'resizable=yes,scrollbars=yes,menubar=no,status=no,location=no,width=700,height=580,screenX=300,screenY=50'
	);
	return false;
}
