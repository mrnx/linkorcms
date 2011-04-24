

// Вспомогательные плагны jQuery.

(function($) {

	/**
	 * Разбирает файл стилей на отдельные правила
	 * @param cssdata Содержимое css файла
	 * @author Alexander Galitsky
	 */
	$.cssParse = function( cssdata ){
		var reg = new RegExp('([A-Za-z_\\-\\.#:\\*0-9\\[\\],= ]+[\\s]*){[\\s]*(([A-Za-z0-9-_]+)[\\s]*:[\\s]*([A-Za-z0-9#-_,\\\\/"\'\\.\\(\\)\\s]+);[\\s]*)*[\\s]*}', 'gm');
		var result = [];
		while(true){
			reg.lastIndex;
			var arr = reg.exec(cssdata);
			if(arr == null){
				break;
			}
			result.push(arr);
		}
		return result;
	}


	/**
	 * Загружает css файлы с помощью Ajax и применяет правила к странице
	 * @param filenames Имя файла или массив имен
	 * @param onsuccess Событие по заверешению загрузки и применения стилей
	 * @author Alexander Galitsky
	 */

	$.cssLoad = function( filenames, onsuccess ){
		if(typeof filenames != 'object'){
			filenames = [filenames]; // преобразуем в массив
		}
		var fc = filenames.length;
		var processed = 0;
		for(var i=0; i<fc; i++){
			var file = filenames[i];
			if($("style#"+file).length == 0){ // Проверяем, не был ли загружен этот файл раньше
				$.ajax({
					url: file,
					success: function(data){
						var cssrules = $.cssParse(data);
						$('<style>').attr('id', file).appendTo('head');
						var styleSheet = document.styleSheets[document.styleSheets.length - 1];
						for(var i=0; i<cssrules.length; i++){
							styleSheet.insertRule(cssrules[i][0], 0);
						}
						processed++;
						if(processed == fc){
							if(onsuccess != undefined){
								onsuccess.call();
							}
						}
					}
				});
			}

		}
	};


	/**
	 * Работа со всплывающими элементами (вложенное меню, подсказки)
	 * @param options
	 * @author Alexander Galitsky
	 */

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


	/**
	 * jQuery.timers - Timer abstractions for jQuery
	 * Written by Blair Mitchelmore (blair DOT mitchelmore AT gmail DOT com)
	 * Licensed under the WTFPL (http://sam.zoy.org/wtfpl/).
	 * Date: 2009/10/16
	 *
	 * @author Blair Mitchelmore
	 * @version 1.2
	 *
	 */

	$.fn.extend({
		everyTime: function(interval, label, fn, times) {
			return this.each(function() {
				$.timer.add(this, interval, label, fn, times);
			});
		},
		oneTime: function(interval, label, fn) {
			return this.each(function() {
				$.timer.add(this, interval, label, fn, 1);
			});
		},
		stopTime: function(label, fn) {
			return this.each(function() {
				$.timer.remove(this, label, fn);
			});
		}
	});

	$.extend({
		timer: {
			global: [],
			guid: 1,
			dataKey: "jQuery.timer",
			regex: /^([0-9]+(?:\.[0-9]*)?)\s*(.*s)?$/,
			powers: {
				// Yeah this is major overkill...
				'ms': 1,
				'cs': 10,
				'ds': 100,
				's': 1000,
				'das': 10000,
				'hs': 100000,
				'ks': 1000000
			},
			timeParse: function(value) {
				if (value == undefined || value == null)
					return null;
				var result = this.regex.exec($.trim(value.toString()));
				if (result[2]) {
					var num = parseFloat(result[1]);
					var mult = this.powers[result[2]] || 1;
					return num * mult;
				} else {
					return value;
				}
			},
			add: function(element, interval, label, fn, times) {
				var counter = 0;

				if ($.isFunction(label)) {
					if (!times)
						times = fn;
					fn = label;
					label = interval;
				}

				interval = $.timer.timeParse(interval);

				if (typeof interval != 'number' || isNaN(interval) || interval < 0)
					return;

				if (typeof times != 'number' || isNaN(times) || times < 0)
					times = 0;

				times = times || 0;

				var timers = $.data(element, this.dataKey) || $.data(element, this.dataKey, {});

				if (!timers[label])
					timers[label] = {};

				fn.timerID = fn.timerID || this.guid++;

				var handler = function() {
					if ((++counter > times && times !== 0) || fn.call(element, counter) === false)
						$.timer.remove(element, label, fn);
				};

				handler.timerID = fn.timerID;

				if (!timers[label][fn.timerID])
					timers[label][fn.timerID] = window.setInterval(handler,interval);

				this.global.push( element );

			},
			remove: function(element, label, fn) {
				var timers = jQuery.data(element, this.dataKey), ret;

				if ( timers ) {

					if (!label) {
						for ( label in timers )
							this.remove(element, label, fn);
					} else if ( timers[label] ) {
						if ( fn ) {
							if ( fn.timerID ) {
								window.clearInterval(timers[label][fn.timerID]);
								delete timers[label][fn.timerID];
							}
						} else {
							for ( var fn in timers[label] ) {
								window.clearInterval(timers[label][fn]);
								delete timers[label][fn];
							}
						}

						for ( ret in timers[label] ) break;
						if ( !ret ) {
							ret = null;
							delete timers[label];
						}
					}

					for ( ret in timers ) break;
					if ( !ret )
						jQuery.removeData(element, this.dataKey);
				}
			}
		}
	});

	$(window).bind("unload", function() {
		$.each($.timer.global, function(index, item) {
			$.timer.remove(item);
		});
	});


	/*
	 * jQuery Color Animations
	 * Copyright 2007 John Resig
	 * Released under the MIT and GPL licenses.
	 */

	// We override the animation for all of these color styles
	jQuery.each(['backgroundColor', 'borderBottomColor', 'borderLeftColor', 'borderRightColor', 'borderTopColor', 'color', 'outlineColor'], function(i,attr){
		jQuery.fx.step[attr] = function(fx){
			if ( fx.state == 0 ) {
				fx.start = getColor( fx.elem, attr );
				fx.end = getRGB( fx.end );
			}

			fx.elem.style[attr] = "rgb(" + [
				Math.max(Math.min( parseInt((fx.pos * (fx.end[0] - fx.start[0])) + fx.start[0]), 255), 0),
				Math.max(Math.min( parseInt((fx.pos * (fx.end[1] - fx.start[1])) + fx.start[1]), 255), 0),
				Math.max(Math.min( parseInt((fx.pos * (fx.end[2] - fx.start[2])) + fx.start[2]), 255), 0)
			].join(",") + ")";
		}
	});

	// Color Conversion functions from highlightFade
	// By Blair Mitchelmore
	// http://jquery.offput.ca/highlightFade/

	// Parse strings looking for color tuples [255,255,255]
	function getRGB(color) {
		var result;

		// Check if we're already dealing with an array of colors
		if ( color && color.constructor == Array && color.length == 3 )
			return color;

		// Look for rgb(num,num,num)
		if (result = /rgb\(\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})\s*\)/.exec(color))
			return [parseInt(result[1]), parseInt(result[2]), parseInt(result[3])];

		// Look for rgb(num%,num%,num%)
		if (result = /rgb\(\s*([0-9]+(?:\.[0-9]+)?)\%\s*,\s*([0-9]+(?:\.[0-9]+)?)\%\s*,\s*([0-9]+(?:\.[0-9]+)?)\%\s*\)/.exec(color))
			return [parseFloat(result[1])*2.55, parseFloat(result[2])*2.55, parseFloat(result[3])*2.55];

		// Look for #a0b1c2
		if (result = /#([a-fA-F0-9]{2})([a-fA-F0-9]{2})([a-fA-F0-9]{2})/.exec(color))
			return [parseInt(result[1],16), parseInt(result[2],16), parseInt(result[3],16)];

		// Look for #fff
		if (result = /#([a-fA-F0-9])([a-fA-F0-9])([a-fA-F0-9])/.exec(color))
			return [parseInt(result[1]+result[1],16), parseInt(result[2]+result[2],16), parseInt(result[3]+result[3],16)];

		// Otherwise, we're most likely dealing with a named color
		return colors[jQuery.trim(color).toLowerCase()];
	}

	function getColor(elem, attr) {
		var color;

		do {
			color = jQuery.curCSS(elem, attr);

			// Keep going until we find an element that has color, or we hit the body
			if ( color != '' && color != 'transparent' || jQuery.nodeName(elem, "body") )
				break;

			attr = "backgroundColor";
		} while ( elem = elem.parentNode );

		return getRGB(color);
	};

	// Some named colors to work with
	// From Interface by Stefan Petre
	// http://interface.eyecon.ro/

	var colors = {
		aqua:[0,255,255],
		azure:[240,255,255],
		beige:[245,245,220],
		black:[0,0,0],
		blue:[0,0,255],
		brown:[165,42,42],
		cyan:[0,255,255],
		darkblue:[0,0,139],
		darkcyan:[0,139,139],
		darkgrey:[169,169,169],
		darkgreen:[0,100,0],
		darkkhaki:[189,183,107],
		darkmagenta:[139,0,139],
		darkolivegreen:[85,107,47],
		darkorange:[255,140,0],
		darkorchid:[153,50,204],
		darkred:[139,0,0],
		darksalmon:[233,150,122],
		darkviolet:[148,0,211],
		fuchsia:[255,0,255],
		gold:[255,215,0],
		green:[0,128,0],
		indigo:[75,0,130],
		khaki:[240,230,140],
		lightblue:[173,216,230],
		lightcyan:[224,255,255],
		lightgreen:[144,238,144],
		lightgrey:[211,211,211],
		lightpink:[255,182,193],
		lightyellow:[255,255,224],
		lime:[0,255,0],
		magenta:[255,0,255],
		maroon:[128,0,0],
		navy:[0,0,128],
		olive:[128,128,0],
		orange:[255,165,0],
		pink:[255,192,203],
		purple:[128,0,128],
		violet:[128,0,128],
		red:[255,0,0],
		silver:[192,192,192],
		white:[255,255,255],
		yellow:[255,255,0]
	};

})(jQuery);