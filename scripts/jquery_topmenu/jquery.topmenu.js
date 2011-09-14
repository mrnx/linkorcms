
/**
 * ������ �������� ���� ��� �����-������
 * �����: ��������� ��������
 * ������ ���������� � ������ ������� LinkorCMS
 */

(function($){

	$.fn.topmenu = function( menuData ){

		var $topmenu;
		var active = false; // ���������, ���������� ��� ���� ���� ������������
		var timer_label = null;
		var prev_enter_object = null;
		var prev_item_target = null;

		var default_menudata = {
			id: '0', // ���������� ������������� �������
			icon: 'scripts/lTreeView/theme/icon.png', // ��� ����� ������ ��������
			title: 'Title', // ������ � ���������� ��������
			submenu: [] // �������� �������� � �����-�� �������
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

		// ������� - ������ ���� ��� ��������� � ������� ����
		function EventMenuEnter(event){
			clearTimeout(timer_label);
		}

		// ������� - ������ ���� ����� �� ������� ����
		function EventMenuLeave(event){
			timer_label = setTimeout(function(){
				HideSubMenus();
				active = false;
			}, 1200);
		}

		// ������� - ���� �� ��������� �������� ������
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

		// ������� - ������ ���� ��� ��������� � ������� ��������
		function EventItemEnter( event ){
			//console.dir();
			if(active){
				$(event.currentTarget).parent().children('li').each(function(i,e){
					HideSubMenu(e, true);
				});
				ShowSubMenu(event.currentTarget, true);
			}
		}

		// ������� - ������ ���� ����� �� ������� ��������
		function EventItemLeave( event ){
			//HideSubMenu(event.currentTarget, true);
		}

		// ������� - ���� �� ��������
		function EventItemClick( event ){
			HideSubMenus();
			active = false;
		}

		// ��������� ������ ������� ������
		function GenerateSubMenu( $parentElement, menuData ){
			var $menu = $("<ul>", {"class": 'l-topmenu-sub'});
			$menu.mousedown(function(event){
				event.stopPropagation();
			});
			var itemId, html, icon, arrow, link_body;

			// ��������� �������� � �����������, ���� ����
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

				html = '<div class="admin_menu_item">';
				link_body = icon+arrow+'<div class="admin_menu_item_title">'+menuData[i].title+'</div>';
				switch(menuData[i].type){
					case 'admin':
						html += '<a class="admin_menu_sub_link" href="'+menuData[i].admin_link+'" onclick="return Admin.CheckButton(2, event);" onMouseDown="return Admin.LoadPage(\''+menuData[i].admin_link+'\', event);">'+link_body+'</a>';
					break;
					case 'external':
						html += '<a class="admin_menu_sub_link" href="'+menuData[i].external_link+'"'+(menuData[i].blank == 'true' ? ' target="_blank"' : '')+'>'+link_body+'</a>';
					break;
					case 'js':
						html += '<a class="admin_menu_sub_link" onclick="return false;" onMouseDown="'+menuData[i].js+'">'+link_body+'</a>';
					break;
					case 'node':
						html += '<a class="admin_menu_sub_link" href="#" onclick="return false">'+link_body+'</a>';
					break;
					case 'delimiter':
						html += '<div class="admin_menu_sub_delimiter"></div>';
					break;
				}
				html += '</div>';

				// ������� ������� ����
				var $mElement = $('<li>', {"class": 'sub'}).html(html);

				// ��������� �������� ������ ���������
				if(menuData[i].submenu.length > 0){
					GenerateSubMenu($mElement, menuData[i].submenu);
				}

				// ������������� ����������� �������
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

		// ��������� ������ �������� �����
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
				var html = '';
				switch(menuData[i].type){
					case 'admin':
						html += '<a class="admin_menu_top_link" href="'+menuData[i].admin_link+'" onclick="return Admin.CheckButton(2, event);" onMouseDown="return Admin.LoadPage(\''+menuData[i].admin_link+'\', event);">'+menuData[i].title+'</a>';
					break;
					case 'external':
						html += '<a class="admin_menu_top_link" href="'+menuData[i].external_link+'"'+(menuData[i].blank == 'true' ? ' target="_blank"' : '')+'>'+menuData[i].title+'</a>';
					break;
					case 'js':
						html += '<a class="admin_menu_top_link" onclick="return false;" onMouseDown="'+menuData[i].js+'">'+menuData[i].title+'</a>';
					break;
					case 'node':
						html += '<a class="admin_menu_top_link" href="#" onclick="return false">'+menuData[i].title+'</a>';
					break;
				}

				var $mElement = $('<li>', {"class": 'top', "id": 'topmenu_item_'+itemId}).html(html);
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
