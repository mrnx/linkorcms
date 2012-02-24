
/**
 * ���������� ��� ��� ���������� ������� ��������
 */

(function(window, $, undefined){

	/**
	 * @class AdminFn
	 * @param AdminFile ������������� ��� ����� �����-������
	 * @param Ajax ������������ ajax ��� �������� �������
	 */
	window.FrontFn = function( AdminFile, Ajax ){
		this.liveWatch = [];
		this.liveTimer = null;
	}

	window.FrontFn.prototype = {
		/**
		 * ������ ������� ��������� ����������
		 */
		_liveRun: function(){
			var self = this;
			this._liveStop();
			this.liveTimer = setInterval(function(){
				self.LiveUpdate();
			}, 200);
		},

		/**
		 * ��������� ������� ��������� ����������
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
		}
	}

	window.Front = new FrontFn(); // FIXME: admin.php, ajax ?
	window.Front._liveRun();

})(window, jQuery);
