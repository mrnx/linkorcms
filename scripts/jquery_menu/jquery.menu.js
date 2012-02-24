
/**
 * Плагин меню
 * Автор: Александр Галицкий
 * Плагин разработан в рамках проекта LinkorCMS
 */

(function($){

	/**
	 *
	 * @param menuData Массив с описаниями элементов меню.
	 * @param options Дополнительные опции.
	 */
	$.fn.menu = function( menuData, options, popup ){

		var $topmenu; // Ссылка на верхний список UL или на элемент к которому привязано всплывающее меню
		var active = false; // Состояние, показывает что меню было активировано
		var timer_label = null;
		var prev_enter_object = null;
		var prev_item_target = null;
		var popupMode = false;
		var popupMenu = null;

		var default_menudata = {
			id: '0', // Уникальный идентификатор объекта
			title: 'Title', // Секция с заголовком элемента
			icon: 'scripts/lTreeView/theme/icon.png', // Имя файла иконки элемента
			type: 'admin', // admin, external, js, node, delimiter
			link: '',
			admin_link: '',
			js: '',
			blank: false,
			submenu: [] // Дочерние элементы в таком-же формате
		};

		var default_options = {
			popup: false, // Режим всплывающего меню, меню привязывается к выбранному элементу.
			theme: "default", // Тема оформления
			topItemActiveClass: "active", // Класс для активных пунктов верхнего меню
			topItemAtPosition: { // Позиция открытия подменю относительно верхнего меню
				at: "left bottom",
				my: "left top",
				offset: "-1 0"
			},
			topSubItemAtPosition: { // Позиция открытия подменю относительно вложенного пункта меню
				at: "right top",
				my: "left top",
				offset: "-4 -1"
			},
			popupItemActiveClass: "topmenu-active", // Класс для элемента на котором открыто всплывающее меню
			popupItemAtPosition: { // Позиция открытия меню относительно элемента
				at: "left bottom",
				my: "left top",
				offset: "-1 0"
			},
			effect: "slide", // no, fade, slide // Эффект показа меню
			effectSpeed: "fast", // fast, slow // Скорость анимации для fade и slide
			activateOnHover: false, // Активировать меню при наведении курсора
			deactivateOnTimer: true, // Деактивировать меню по таймеру когда курсор не на меню
			timerDelay: 1000 // Период таймера
		};

		function MenuEffect($item, show, nofx){
			var effect = settings.effect;
			if(nofx) effect = 'no';
			var speed = settings.effectSpeed;
			if(show){
				if(effect == 'no'){
					$item.show();
				}else if(effect == 'fade'){
					$item.fadeIn(speed);
				}else if(effect == 'slide'){
					$item.slideDown(speed);
				}
			}else{
				if(effect == 'no'){
					$item.hide();
				}else if(effect == 'fade'){
					$item.fadeOut(speed);
				}else if(effect == 'slide'){
					$item.slideUp(speed);
				}
			}
		}

		function GetUL(item){
			return $(item).children('ul');
		}

		function DeactivateMenu(){
			ClearMenuTimer();
			active = false;
			if(!popupMode){
				MenuEffect($topmenu.find(".l-topmenu-sub-"+settings.theme+":visible"), false, true);
				$topmenu.find("a.admin_menu_top_link").removeClass(settings.topItemActiveClass);
			}else{
				$($topmenu).removeClass(settings.popupItemActiveClass);
				MenuEffect(popupMenu.find(".l-topmenu-sub-"+settings.theme+":visible"), false, true);
				MenuEffect(popupMenu, false, true);
			}
			prev_item_target = null;
		}

		function ShowMenu( parentElement, Ul, sub ){
			var position = {};
			MenuEffect(Ul, true);
			if(sub){
				var position = settings.topSubItemAtPosition;
			}else{
				if(popupMode){
					var position = settings.popupItemAtPosition;
				}else{
					var position = settings.topItemAtPosition;
				}
			}
			Ul.position({
				of: parentElement,
				my: position.my,
				at: position.at,
				offset: position.offset
			});
		}

		function HideMenu( Ul, nofx ){
			$(Ul).children('li').each(function(i, e){
				var $ul = GetUL(e);
				if($ul.length > 0){
					MenuEffect($ul, false, nofx);
				}
			});
			MenuEffect(Ul, false, nofx);
		}

		function ShowSubMenu( parentElement, sub ){
			var $ul = GetUL(parentElement);
			if($ul.length > 0){
				ShowMenu(parentElement, $ul, sub)
			}
		}

		function HideSubMenu( parentElement, nofx ){
			var $ul = GetUL(parentElement);
			if($ul.length > 0){
				HideMenu($ul, nofx);
			}
		}

		function ClearMenuTimer(){
			if(!settings.deactivateOnTimer) return;
			clearTimeout(timer_label);
			timer_label = null;
		}

		function BeginMenuTimer(){
			if(!settings.deactivateOnTimer){
				DeactivateMenu();
				return;
			}
			if(timer_label != null){
				ClearMenuTimer();
			}
			timer_label = setTimeout(function(){
				DeactivateMenu();
			}, settings.timerDelay);
		}

// MENU EVENTS

		// Курсор мыши был перемещен в область меню
		function EventMenuEnter(event){
			ClearMenuTimer();
		}

		// Курсор мыши вышел за область меню
		function EventMenuLeave(event){
			BeginMenuTimer();
		}

// TOP ITEMS EVENTS

		// Клик на элеменете верхнего уровня
		function EventTopItemClick( event ){
			if(active){
				DeactivateMenu();
			}else{
				active = true;
				if(!popupMode){
					ShowSubMenu(event.currentTarget);
					$(event.currentTarget).find('a:first-child').addClass(settings.topItemActiveClass);
				}else{
					ShowMenu($topmenu, popupMenu);
					$($topmenu).addClass(settings.popupItemActiveClass);
				}
			}
		}

		function EventTopItemEnter( event ){
			if(active){
				if(prev_enter_object != event.currentTarget){
					HideSubMenu(prev_enter_object, true);
					if(!popupMode){
						ShowSubMenu(event.currentTarget);
						$(prev_enter_object).find('a').removeClass(settings.topItemActiveClass);
						$(event.currentTarget).find('a:first-child').addClass(settings.topItemActiveClass);
					}else{
						ShowSubMenu(popupMenu);
						$($topmenu).addClass(settings.popupItemActiveClass);
					}
				}
				prev_enter_object = event.currentTarget;
			}else{
				prev_enter_object = event.currentTarget;
				if(settings.activateOnHover){
					EventTopItemClick(event);
				}
			}
		}

		function EventTopItemLeave( event ){
			prev_enter_object = event.currentTarget;
		}

// SUB ITEMS EVENTS

		// Клик на элементе
		function EventItemClick( event ){
			DeactivateMenu();
		}

		// Курсор мыши был перемещен в область элемента
		function EventItemEnter( event ){
			if(active){
				$(event.currentTarget).parent().children('li').each(function(i,e){
					HideSubMenu(e, true);
				});
				ShowSubMenu(event.currentTarget, true);
			}
		}

		// Курсор мыши вышел за область элемента
		function EventItemLeave( event ){
			//HideSubMenu(event.currentTarget, true);
		}

// GENERATE

		// Генерация списка второго уровня
		function GenerateSubMenu( menuData, top ){
			var $menu = $("<ul>", {"class": 'l-topmenu-sub-'+settings.theme});
			$menu.mousedown(function(event){
				event.stopPropagation();
			});
			if(top){
				$menu.hover(function(event){
						EventMenuEnter(event);
					},
					function(event){
						EventMenuLeave(event);
					});
			}
			var itemId, html, icon, arrow, link_body;

			// Добавляем элементы и субэлементы, если есть
			for(var i = 0; i < menuData.length; i++){
				itemId = menuData[i].id;
				html = '';
				icon = '';
				arrow = '';
				if(menuData[i].icon && menuData[i].icon != ''){
					icon = '<div class="admin_menu_item_icon"><img src="'+menuData[i].icon+'"></div>';
				}else{
					icon = '<div class="admin_menu_item_noicon"></div>';
				}
				if(menuData[i].submenu && menuData[i].submenu.length > 0){
					arrow = '<div class="admin_menu_item_arrow"></div>';
				}

				html = '<div class="admin_menu_item">';
				link_body = icon+arrow+'<div class="admin_menu_item_title">'+menuData[i].title+'</div>';
				switch(menuData[i].type){
					case 'admin':
						html += '<a class="admin_menu_sub_link" href="'+menuData[i].admin_link+'" onclick="return Admin.CheckButton(2, event);" onmousedown="return Admin.LoadPage(\''+menuData[i].admin_link+'\', event, \'\', true);">'+link_body+'</a>';
					break;
					case 'external':
						html += '<a class="admin_menu_sub_link" href="'+menuData[i].link+'"'+(menuData[i].blank == 'true' ? ' target="_blank"' : '')+'>'+link_body+'</a>';
					break;
					case 'js':
						html += '<a class="admin_menu_sub_link" onclick="return false;" onmousedown="'+menuData[i].js+'">'+link_body+'</a>';
					break;
					case 'node':
						html += '<a class="admin_menu_sub_link" href="#" onclick="return false;">'+link_body+'</a>';
					break;
					case 'delimiter':
						html += '<div class="admin_menu_sub_delimiter"></div>';
					break;
					default: html += '<a class="admin_menu_sub_link" href="#" onclick="return false;">'+link_body+'</a>';
				}
				html += '</div>';

				// Создаем элемент меню
				var $mElement = $('<li>', {"class": 'sub'}).html(html);

				// Добавляем дочерний список элементов
				if(menuData[i].submenu && menuData[i].submenu.length > 0){
					GenerateSubMenu(menuData[i].submenu).appendTo($mElement);
				}

				// Устанавливаем обработчики событий
				$mElement
				.mousedown(function(event){
					if(event.which == 1){
						EventItemClick(event);
					}
				})
				.hover(function(event){
						EventItemEnter(event);
					},
					function(event){
						EventItemLeave(event);
					}
				);

				$mElement.appendTo($menu);
			}
			return $menu;
		}

		// Генерация списка верхнего уроня
		function Menu( $parentElement, menuData, popup ){
			if(popup){ // Всплывающее меню
				popupMode = true;
				popupMenu = GenerateSubMenu(menuData, true);
				popupMenu.appendTo(document.body);
				$parentElement.mousedown(function(event){
						EventTopItemClick(event);
						event.stopPropagation();
					});
				$parentElement.hover(function(event){
						EventMenuEnter(event);
					},
					function(event){
						EventMenuLeave(event);
					});
				return $parentElement;
			}

			var $menu = $('<ul>', {"class": 'l-topmenu-'+settings.theme});
			$menu.hover(function(event){
				EventMenuEnter(event);
			},
			function(event){
				EventMenuLeave(event);
			});
			for(var i = 0; i < menuData.length; i++){
				var itemId = menuData[i].id;
				var html = '';
				switch(menuData[i].type){
					case 'admin':
						html += '<a class="admin_menu_top_link" href="'+menuData[i].admin_link+'" onclick="return Admin.CheckButton(2, event);" onMouseDown="return Admin.LoadPage(\''+menuData[i].admin_link+'\', event);">'+menuData[i].title+'</a>';
					break;
					case 'external':
						html += '<a class="admin_menu_top_link" href="'+menuData[i].link+'"'+(menuData[i].blank == 'true' ? ' target="_blank"' : '')+'>'+menuData[i].title+'</a>';
					break;
					case 'js':
						html += '<a class="admin_menu_top_link" onclick="return false;" onMouseDown="'+menuData[i].js+'">'+menuData[i].title+'</a>';
					break;
					case 'node':
						html += '<a class="admin_menu_top_link" href="#" onclick="return false">'+menuData[i].title+'</a>';
					break;
				}
				var $mElement = $('<li>', {"class": 'top', "id": 'topmenu_item_'+itemId}).html(html);
				if(menuData[i].submenu && menuData[i].submenu.length > 0){
					GenerateSubMenu(menuData[i].submenu).appendTo($mElement);
				}
				$mElement.mousedown((function(type){
				  return function(event){
					  if(type != 'node'){
						  HideSubMenus();
						  active = false;
					  }else{
						  EventTopItemClick(event);
					  }
				  }
				})(menuData[i].type));

				$mElement.hover(
				  (function(type){
					  return function(event){
						  if(type == 'node'){
							  EventTopItemEnter(event);
						  }
					  }
				  })(menuData[i].type),
				  (function(type){
					  return function(event){
						  if(type == 'node'){
							  EventTopItemLeave(event);
						  }
					  }
				  })(menuData[i].type)
				);
				$mElement.appendTo($menu);
			}
			$menu.appendTo($parentElement);
			return $menu;
		}

		var settings = $.extend({}, default_options, options);
		$topmenu = Menu(this, menuData, settings.popup);

		$(document).mousedown(function(event){
			// Клик за пределами меню
			if($(event.target).parents().filter('.l-topmenu-'+settings.theme).length != 1){
				DeactivateMenu();
			}
		});

		return this;
	}

})(jQuery);
