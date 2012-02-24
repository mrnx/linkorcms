
/**
 * Клиентский код веб приложения главной страницы
 */

(function(window, $, undefined){

	/**
	 * @class AdminFn
	 * @param AdminFile Относительное имя файла админ-панели
	 * @param Ajax Использовать ajax при загрузке страниц
	 */
	window.FrontFn = function( AdminFile, Ajax ){
		this.liveWatch = [];
		this.liveTimer = null;
	}

	window.FrontFn.prototype = {
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
		}
	}

	window.Front = new FrontFn(); // FIXME: admin.php, ajax ?
	window.Front._liveRun();

})(window, jQuery);
