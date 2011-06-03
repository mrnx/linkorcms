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

	$.widget("ui.table", {
		options: {
			columns: {}, // �������� �������
			rows: {}, // ������ �������

			// ����� �������� ��� ���������� ������
			// ������� (POST: page, itemsonpage, sortby, desc)
			listingUrl: "",
			onpage: 10, // ���-�� ��������� �� ��������
			page: 1, // ������� ��������
			total: 0 // ���������� ��������� �����
		},

		default_column_options: {
			id: "0", // ���������� ������������� ��� �������
			title: "Column Title", // ���������
			sortable: true, // ��������� ���������� �� ����� �������
			sorted: false, // ���������� ������ ������������� �� ������ �������
			desc: false,
			align: "left" // ������������ � ������� (left, right, center)
		},

		default_row_options: {
			id: "0", // ������������� ������
			data: [] // ������ �����
		},

		table: null, // ������ �� �������
		thead: null,
		tbody: null,
		tfoot: null,
		tnav: null,

		navStart: 1, // �������� ������������ ���������
		sortBy: null,
		sortDesc: false,

		_create: function(){
			var o = this.options,
					self = this;

			this.navStart = o.page - 4;
			if(this.navStart < 1) this.navStart = 1;

			// ���������� �������
			this.table = $('<table class="ui-table"></table>').appendTo(this.element);
			this.thead = $('<thead class="ui-table-thead"></thead>').appendTo(this.table);
			this.tfoot = $('<tfoot class="ui-table-tfoot"></tfoot>').appendTo(this.table);
			this.tbody = $('<tbody class="ui-table-tbody"></tbody>').appendTo(this.table);

			//���������� �����
			var header = $('<tr>').appendTo(this.thead);
			for(var i = 0; i < o.columns.length; i++){
				var col = this.options.columns[i] = $.extend({}, this.default_column_options, o.columns[i]);
				var $th = $('<th id="ui-table-column-'+col.id+'" class="ui-table-column"></th>').appendTo(header);
				$th.bind('selectstart', function(){ return false; });
				var $value = $('<div class="ui-table-column-value">'+col.title+'</div>').appendTo($th);
				if(col.sortable){
					if(col.sorted){
						this.sortBy = i;
						this.sortDesc = col.desc;
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

			// ������ �������
			var footer = $('<tr>').appendTo(this.tfoot);
			var $ftd = $('<td class="ui-table-footer" colspan="'+o.columns.length+'"></td>').appendTo(footer);

			// ������������ ���������
			var $nav = $('<div class="ui-table-footer-nav"></div>').appendTo($ftd);
			this.tnav = $('<span class="ui-table-footer-nav-items"></span>').appendTo($nav);
			this._rebuildNav();

			var $updb = $('<div class="ui-table-footer-panel"></div>').appendTo($ftd);
			this._button({
				html: '<img src="images/admin/refresh.png" alt="��������" />',
				title: "�������� ������ �������",
				click: function(){ self._updateData(); return false; }
			}).appendTo($updb);

			$('<div class="ui-table-footer-panel">���-�� �� ��������:&nbsp;' +
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
			$ftd.find("#rowsonpage").change(function(){
				o.onpage = parseInt(this.value);
				self._updateData();
				self._rebuildNav();
			});
			$('<div class="ui-table-footer-panel"><span>����� ��������: '+o.total+'</span></div>').appendTo($ftd);

			// ���������
			this._setData(o.rows);
		},

		_button: function(options){
			if(!options.click) options.click = function(){ return false; };
			if(!options.class) options.class = "button";
			if(!options.href) options.href = "#";
			return $('<a>', options);
		},

		_setPage: function( page ){
			if(page < 1 || page > Math.ceil(this.options.total / this.options.onpage)) return;
			this.options.page = page;
			this.navStart = page - 4;
			if(this.navStart < 1) this.navStart = 1;
			this._rebuildNav();
			this._updateData();
		},

		_getNavBounds: function(totalPages, page, start, stop){
			if(start == undefined){
				if(this.navStart > totalPages - 9) this.navStart = totalPages - 9;
				if(this.navStart < 1) this.navStart = 1;
				var start = this.navStart;
				var stop = this.navStart + 9;
				if(stop > totalPages) stop = totalPages;
			}
			var show = stop - start;
			if(show == 9 || (start == 1 && stop == totalPages)){
				return {start: start, stop: stop};
			}
			if(show < 9){
				start = start - 1;
				stop = stop + 1;
			}
			if(start < 1) start = 1;
			if(stop > totalPages) stop = totalPages;
			return this._getNavBounds(totalPages, page, start, stop);
		},

		_rebuildNav: function(){
			var self = this,
					o = this.options,
					totalPages = Math.ceil(o.total / o.onpage),
					page = o.page,
					bounds = this._getNavBounds(totalPages, page);
			this.tnav.children().remove();
			if(totalPages == 1) return;
			this._button({
				             html: '<img src="images/admin/back.png" alt="�����" /></a>',
				             title: "�����",
				             click: function(){self._setPage(o.page-1);return false;}
			             }).appendTo(this.tnav);
			if(totalPages > 9)
				this._button({html: "&nbsp;1...&nbsp;",mousedown: function(){self.navStart=self.navStart-9;self._rebuildNav();}}).appendTo(this.tnav);
			for(var i=bounds.start; i<=bounds.stop; i++){
				if(i == page){
					this._button({html: '<u>'+i+'</u>', disabled: "disabled"}).appendTo(this.tnav);
				}else{
					this._button({text: i, click: (function(p){return function(){self._setPage(p); return false;}})(i) }).appendTo(this.tnav);
				}
			}
			if(totalPages > 9)
				this._button({html: "..."+totalPages, mousedown: function(){self.navStart=self.navStart+9;self._rebuildNav();}}).appendTo(this.tnav);
			this._button({
				             html: '<img src="images/admin/next.png" alt="������" />',
				             title: "������",
				             click: function(){self._setPage(o.page+1);return false;}
			             }).appendTo(this.tnav);
			if(window.Admin.LiveUpdate){
				window.Admin.LiveUpdate();
			}
		},

		/**
		 * ������� �������
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
			this.sortBy = ColumnId;
			this.sortDesc = o.desc;
			this._updateData();
		},

		/**
		 * ��������� �������� �������
		 * @param tableData ���������� ������ ��� ������
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
		 * �������� ����������� ������ � �������
		 */
		_updateData: function(){
			var self = this,
			postdata = 'page='+this.options.page+'&onpage='+this.options.onpage;
			if(this.sortBy != null){
				postdata += '&sortby='+this.sortBy+'&desc='+(this.sortDesc ? '1' : '0');
			}
			if(self.options.listingUrl == '') return;
			if(window.Admin.ShowSplashScreen) window.Admin.ShowSplashScreen();
			$.ajax({
				type: "POST",
				url: self.options.listingUrl,
				dataType: "json",
				data: postdata,
				cache: false,
				success: function(data){
					self._setData(data);
					if(window.Admin.ShowSplashScreen) window.Admin.HideSplashScreen();
					if(window.Admin.LiveUpdate) window.Admin.LiveUpdate();
				}
			});
		}
	});

})(jQuery);