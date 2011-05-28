/*
 * LinkorCMS JQuery Table 1.0
 * Компонент Ajax таблицы
 *
 * Copyright 2011, Александр Галицкий
 * Email: linkorcms@yandex.ru
 * Site: http://linkorcms.ru/
 *
 * Зависимости:
 *
 */

(function( $, undefined ){

	$.widget("ui.lTable", {
		options: {
			columns: {}, // Описание колонок
			rows: {}, // Данные таблицы

			// Адрес страницы для обновления данных
			// таблицы (POST: page, itemsonpage, sortby)
			listingUrl: ""
		},

		default_column_options: {
			id: "0", // Уникальный идентификатор для доступа
			title: "Column Title", // Заголовок
			sortable: false, // Разрешить сортировку по этому столбцу
			sorted: false, // Приходящие данные отсортированы по данной колонке
			dataType: "str" // Тип данных (int, str, blob)
		},

		default_row_options: {
			id: "0", // Идентификатор строки
			data: [] // Данные ячеек
		},

		table: null, // Ссылка на таблицу
		thead: null,
		tbody: null,
		tfoot: null,

		_create: function(){
			var o = this.options;

			// Генерируем таблицу
			this.table = $('<table class="ui-lTable"></table>').appendTo(this.element);
			this.thead = $('<thead class="ui-lTable-thead"></thead>').appendTo(this.table);
			this.tfoot = $('<tfoot class="ui-lTable-tfoot"></tfoot>').appendTo(this.table);
			this.tbody = $('<tbody class="ui-lTable-tbody"></tbody>').appendTo(this.table);

			//Генерируем шапку
			var header = $('<tr>').appendTo(this.thead);
			for(var i = 0; i < o.columns.length; i++){
				var col = $.extend({}, this.default_column_options, o.columns[i]);
				$('<th id="ui-lTable-column-'+col.id+'" class="ui-lTable-column">'+col.title+'</th>').appendTo(header);
			}

			// Заполняем
			this._setData(o.rows);
		},

		/**
		 * Очищает таблицу
		 */
		_clear: function(){
			this.tbody.children().remove();
		},

		/**
		 * Генерация контента таблицы
		 * @param tableData Двухмерный массив или объект
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
		 * Загрузка обновленных данных с сервера
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