var ajaxcss = {};

ajaxcss = {

	/**
	 * ��������� ���� ������ �� ��������� �������
	 * @param cssdata ���������� css �����
	 * @author Alexander Galitsky
	 */
	parse: function(cssdata) {
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


	/**
	 * ��������� css ����� � ������� Ajax � ��������� ������� � ��������
	 * @param filenames ��� ����� ��� ������ ����
	 * @param onsuccess ������� �� ����������� �������� � ���������� ������
	 * @author Alexander Galitsky
	 */
	load: function(filenames, onsuccess) {
		if (typeof filenames != 'object') {
			filenames = [filenames]; // ����������� � ������
		}
		var fc = filenames.length;
		var processed = 0;
		for (var i = 0; i < fc; i++) {
			var file = filenames[i];
			if ($("style#" + file).length == 0) { // ���������, �� ��� �� �������� ���� ���� ������
				$.ajax({
					url: file,
					success: function(data) {
						var cssrules = this.cssParse(data);
						$('<style>').attr('id', file).appendTo('head');
						var styleSheet = document.styleSheets[document.styleSheets.length - 1];
						for (var i = 0; i < cssrules.length; i++) {
							styleSheet.insertRule(cssrules[i][0], 0);
						}
						processed++;
						if (processed == fc) {
							if (onsuccess != undefined) {
								onsuccess.call();
							}
						}
					}
				});
			}
		}
	}

};
