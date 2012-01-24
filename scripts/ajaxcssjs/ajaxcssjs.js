
if(!Array.indexOf){
	Array.prototype.indexOf = function(obj, start){
		for(var i = (start || 0); i < this.length; i++){
			if(this[i] == obj){
				return i;
			}
		}
		return -1;
	}
}

var ajaxcssjs = {};
ajaxcssjs = {

	loaded_files: [],

	/**
	 * Разбирает файл стилей на отдельные правила
	 * @param cssdata Содержимое css файла
	 * @author Alexander Galitsky
	 */
	parseCSS: function(cssdata){
		var reg = new RegExp('([A-Za-z_\\-\\.#:\\*0-9\\[\\],=\\(\\) ]+[\\s]*){([A-Za-z0-9#-_,\\\\/"\'\\.\\(\\)\\s:;]+)*}', 'gm');
		var result = [];
		while(true){
			reg.lastIndex;
			var arr = reg.exec(cssdata);
			if (arr == null) {
				break;
			}
			result.push(arr);
		}
		return result;
	},

	cssLoaded: function(file){
		if(ajaxcssjs.loaded_files.indexOf(file) == -1){
			var result = false;
			$("link").each(function(){
				if($(this).attr('href') == file){
					result = true;
				}
			});
			return result;
		}else{
			return true;
		}
	},

	jsLoaded: function(file){
		if(ajaxcssjs.loaded_files.indexOf(file) == -1){
			var result = false;
			$("script").each(function(){
				if($(this).attr('src') == file){
					result = true;
				}
			});
			return result;
		}else{
			return true;
		}
	},


	/**
	 * Загружает css файлы с помощью Ajax и применяет правила к странице
	 * @param filenames Имя файла или массив имен
	 * @param onsuccess Событие по заверешению загрузки и применения стилей
	 * @author Alexander Galitsky
	 */
	loadCSS: function(filenames, onsuccess){
		if (typeof filenames != 'object'){
			filenames = [filenames]; // преобразуем в массив
		}
		var fc = filenames.length;
		if(fc == 0){
			if (onsuccess != undefined){
				onsuccess.call();
			}
			return;
		}
		var i = 0;
		var includecss = function(){
			var file = filenames[i];
			i++;
			if(!ajaxcssjs.cssLoaded(file)){ // Проверяем, не был ли загружен этот файл раньше
				$.ajax({
					url: file,
					success: (function(file, i){
						return function(data){
							var cssrules = ajaxcssjs.parseCSS(data);
							var baseUrl = file.replace(/\\/g, '/').replace(/[^\/]*\/?$/, '');
							$('<style>').attr('type', 'text/css').appendTo('head');
							var styleSheet = document.styleSheets[document.styleSheets.length - 1];
							for (var j = 0; j < cssrules.length; j++){
								var selector = cssrules[j][1];
								var cssrule = cssrules[j][2];
								if(cssrule != undefined){
									if(cssrule.toLowerCase().indexOf('url') != -1){
										cssrule = cssrule.replace(/([\("']+)((?!http)[^\("']+\.(gif|jpg|jpeg|png))([\)"']+)/g, "$1"+baseUrl+"$2$4");
									}
									if(styleSheet.insertRule){
										styleSheet.insertRule(selector+'{'+ cssrule+'}', styleSheet.cssRules.length);
									}else { /* IE */
										styleSheet.addRule(selector, cssrule, -1);
									}
								}
							}
							ajaxcssjs.loaded_files.push(file);
							if(i == fc) {
								if (onsuccess != undefined){
									onsuccess.call();
								}
							}else{
								includecss();
							}
						}
					})(file, i)
				});
			}else{
				if(i == fc){
					if(onsuccess != undefined){
						onsuccess.call();
					}
				}else{
					includecss();
				}
			}
		}
		includecss();
	},

	loadJS: function(filenames, onsuccess){
		if (typeof filenames != 'object'){
			filenames = [filenames]; // преобразуем в массив
		}
		var fc = filenames.length;
		if(fc == 0){
			if (onsuccess != undefined){
				onsuccess.call();
			}
			return;
		}
		var i = 0;
		var includejs = function(){
			var file = filenames[i];
			i++;
			if(!ajaxcssjs.jsLoaded(file)){ // Проверяем, не был ли загружен этот файл раньше
				$.getScript(file, (function(file, i){
					return function(){
						ajaxcssjs.loaded_files.push(file);
						if(i == fc){
							if(onsuccess != undefined){
								onsuccess.call();
							}
						}else{
							includejs();
						}
					}
				})(file, i));
			}else{
				if(i == fc){
					if(onsuccess != undefined){
						onsuccess.call();
					}
				}else{
					includejs();
				}
			}
		}
		includejs();
	}
};
