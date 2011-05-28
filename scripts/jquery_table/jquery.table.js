/*
 * LinkorCMS JQuery Table 1.0
 * ��������� Ajax �������
 *
 * Copyright 2011, ��������� ��������
 * Email: linkorcms@yandex.ru
 * Site: http://linkorcms.ru/
 *
 * �����������:
 *
 */

(function( $, undefined ){

	$.widget("ui.lTable", {
		options: {
			columns: {}, // �������� �������
			rows: {}, // ������ �������

			// ����� �������� ��� ���������� ������
			// ������� (POST: page, itemsonpage, sortby)
			listingUrl: ""
		},

		default_column_options: {
			id: "0", // ���������� ������������� ��� �������
			title: "Column Title", // ���������
			sortable: false, // ��������� ���������� �� ����� �������
			sorted: false, // ���������� ������ ������������� �� ������ �������
			dataType: "str" // ��� ������ (int, str, blob)
		},

		default_row_options: {
			id: "0", // ������������� ������
			data: [] // ������ �����
		},

		table: null, // ������ �� �������
		thead: null,
		tbody: null,
		tfoot: null,

		_create: function(){
			var o = this.options;

			// ���������� �������
			this.table = $('<table class="ui-lTable"></table>').appendTo(this.element);
			this.thead = $('<thead class="ui-lTable-thead"></thead>').appendTo(this.table);
			this.tfoot = $('<tfoot class="ui-lTable-tfoot"></tfoot>').appendTo(this.table);
			this.tbody = $('<tbody class="ui-lTable-tbody"></tbody>').appendTo(this.table);

			//���������� �����
			var header = $('<tr>').appendTo(this.thead);
			for(var i = 0; i < o.columns.length; i++){
				var col = $.extend({}, this.default_column_options, o.columns[i]);
				$('<th id="ui-lTable-column-'+col.id+'" class="ui-lTable-column">'+col.title+'</th>').appendTo(header);
			}

			// ���������
			this._setData(o.rows);
		},

		/**
		 * ������� �������
		 */
		_clear: function(){
			this.tbody.children().remove();
		},

		/**
		 * ��������� �������� �������
		 * @param tableData ���������� ������ ��� ������
		 */
		_setData: function( tableData ){
			if(tableData.length == 0) return;
			for(var i = 0; i < tableData.length; i++){
				var ro = tableData[i];
				var row = $('<tr id="ui-lTable-row-'+ro.id+'" class="ui-lTable-row">').appendTo(this.tbody);
				row.hover(
					function(){
						$(this).addClass("ui-lTable-row-hover");
					},
					function(){
						$(this).removeClass("ui-lTable-row-hover");
					}
				);
				for(var j = 0; j < ro.data.length; j++){
					$('<th id="ui-lTable-cell-'+ro.id+'" class="ui-lTable-cell">'+ro.data[j]+'</th>').appendTo(row);
				}
				this.tbody.children(":even").addClass("ui-lTable-row-even");
			}
		},

		/**
		 * �������� ����������� ������ � �������
		 */
		_updateData: function(){
			var self = this;
			if(window.Admin.ShowSplashScreen) window.Admin.ShowSplashScreen();
			$.ajax({
				url: self.options.listingUrl,
				dataType: "json",
				success: function(data){
				self._updateData(data);
					if(window.Admin.HideSplashScreen) window.Admin.HideSplashScreen();
				}
			});
		}
	});

})(jQuery);