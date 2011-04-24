
/**
 * Плагин верхнего меню для админ-панели
 * Автор: Александр Галицкий
 * Плагин разработан в рамках проекта LinkorCMS
 */

(function($){

	$.fn.lTopMenu = function( menuData ){

		var $topmenu;
		var active = false; // Состояние, показывает что меню было активировано
		var timer_label = 'l-topmenu-deactivate-timer';
		var prev_enter_object = null;
		var prev_item_target = null;

		var default_menudata = {
			id: '0', // Уникальный идентификатор объекта
			icon: 'scripts/lTreeView/theme/icon.png', // Имя файла иконки элемента
			title: 'Title', // Секция с заголовком элемента
			submenu: [] // Дочерние элементы в таком-же формате
		};

		function HideSubMenus(fast){
			if(fast){
				$(".l-topmenu-sub:visible").hide();
			}else{
				$(".l-topmenu-sub:visible").fadeOut("fast");
			}
			$(".l-topmenu").find('a').removeClass('active');
			prev_item_target = null;
		}

		function HideSubMenu( item, fast ){
			$ul = $(item).children('ul');
			if($ul.length > 0){
				if(fast){
					$ul.hide();
				}else{
					$ul.fadeOut("fast");
				}
			}
			prev_item_target = null;
		}

		function ShowSubMenu( item, sub ){
			var at = "left bottom";
			var my = "left top";
			var offset = "-1 0";
			$item = $(item);
			$ul = $item.children('ul');
			if($ul.length > 0){
				$ul.fadeIn("fast");
				if(sub){
					at = "right top";
					my = "left top";
					offset = "-4 -1";
				}
				$ul.position({
					of: $item,
					my: my,
					at: at,
					offset: offset
				});
			}

		}

		function ToggleSubMenu( item ){
			$ul = $(item).children('ul');
			if($ul.is(":visible")){
				HideSubMenu(item);
			}else{
				ShowSubMenu(item);
			}
		}

		// Событие - курсор мыши был перемещен в область меню
		function EventMenuEnter(event){
			$(event.currentTarget).stopTime(timer_label);
		}

		// Событие - курсор мыши вышел за область меню
		function EventMenuLeave(event){
			$(event.currentTarget).oneTime(1200, timer_label, function(){
				HideSubMenus();
				active = false;
			});
		}

		// Событие - клик на элеменете верхнего уровня
		function EventTopItemClick( event ){
			if(active){
				HideSubMenus();
				active = false;
			}else{
				active = true;
				ToggleSubMenu(event.currentTarget);
				$(event.currentTarget).find('a:first-child').addClass('active');
			}

		}

		function EventTopItemEnter( event ){
			if(active){
				if(prev_enter_object != event.currentTarget){
					HideSubMenus(true);
					ShowSubMenu(event.currentTarget);
					$(event.currentTarget).find('a:first-child').addClass('active');
				}
			}
		}

		function EventTopItemLeave( event ){
			prev_enter_object = event.currentTarget;
		}

		// Событие - курсор мыши был перемещен в область элемента
		function EventItemEnter( event ){
			//console.dir();
			if(active){
				$(event.currentTarget).parent().children('li').each(function(i,e){
					HideSubMenu(e, true);
				});
				ShowSubMenu(event.currentTarget, true);
			}
		}

		// Событие - курсор мыши вышел за область элемента
		function EventItemLeave( event ){
			//HideSubMenu(event.currentTarget, true);
		}

		// Событие - клик на элементе
		function EventItemClick( event ){
			HideSubMenus();
		}

		// Генерация списка второго уровня
		function GenerateSubMenu( $parentElement, menuData ){
			var $menu = $("<ul>", {"class": 'l-topmenu-sub'});
			$menu.mousedown(function(event){
				event.stopPropagation();
			});
			var itemId, html, icon, arrow, link_body;

			// Добавляем элементы и субэлементы, если есть
			for(var i = 0; i < menuData.length; i++){
				itemId = menuData[i].id;
				html = '';
				icon = '';
				arrow = '';
				if(menuData[i].icon != ''){
					icon = '<div class="admin_menu_item_icon"><img src="'+menuData[i].icon+'"></div>';
				}else{
					icon = '<div class="admin_menu_item_noicon">&nbsp;</div>';
				}
				if(menuData[i].submenu.length > 0){
					arrow = '<div class="admin_menu_item_arrow"></div>';
				}

//				if(menuData[i].admin){
//					admin = "Admin('"+menuData[i].admin+"', window.adminAjaxLinks); return false;";
//					click = 'return false;';
//					target = '';
//				}else if(menuData[i].url){
//					admin = '';
//					click = '';
//
//					if(menuData[i].blank){
//						target = 'target="_blank"';
//					}
//				}

				html = '<div class="admin_menu_item">';
				link_body = icon+arrow+'<div class="admin_menu_item_title">'+menuData[i].title+'</div>';

				href = menuData[i].url;
				switch(menuData[i].type){
					case 'admin': html += '<a class="admin_menu_sub_link" href="'+menuData[i].admin_link+'" onclick="return Admin.CheckButton(2, event);" onMouseDown="return Admin.LoadPage(\''+menuData[i].admin_link+'\', event);">'+link_body+'</a>';
					break;
					case 'external': html += '<a class="admin_menu_sub_link" href="'+menuData[i].external_link+'" onclick="return Admin.CheckButton(2, event);" onMouseDown="return Admin.Leave(\''+menuData[i].external_link+'\', \''+menuData[i].blank+'\', event);">'+link_body+'</a>';
					break;
					case 'js': html += '<a class="admin_menu_sub_link" onclick="return false;" onMouseDown="'+menuData[i].js+'">'+link_body+'</a>';
					break;
					case 'node': html += '<a class="admin_menu_sub_link" href="#" onclick="return false">'+link_body+'</a>';
					break;
					case 'delimiter': html += ''; // TODO: Здесь какой нибудь особый блок
					break;
				}

				html += '</div>';

				// Создаем элемент меню
				var $mElement = $('<li>', {"class": 'sub'}).html(html);

				// Добавляем дочерний список элементов
				if(menuData[i].submenu.length > 0){
					GenerateSubMenu($mElement, menuData[i].submenu);
				}

				// Устанавливаем обработчики событий
				$mElement
				.click(function(event){
					EventItemClick(event);
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
			$menu.appendTo($parentElement);
		}

		// Генерация списка верхнего уроня
		function Menu( $parentElement, menuData ){
			var $menu = $('<ul>', {"class": 'l-topmenu'});
			$menu.hover(function(event){
				EventMenuEnter(event);
			},
			function(event){
				EventMenuLeave(event);
			});
			for(var i = 0; i < menuData.length; i++){
				var itemId = menuData[i].id;
				var $mElement = $('<li>', {"class": 'top', "id": 'topmenu_item_'+itemId})
					.html('<a class="admin_menu_top_link" href="#" onclick="return false">'+menuData[i].title+'</a>');
				if(menuData[i].submenu.length > 0){
					GenerateSubMenu($mElement, menuData[i].submenu);
				}
				$mElement.mousedown(function(event){
					EventTopItemClick(event);
				}).hover(function(event){
					EventTopItemEnter(event);
				},
				function(event){
					EventTopItemLeave(event);
				});
				$mElement.appendTo($menu);
			}
			$menu.appendTo($parentElement);
		}

		$(document).mousedown(function(event){
			if($(event.target).parents().filter('.l-topmenu').length != 1){
				HideSubMenus(true);
				active = false;
			}
		});

		$topmenu = Menu(this, menuData);
		return this;
	}

})(jQuery);