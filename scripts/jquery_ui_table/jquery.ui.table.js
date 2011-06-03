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

	$.widget("ui.table", {
		options: {
			columns: {}, // Описание колонок
			rows: {}, // Данные таблицы

			// Адрес страницы для обновления данных
			// таблицы (POST: page, itemsonpage, sortby, desc)
			listingUrl: "",
			onpage: 10, // Кол-во элементов на странице
			page: 1, // Текущая страница
			total: 0 // Количество элементов всего
		},

		default_column_options: {
			id: "0", // Уникальный идентификатор для доступа
			title: "Column Title", // Заголовок
			sortable: true, // Разрешить сортировку по этому столбцу
			sorted: false, // Приходящие данные отсортированы по данной колонке
			desc: false,
			align: "left" // Выравнивание в ячейках (left, right, center)
		},

		default_row_options: {
			id: "0", // Идентификатор строки
			data: [] // Данные ячеек
		},

		table: null, // Ссылка на таблицу
		thead: null,
		tbody: null,
		tfoot: null,
		tnav: null,

		navStart: 0, // смещение постраничной навигации
		totalPages: 0,

		_create: function(){
			var o = this.options,
					self = this;

			console.dir(o);

			this.navStart = 0;
			this.totalPages = Math.round(o.total / o.onpage);

			// Генерируем таблицу
			this.table = $('<table class="ui-table"></table>').appendTo(this.element);
			this.thead = $('<thead class="ui-table-thead"></thead>').appendTo(this.table);
			this.tfoot = $('<tfoot class="ui-table-tfoot"></tfoot>').appendTo(this.table);
			this.tbody = $('<tbody class="ui-table-tbody"></tbody>').appendTo(this.table);

			//Генерируем шапку
			var header = $('<tr>').appendTo(this.thead);
			for(var i = 0; i < o.columns.length; i++){
				var col = this.options.columns[i] = $.extend({}, this.default_column_options, o.columns[i]);
				var $th = $('<th id="ui-table-column-'+col.id+'" class="ui-table-column"></th>').appendTo(header);
				$th.bind('selectstart', function(){ return false; });
				var $value = $('<div class="ui-table-column-value">'+col.title+'</div>').appendTo($th);
				if(col.sortable){
					if(col.sorted){
						$th.addClass('ui-table-column-sortable-selected');
						var arrowClass = 'ui-table-column-arrow-'+(col.desc ? 'desc' : 'asc');
					}else{
						$th.addClass('ui-table-column-sortable');
						var arrowClass = 'ui-table-column-arrow';
					}
					var $arrow = $('<div id="ui-table-arrow" class="'+arrowClass+'"></div>').prependTo($value);
					$th.bind('click', (function(id){
							return function(){
								self._setSortedColumn(id);
							}
						})(i));
				}
			}

			// Подвал таблицы
			var footer = $('<tr>').appendTo(this.tfoot);
			var $ftd = $('<td class="ui-table-footer" colspan="'+o.columns.length+'"></td>').appendTo(footer);

			// Постраничная навигация
			var $nav = $('<div class="ui-table-footer-nav"></div>').appendTo($ftd);
			$('<a title="Назад" href="#" class="button"><img src="images/admin/back.png" alt="Назад" /></a>').appendTo($nav);
			this.tnav = $('<div class="ui-table-footer-nav-items"></div>').appendTo($nav);
			$('<a title="Вперед" href="#" class="button"><img src="images/admin/next.png" alt="Вперед" /></a>').appendTo($nav);
			this._rebuildNav();

			$('<div class="ui-table-footer-panel"><a title="Обновить данные таблицы" href="#" class="button"><img src="images/admin/refresh.png" alt="Обновить" /></a></div>').appendTo($ftd);
			$('<div class="ui-table-footer-panel">Кол-во на странице:&nbsp;' +
			  '<select id="rowsonpage">' +
			  '<option'+(o.onpage == 10 ? ' selected' : '')+'>10</option>' +
			  '<option'+(o.onpage == 20 ? ' selected' : '')+'>20</option>' +
			  '<option'+(o.onpage == 30 ? ' selected' : '')+'>30</option>' +
			  '<option'+(o.onpage == 40 ? ' selected' : '')+'>40</option>' +
			  '<option'+(o.onpage == 50 ? ' selected' : '')+'>50</option>' +
			  '<option'+(o.onpage == 75 ? ' selected' : '')+'>75</option>' +
			  '<option'+(o.onpage == 100 ? ' selected' : '')+'>100</option>' +
			  '</select>' +
			  '</div>').appendTo($ftd);
			$('<div class="ui-table-footer-panel"><span>Всего объектов: '+o.total+'</span></div>').appendTo($ftd);
			$('<div class="ui-table-footer-panel"><span>Всего страниц: '+o.total+'</span></div>').appendTo($ftd);

			// Заполняем
			this._setData(o.rows);
		},

		_rebuildNav: function(){
			for(var i=0; i < this.totalPages; i++){
				$('<a href="#" onclick="return false;" class="button">&nbsp;'+i+'&nbsp;</a>').appendTo(this.tnav);
			}
		},

		/**
		 * Очищает таблицу
		 */
		_clear: function(){
			this.tbody.children().remove();
		},

		_col: function(id){
			return this.options.columns[id];
		},

		_setSortedColumn: function( ColumnId ){
			var o = this.options.columns[ColumnId];
			if(!o.sortable) return;

			this.thead.find('th').removeClass().addClass('ui-table-column ui-table-column-sortable');
			this.thead.find('#ui-table-arrow').removeClass().addClass('ui-table-column-arrow');
			var $th = this.thead.find('#ui-table-column-'+ColumnId);

			if(o.sorted){
				o.desc = !o.desc;
			}else{
				o.desc = false;
			}

			for(var i = 0; i < this.options.columns.length; i++){
				this.options.columns[i].sorted = false;
			}
			o.sorted = true;

			$th.removeClass().addClass('ui-table-column ui-table-column-sortable-selected');
			var arrowClass = 'ui-table-column-arrow-'+(o.desc ? 'desc' : 'asc');

			$th.find('#ui-table-arrow').removeClass().addClass(arrowClass);
			this.options.columns[ColumnId] = o;
			this._updateData(1, 10, ColumnId, o.desc);
		},

		/**
		 * Генерация контента таблицы
		 * @param tableData Двухмерный массив или объект
		 */
		_setData: function( tableData ){
			this._clear();
			if(tableData.length == 0) return;
			for(var i = 0; i < tableData.length; i++){
				var ro = tableData[i];
				var row = $('<tr id="ui-table-row-'+ro.id+'" class="ui-table-row">').appendTo(this.tbody);
				row.hover(
					function(){
						$(this).addClass("ui-table-row-hover");
					},
					function(){
						$(this).removeClass("ui-table-row-hover");
					}
				);
				for(var j = 0; j < ro.data.length; j++){
					var $cell = $('<td id="ui-table-cell-'+ro.id+'" class="ui-table-cell">'+ro.data[j]+'</td>').appendTo(row);
					$cell.addClass('ui-table-align-'+this.options.columns[j].align);
				}
			}
			this.tbody.children(":even").addClass("ui-table-row-even");
		},

		/**
		 * Загрузка обновленных данных с сервера
		 */
		_updateData: function( page, itemsonpage, sortby, desc ){
			var self = this;
			if(self.options.listingUrl == '') return;
			if(window.Admin.ShowSplashScreen) window.Admin.ShowSplashScreen();
			$.ajax({
				type: "POST",
				url: self.options.listingUrl,
				dataType: "json",
				data: 'page='+page+'&itemsonpage='+itemsonpage+'&sortby='+sortby+'&desc='+(desc ? '1' : '0'),
				cache: false,
				success: function(data){
					self._setData(data);
					if(window.Admin.ShowSplashScreen) window.Admin.HideSplashScreen();
				}
			});
		}
	});

})(jQuery);