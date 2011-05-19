/*
 * LinkorCMS JQuery TreeView 1.0
 *
 * Copyright 2011, Александр Галицкий
 * Email: linkorcms@yandex.ru
 * Site: http://linkorcms.ru/
 *
 * Зависимости:
 *	 jquery.ui.nestedSortable.js
 *   jquery.ui.position.js
 *
 */

(function($){

	$.fn.lTreeView = function( options, tree, nestedSortableOtions  ){

		var $tree;

		var default_nestedSortableOptions = {
			forcePlaceholderSize: true,
			handle: '.item_icon img',
			items: 'li',
			opacity: .6,
			placeholder: 'placeholder',
			tolerance: 'intersect',
			toleranceElement: '> div',
			tabSize: 25,
			disableNesting: 'no-nest',
			errorClass: 'nest_error',
			update: Sort
		}

		var default_item_options = {
			id: '0', // Уникальный идентификатор объекта
			icon: 'scripts/jquery_treeview/theme/icon.png', // Имя файла иконки элемента
			title: 'NodeTitle', // Секция с заголовком элемента
			info: '', // Секция с информацией для всплывающей подсказки в формате HTML
			func: '', // Секция с функциональными кнопками и ссылками
			opened: false, // Статус open/close
			nonest: false, // Запретить ли в этот элемент кидать дочерние элементы
			isnode: false, // Есть ли дочерние элемены - нужно когда они не загружены
			child_url: '', // Адрес для подгрузки узлов
			childs: [] // Дочерние элементы в таком-же формате
		};

		var default_options = {
			moveHandler: '' // Адрес страницы обработчика перемещения элементов
		};

		if(arguments.length <= 2){
			nestedSortableOtions = {};
		}
		nestedSortableOtions = $.extend({}, default_nestedSortableOptions, nestedSortableOtions);
		options = $.extend({}, default_options, options);

		/**
		 * Показать кнопку информации
		 * @param item_id
		 */
		function ShowInfoButton( item_id ){
			$("#item_info_"+item_id).show().css('display', 'inline-block');
		}

		/**
		 * Скрыть кнопку информации
		 * @param item_id
		 */
		function HideInfoButton( item_id ){
			$("#item_info_"+item_id).hide();
		}

		/**
		 * Открытие - закрытие элемента содержащего дочерние элементы
		 * @param item_id Объект элемента списка или его атрибут id
		 * @param end_toggle Обработчик события при показе элемента (окончанию загрузки)
		 */
		function ToggleNode( item_id, end_toggle ){
			if(typeof item_id != 'object'){
				var $element = $('li#item_'+item_id);
			}else{
				var $element = $(item_id);
			}
			var opt = $element.data('options');
			var $node = $element.find("ol:first");
			var $bullet = $('#item_bullet_'+item_id);
			if('opened' in opt && opt.opened){ // скрыть
				$node.slideUp();
				opt.opened = false;
				// Меняем значёк на кнопке
				$bullet.removeClass('node_open');
				if(!$bullet.hasClass('node_close')){
					$bullet.addClass('node_close');
				}
			}else{ // показать
				if(!$node.length){
					if('childs' in opt && opt.childs.length > 0){
						GenerateList($element, opt.childs);
						if(end_toggle != undefined){
							end_toggle.call($element);
						}
					}else if('child_url' in opt && opt.child_url != ''){
						LoadList($element, opt.child_url, end_toggle);
					}
				}else{
					$node.slideDown();
					if(end_toggle != undefined){
						end_toggle.call($element);
					}
				}
				opt.opened = true;
				// Меняем значёк на кнопке
				$bullet.removeClass('node_close');
				if(!$bullet.hasClass('node_open')){
					$bullet.addClass('node_open');
				}
			}
		}

		/**
		 * Обновление статусов кнопок
		 */
		function UpdateBullets(){
			$tree.find('li').each(function(){
				var $obj = $(this);
				var opt = $obj.data('options');
				var $bullet = $obj.find('.node_button:first');
				var $child = $obj.find('ol:first');
				if(($child.length > 0 && $child.find('li').length > 0) || opt.isnode){ // есть дочерние элементы
					//opt.isnode = true;
					if($child.is(':visible')){ // Виден
						$bullet.removeClass('node_close');
						if(!$bullet.hasClass('node_open')){
							$bullet.addClass('node_open');
						}
						opt.opened = true;
					}else{ // Скрыт
						$bullet.removeClass('node_open');
						if(!$bullet.hasClass('node_close')){
							$bullet.addClass('node_close');
						}
						opt.opened = false;
					}
					// Обновляем событие
					$bullet.unbind();
					$bullet.bind('click', function(){
						ToggleNode(opt.id);
					});
				}else{
					if($child.length > 0){ // пустой список
						$child.remove();
					}
					$bullet.removeClass('node_close node_open').addClass('node_none');
					opt.isnode = false;
					opt.opened = true;
					// Удаляем события
					$bullet.unbind();
				}
			});
		}

		/**
		 * Обработчик события начала загрузки дочерних элементов
		 * @param $element
		 */
		function LoadingStart( $element ){
			return $("<ol>", {
				"class": 'treeview',
				html: '<li><img src="images/ajax-loader.gif" style="vertical-align: middle;" /></li>'
			}).appendTo($element);
		}

		/**
		 * Обработчик события окончания загрузки дочерних элементов
		 * @param $element
		 * @param $list
		 * @param $placeholder
		 */
		function LoadingEnd( $element, $list, $placeholder ){
			$placeholder.remove();
			$list.show();
		}

		/**
		 * Обработчик события перемещения элемента
		 * @param event
		 * @param ui
		 */
		function Sort(event, ui){
			var $item = $(ui.item); // Перемещаемый элемент
			var $target = $(ui.item).parents('li:first'); // Куда переместили

			var item_opt = $item.data('options');
			var target_opt = $target.data('options');

			// Посылаем POST запрос перемещения элементов
			if(options.moveHandler != ''){
				var index = $(ui.item).parent().children().index(ui.item);
				//console.log(index);
				$.ajax({
					type: "POST",
					url: options.moveHandler,
					data: 'item_id='+item_opt.id+'&target_id='+target_opt.id+'&item_new_position='+index,
					cache: false
					//,success: function(resp){ console.log(resp); }
				});
				// FIXME: При неудачном перемещении должно выводиться сообщение об ошибке
			}

			if(target_opt.isnode && !target_opt.opened){ // Вырезание и загрузка
				var $li = $item.detach();
				$target.children('ol').remove();
				ToggleNode($target, function(){
					$target.children('ol').append($li);
					UpdateBullets();
				});
			}else{
				UpdateBullets();
			}
		}

		/**
		 * Создание нового элемента списка с использованием пользовательских параметров
		 * @param opt
		 */
		function GenerateElement(opt){
			opt = $.extend({}, default_item_options, opt);

			// Элемент
			var element_options = {id: "item_"+opt.id};
			if(opt.nonest){
				$.extend(element_options, {"class": "no-nest"});
			}
			var $element = $('<li>', element_options);
			var $div_helper = $('<div>').appendTo($element); // Вспомогательный див в который отдельно будут помещены и элементы и кнопка узла

			// Контейнер
			var $div = $('<div>', {
				id: "item_div_"+opt.id,
				"class": "item",
				mouseenter: function(){
					ShowInfoButton(opt.id);
				},
				mouseleave : function(){
					HideInfoButton(opt.id);
				}
			}).appendTo($div_helper);

			// Кнопка закрытия и открытия элементов
			var bullet_options = {
				id: "item_bullet_"+opt.id
			};
			if('childs' in opt && opt.childs.length == 0 && !opt.isnode){
				$.extend(bullet_options, {"class": "node_button node_none"});
			}else{
				if(opt.opened){
					$.extend(bullet_options, {
						"class": "node_button node_open", click: function(){
							ToggleNode(opt.id);
						}
					});
				}else{
					$.extend(bullet_options, {
						"class": "node_button node_close", click: function(){
							ToggleNode(opt.id);
						}
					});
				}
			}
			$('<div>', bullet_options).prependTo($div_helper);

			// Иконка
			if('icon' in opt && opt.icon != ''){
				$('<div class="item_icon" id="item_icon_'+opt.id+'"><img src="'+opt.icon+'" title="Переместить" /></div>').appendTo($div);
			}

			//Заголовок
			$('<div class="item_title" id="item_title_'+opt.id+'">'+opt.title+'<a name="item_'+opt.id+'" /></div>').appendTo($div);

			// Всплывающая информация об элементе
			if('info' in opt && opt.info != ''){
				var $info = $('<div id="item_info_'+opt.id+'" class="item_info"><span class="tooltip">'+opt.info+'</span></div>').appendTo($div);
				$info.lPopUp({
					show: function(options){
						$(this).children(options.popupObject).fadeIn("fast");
					}
				});
			}

			// Функциональные ссылки
			if('func' in opt && opt.func != ''){
				$('<div class="item_func_bar" id="item_func_'+opt.id+'">'+opt.func+'</div>').appendTo($div);
			}

			// Дочерние элементы
			if('opened' in opt && opt.opened){
				if('childs' in opt && opt.childs.length > 0){
					GenerateList($element, opt.childs);
				}else if('child_url' in opt && opt.child_url != ''){
					LoadList($element, opt.child_url);
				}
			}
			$element.data('options', opt);
			return $element; // <li>
		}

		/**
		 * Загрузка и добавление удаленного списка узлов
		 * @param $parentElement Родительский элемент
		 * @param loadUrl Адрес, откуда загружать элементы (данные в формате json)
		 * @param endLoad Обработчик окончания загрузки
		 */
		function LoadList( $parentElement, loadUrl, endLoad ){
			var $placeholder = LoadingStart($parentElement);
			$.ajax({
				url: loadUrl,
				dataType: "json",
				success: function(data){
					LoadingEnd($parentElement, GenerateList($parentElement, data, false, true), $placeholder);
					if(endLoad != undefined){
						endLoad.call($parentElement);
					}
				}
			});
		}

		/**
		 * Генерация списка (OL)
		 * @param $parentElement Родительский элемент (контейнер)
		 * @param elements Данные для создания списка
		 * @param _toplevel Список верхнего уровня
		 * @param hidden Скрыть список после создания (например если элемент свернут)
		 */
		function GenerateList( $parentElement, elements, _toplevel, hidden ){
			if(arguments.length > 2 && _toplevel == true){
				var classname = "treeview toplevel";
			}else{
				var classname = "treeview";
			}
			if(arguments.length > 3){
				var hide_list = hidden;
			}else{
				var hide_list = false;
			}
			if($parentElement.data('options') && 'id' in $parentElement.data('options')){
				var id = $parentElement.data('options').id;
			}else{
				var id = '0';
			}
			var $ol = $("<ol>", {
				"class": classname,
				id: 'node_'+id
			}).appendTo($parentElement);
			for(var i=0; i<elements.length; i++){
				$ol.append(GenerateElement(elements[i]));
			}
			if(hide_list){
				$ol.hide();
			}
			return $ol;
		}

		var $tree = GenerateList(this, tree, true).nestedSortable(nestedSortableOtions);
		// Можем ссылаться на список с помощью $tree в коде плагина
		return this;
	};

})(jQuery);