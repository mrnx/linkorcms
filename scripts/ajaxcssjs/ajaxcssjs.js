
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
	 * ��������� ���� ������ �� ��������� �������
	 * @param cssdata ���������� css �����
	 * @author Alexander Galitsky
	 */
	parseCSS: function(cssdata){
		var reg = new RegExp('([A-Za-z_\\-\\.#:\\*0-9\\[\\],= ]+[\\s]*){[\\s]*(([A-Za-z0-9-_]+)[\\s]*:[\\s]*([A-Za-z0-9#-_,\\\\/"\'\\.\\(\\)\\s]+);[\\s]*)*[\\s]*}', 'gm');
		var result = [];
		while (true) {
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
	 * ��������� css ����� � ������� Ajax � ��������� ������� � ��������
	 * @param filenames ��� ����� ��� ������ ����
	 * @param onsuccess ������� �� ����������� �������� � ���������� ������
	 * @author Alexander Galitsky
	 */
	loadCSS: function(filenames, onsuccess){
		if (typeof filenames != 'object'){
			filenames = [filenames]; // ����������� � ������
		}
		var fc = filenames.length;
		if(fc == 0){
			if (onsuccess != undefined){
				onsuccess.call();
			}
			return;
		}
		var processed = 0;
		for (var i = 0; i < fc; i++){
			var file = filenames[i];
			if(!ajaxcssjs.cssLoaded(file)){ // ���������, �� ��� �� �������� ���� ���� ������
				$.ajax({
					url: file,
					success: (function(file){
						return function(data){
							var cssrules = ajaxcssjs.parseCSS(data);
							var baseUrl = file.replace(/\\/g, '/').replace(/[^\/]*\/?$/, '');
							$('<style>').attr('type', 'text/css').appendTo('head');
							var styleSheet = document.styleSheets[document.styleSheets.length - 1];
							for (var i = 0; i < cssrules.length; i++){
								var cssrule = cssrules[i][0];
								if(cssrules[i][2] != undefined && cssrules[i][2].toLowerCase().indexOf('url') != -1){
									cssrule = cssrule.replace(/([\("']+)((?!http)[^\("']+\.(gif|jpg|jpeg|png))([\)"']+)/g, "$1"+baseUrl+"$2$4");
								}
								styleSheet.insertRule(cssrule, 0);
							}
							processed++;
							ajaxcssjs.loaded_files.push(file);
							if(processed == fc) {
								if (onsuccess != undefined){
									onsuccess.call();
								}
							}
						}
					})(file)
				});
			}else{
				processed++;
				if(processed == fc){
					if (onsuccess != undefined){
						onsuccess.call();
					}
				}
			}
		}
	},

	loadJS: function(filenames, onsuccess){
		if (typeof filenames != 'object'){
			filenames = [filenames]; // ����������� � ������
		}
		var fc = filenames.length;
		if(fc == 0){
			if (onsuccess != undefined){
				onsuccess.call();
			}
			return;
		}
		var processed = 0;
		for(var i = 0; i < fc; i++){
			var file = filenames[i];
			if(!ajaxcssjs.jsLoaded(file)){ // ���������, �� ��� �� �������� ���� ���� ������
				$.getScript(file, (function(file){
					return function(){
						processed++;
						ajaxcssjs.loaded_files.push(file);
						if(processed == fc){
							if(onsuccess != undefined){
								onsuccess.call();
							}
						}
					}
				})(file));
			}else{
				processed++;
				if(processed == fc){
					if (onsuccess != undefined){
						onsuccess.call();
					}
				}
			}
		}
	}
};
