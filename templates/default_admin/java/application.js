
// ���������� ��� ���-���������� �����-������

(function(window, $){

	var document = window.document;

	/**
	 * @class AdminFn
	 * @param AdminFile ������������� ��� ����� �����-������
	 * @param Ajax ������������ ajax ��� �������� �������
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

		/**
		 * �������� ������� ������ ����
		 * @param BtnNo ����� ������: 1 - �����, 2 - �������, 3 - ������
		 * @param event ������� (�������������)
		 */
		CheckButton: function( BtnNo, event ){
			var e = event || window.event || window.Event;
			return (e.which == BtnNo);
		},

		/**
		 * �������� �������� �����-������
		 * @param Url
		 * @param event
		 */
		LoadPage: function( Url, event ){
			if(this.CheckButton(1, event)){ // ������ ����� ������ ����
				document.location = Url;
				return true;
			}else{
				return false;
			}
		},

		/**
		 * ������� �� ������� ������
		 * @param Url
		 * @param Blank
		 * @param event
		 */
		Leave: function( Url, Blank, event ){
			if(Blank){
				window.open(Url);
			}else{
				location = Url;
			}
			return false;
		},

		/**
		 * �������� Splash Screen ��� Ajax �������
		 */
		ShowSplashScreen: function(){
			if(this.splashShows == 0){
				$('div#wrapper').fadeTo(0, 0.5);
				$('div#ajaxsplashscreen').show();
			}
			this.splashShows++;
			this.splashShowsMax++;
			$("#ajaxsplashscreen_progress").text((this.splashShowsMax - this.splashShows + 1)+'/'+this.splashShowsMax);
		},

		/**
		 * ������ Splash Screen
		 */
		HideSplashScreen: function(){
			if(this.splashShows == 1){
				$('div#wrapper').fadeTo(0, 1);
				$('div#ajaxsplashscreen').hide();
			}
			this.splashShows--;
			$("#ajaxsplashscreen_progress").text((this.splashShowsMax - this.splashShows + 1)+'/'+this.splashShowsMax);
			if(this.splashShows <= 0){
				this.splashShows = 0;
				this.splashShowsMax = 0;
			}
		},

		/**
		 * ������ ������� ��������� ����������
		 */
		_liveRun: function(){
			var self = this;
			this._liveStop();
			this.liveTimer = setInterval(function(){
				var length = self.liveWatch.length,
						w;
				while( length-- ){
					w = self.liveWatch[length];
					var objs = $(w.selector),
							nobjs = objs.not(w.elms);
					nobjs.each(function(){
						w.fn.apply(this);
					});
				}
			}, 200);
		},

		/**
		 * ��������� ������� ��������� ����������
		 */
		_liveStop: function(){
			if(this.liveTimer) clearInterval(this.liveTimer);
		},

		/**
		 * ����� ��������� �������� ��������������� ������� ��������� ����� ���������� �������� ��������
		 * @param selector �������� ��� ������� ���������
		 * @param fn ������� ����������
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
		 * ������� ������� ������
		 */
		Buttons: {

			/**
			 * ������ � �������������� ��������
			 * @param Confirm
			 * @param Object
			 */
			Confirm: function( Confirm, Object ){
				return confirm(Confirm);
			},

			/**
			 * ������ ����� ������-���� ������� �������
			 * @param EnabledTitle
			 * @param DisabledTitle
			 * @param EnabledImage
			 * @param DisabledImage
			 * @param AjaxQueryUrl
			 * @param Object
			 */
			Status: function( EnabledTitle, DisabledTitle, EnabledImage, DisabledImage, AjaxQueryUrl, Object ){
				var img = $(Object).children("img:first").get(0), src, title;
				if($(img).attr("src") == EnabledImage){
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
						$(img).attr("src", src);
						$(img).attr("title", title);
						Admin.HideSplashScreen();
					},
					cache: false
				});
				return false;
			},

			Ajax: function( AjaxUrl, Start, Success, Method, Params, Confirm, link ){
				if(Confirm != '' && confirm(Confirm)){
					Start(link);
					$.ajax({
						type: Method,
						url: AjaxUrl,
						data: Params,
						success: Success,
						cache: false
					});
				}

			}
		},

		end: {}
	};

	window.Admin = new AdminFn('admin.php', false); // FIXME: admin.php, ajax ?
	window.Admin._liveRun();

})(window, jQuery);

function MailToMenu(){
	window.open(
		'index.php?name=plugins&p=mail',
		'Mail',
		'resizable=yes,scrollbars=yes,menubar=no,status=no,location=no,width=700,height=580,screenX=300,screenY=50'
	);
	return false;
}