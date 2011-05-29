
/*
 * Работа со всплывающими элементами (вложенное меню, подсказки)
 * @param options
 * @author Alexander Galitsky
 */

(function($, undefined){

	$.fn.lPopUp = function(options){

		var default_options = {
			position: "right top",
			alignment: "left top",
			popupObject: "span:last-child",
			_classname: "lpopup-positionable",
			popupStyle: "hover", // hover | click | down
			delay: 0, // Автоматическая задержка перед показом всплывающего элемента
			offset: "0 0"
			//show: function(options){}, // Функция для показа элемента
			//hide: function(options)(), // Функция для скрытия элемента
		};

		options = $.extend({}, default_options, options);

		function SetPopUpPosition(obj){
			$(obj)
				.children(options.popupObject)
				.position({
					of: obj,
					my: options.alignment,
					at: options.position,
					offset: options.offset
				});
		}

		return this.each(function(){
			$(this).addClass(options._classname);
			var showevent = function(){
						if('show' in options){
							options.show.call(this, options);
						}else{
							$(this).children(options.popupObject).show();
						}
						var scroll = $(window).scrollTop();
						$(window).scrollTop(0);
						SetPopUpPosition(this);
						$(window).scrollTop(scroll);
					};
			var hideevent = function(){
						if('hide' in options){
							options.hide.call(this, options);
						}else{
							$(this).children(options.popupObject).hide();
						}
					};
			if(options.popupStyle == 'hover'){
				$(this).hover(showevent, hideevent);
			}else{
				if(options.popupStyle == 'click'){
					$(this).click(showevent);
				}else{
					$(this).mousedown(showevent);
				}

				$(this).mouseleave(hideevent);
			}
		});
	}

})(jQuery);