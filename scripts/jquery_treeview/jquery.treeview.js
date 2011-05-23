
/*
 * LinkorCMS JQuery TreeView 1.0
 * ��������� ������������ ������������� ������ � ������������ ���������� ���������������
 *
 * Copyright 2011, ��������� ��������
 * Email: linkorcms@yandex.ru
 * Site: http://linkorcms.ru/
 *
 * �����������:
 *	 jquery.ui.nestedSortable.js
 *   jquery.ui.position.js
 *
 */

(function($){

	lTreeView = function(options, tree, nestedSortableOtions, parentComponent){
		var ThisObj = this;
		/**
		 * ������ �� ��������� � DOM
		 */
		this.tree = null;

		/**
		 * ��������� ���������� �� ���������
		 */
		var default_options = {
			moveHandler: '' // ����� �������� ����������� ����������� ���������
		};
		this.options = $.extend({}, default_options, options);

		/**
		 * ��������� �� ��������� ��� ������� NestedSortable
		 */
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
			update: function(event, ui){
				var $item = $(ui.item); // ������������ �������
				var $target = $(ui.item).parents('li:first'); // ���� �����������

				var item_opt = $item.data('options');
				var target_opt = $target.data('options');

				// �������� POST ������ ����������� ���������
				if(ThisObj.options.moveHandler != ''){
					var index = $(ui.item).parent().children().index(ui.item);
					window.Admin.ShowSplashScreen();
					$.ajax({
						type: "POST",
						url: ThisObj.options.moveHandler,
						data: 'item_id='+item_opt.id+'&target_id='+target_opt.id+'&item_new_position='+index,
						cache: false,
						success: window.Admin.HideSplashScreen
					});
					// FIXME: ��� ��������� ����������� ������ ���������� ��������� �� ������
				}

				if(target_opt.isnode && !target_opt.opened){ // ��������� � ��������
					var $li = $item.detach();
					$target.children('ol').remove();
					ThisObj.ToggleNode($target, function(){
						$target.children('ol').append($li);
						ThisObj.UpdateBullets();
					});
				} else{
					ThisObj.UpdateBullets();
				}
			}
		};

		if(nestedSortableOtions != undefined){ // ���-�� ���������� �����. � ����������� ������ ����
			nestedSortableOtions = {};
		}
		nestedSortableOtions = $.extend({}, default_nestedSortableOptions, nestedSortableOtions);

		/**
		 * ������ �������� ������ �� ���������
		 */
		this.default_item_options = {
			id: '0', // ���������� ������������� �������
			icon: 'scripts/jquery_treeview/theme/icon.png', // ��� ����� ������ ��������
			title: 'NodeTitle', // ������ � ���������� ��������
			info: '', // ������ � ����������� ��� ����������� ��������� � ������� HTML
			func: '', // ������ � ��������������� �������� � ��������
			opened: false, // ������ open/close
			nonest: false, // ��������� �� � ���� ������� ������ �������� ��������
			isnode: false, // ���� �� �������� ������� - ����� ����� ��� �� ���������
			child_url: '', // ����� ��� ��������� �����
			childs: [] // �������� �������� � �����-�� �������
		};

		// ���������� ������
		this.tree = this.GenerateList($(parentComponent), tree, true);
		// ������ ������ �����������
		$(this.tree).nestedSortable(nestedSortableOtions);
	};

	lTreeView.prototype = {

		/**
		 * �������� ������ ����������
		 * @param item_id
		 */
		ShowInfoButton: function(item_id){
			$("#item_info_"+item_id).show().css('display', 'inline-block');
		},

		/**
		 * ������ ������ ����������
		 * @param item_id
		 */
		HideInfoButton: function(item_id){
			$("#item_info_"+item_id).hide();
		},

		/**
		 * �������� - �������� �������� ����������� �������� ��������
		 * @param item_id ������ �������� ������ ��� ��� ������� id
		 * @param end_toggle ���������� ������� ��� ������ �������� (��������� ��������)
		 */
		ToggleNode: function(item_id, end_toggle){
			if(typeof item_id != 'object'){
				var $element = $('li#item_'+item_id);
			} else{
				var $element = $(item_id);
			}
			var opt = $element.data('options');
			var $node = $element.find("ol:first");
			var $bullet = $('#item_bullet_'+item_id);
			if('opened' in opt && opt.opened){ // ������
				$node.slideUp();
				opt.opened = false;
				// ������ ������ �� ������
				$bullet.removeClass('node_open');
				if(!$bullet.hasClass('node_close')){
					$bullet.addClass('node_close');
				}
			} else{ // ��������
				if(!$node.length){
					if('childs' in opt && opt.childs.length > 0){
						this.GenerateList($element, opt.childs);
						if(end_toggle != undefined){
							end_toggle.call($element);
						}
					} else{
						if('child_url' in opt && opt.child_url != ''){
							this.LoadList($element, opt.child_url, end_toggle);
						}
					}
				} else{
					$node.slideDown();
					if(end_toggle != undefined){
						end_toggle.call($element);
					}
				}
				opt.opened = true;
				// ������ ������ �� ������
				$bullet.removeClass('node_close');
				if(!$bullet.hasClass('node_open')){
					$bullet.addClass('node_open');
				}
			}
		},

		/**
		 * ���������� �������� ������
		 */
		UpdateBullets: function(){
			var ThisObj = this;
			this.tree.find('li').each(function(){
				var $obj = $(this);
				var opt = $obj.data('options');
				var $bullet = $obj.find('.node_button:first');
				var $child = $obj.find('ol:first');
				if(($child.length > 0 && $child.find('li').length > 0) || opt.isnode){ // ���� �������� ��������
					//opt.isnode = true;
					if($child.is(':visible')){ // �����
						$bullet.removeClass('node_close');
						if(!$bullet.hasClass('node_open')){
							$bullet.addClass('node_open');
						}
						opt.opened = true;
					} else{ // �����
						$bullet.removeClass('node_open');
						if(!$bullet.hasClass('node_close')){
							$bullet.addClass('node_close');
						}
						opt.opened = false;
					}
					// ��������� �������
					$bullet.unbind();
					$bullet.bind('click', function(){
						ThisObj.ToggleNode(opt.id);
					});
				} else{
					if($child.length > 0){ // ������ ������
						$child.remove();
					}
					$bullet.removeClass('node_close node_open').addClass('node_none');
					opt.isnode = false;
					opt.opened = true;
					// ������� �������
					$bullet.unbind();
				}
			});
		},

		/**
		 * ���������� ������� ������ �������� �������� ���������
		 * @param $element
		 */
		LoadingStart: function($element){
			return $("<ol>", {
				"class": 'treeview',
				html: '<li><img src="images/ajax-loader.gif" style="vertical-align: middle;" /></li>'
			}).appendTo($element);
		},

		/**
		 * ���������� ������� ��������� �������� �������� ���������
		 * @param $element
		 * @param $list
		 * @param $placeholder
		 */
		LoadingEnd: function($element, $list, $placeholder){
			$placeholder.remove();
			$list.show();
		},

		/**
		 * �������� ������ �������� ������ � �������������� ���������������� ����������
		 * @param opt
		 */
		GenerateElement: function(opt){
			opt = $.extend({}, this.default_item_options, opt);
			var ThisObj = this;

			// �������
			var element_options = {id: "item_"+opt.id};
			if(opt.nonest){
				$.extend(element_options, {"class": "no-nest"});
			}
			var $element = $('<li>', element_options);
			var $div_helper = $('<div>').appendTo($element); // ��������������� ��� � ������� �������� ����� �������� � �������� � ������ ����

			// ���������
			var $div = $('<div>', {
				id: "item_div_"+opt.id,
				"class": "item",
				mouseenter: function(){
					ThisObj.ShowInfoButton(opt.id);
				},
				mouseleave : function(){
					ThisObj.HideInfoButton(opt.id);
				}
			}).appendTo($div_helper);

			// ������ �������� � �������� ���������
			var bullet_options = {
				id: "item_bullet_"+opt.id
			};
			if('childs' in opt && opt.childs.length == 0 && !opt.isnode){
				$.extend(bullet_options, {"class": "node_button node_none"});
			} else{
				if(opt.opened){
					$.extend(bullet_options, {
						"class": "node_button node_open", click: function(){
							ThisObj.ToggleNode(opt.id);
						}
					});
				} else{
					$.extend(bullet_options, {
						"class": "node_button node_close", click: function(){
							ThisObj.ToggleNode(opt.id);
						}
					});
				}
			}
			$('<div>', bullet_options).prependTo($div_helper);

			// ������
			if('icon' in opt && opt.icon != ''){
				$('<div class="item_icon" id="item_icon_'+opt.id+'"><img src="'+opt.icon+'" title="�����������" /></div>').appendTo($div);
			}

			//���������
			$('<div class="item_title" id="item_title_'+opt.id+'">'+opt.title+'<a name="item_'+opt.id+'" /></div>').appendTo($div);

			// ����������� ���������� �� ��������
			if('info' in opt && opt.info != ''){
				var $info = $('<div id="item_info_'+opt.id+'" class="item_info"><span class="tooltip">'+opt.info+'</span></div>').appendTo($div);
				$info.lPopUp({
					             show: function(options){
						             $(ThisObj).children(options.popupObject).fadeIn("fast");
					             }
				             });
			}

			// �������������� ������
			if('func' in opt && opt.func != ''){
				$('<div class="item_func_bar" id="item_func_'+opt.id+'">'+opt.func+'</div>').appendTo($div);
			}

			// �������� ��������
			if('opened' in opt && opt.opened){
				if('childs' in opt && opt.childs.length > 0){
					this.GenerateList($element, opt.childs);
				} else{
					if('child_url' in opt && opt.child_url != ''){
						this.LoadList($element, opt.child_url);
					}
				}
			}
			$element.data('options', opt);
			return $element; // <li>
		},

		/**
		 * �������� � ���������� ���������� ������ �����
		 * @param $parentElement ������������ �������
		 * @param loadUrl �����, ������ ��������� �������� (������ � ������� json)
		 * @param endLoad ���������� ��������� ��������
		 */
		LoadList: function($parentElement, loadUrl, endLoad){
			var $placeholder = this.LoadingStart($parentElement);
			var ThisObj = this;
			$.ajax({
				       url: loadUrl,
				       dataType: "json",
				       success: function(data){
					       ThisObj.LoadingEnd($parentElement, ThisObj.GenerateList($parentElement, data, false, true), $placeholder);
					       if(endLoad != undefined){
						       endLoad.call($parentElement);
					       }
				       }
			       });
		},

		/**
		 * ��������� ������ (OL)
		 * @param $parentElement ������������ ������� (���������)
		 * @param elements ������ ��� �������� ������
		 * @param _toplevel ������ �������� ������
		 * @param hidden ������ ������ ����� �������� (�������� ���� ������� �������)
		 */
		GenerateList: function($parentElement, elements, _toplevel, hidden){
			if(arguments.length > 2 && _toplevel == true){
				var classname = "treeview toplevel";
			} else{
				var classname = "treeview";
			}
			if(arguments.length > 3){
				var hide_list = hidden;
			} else{
				var hide_list = false;
			}
			if($parentElement.data('options') && 'id' in $parentElement.data('options')){
				var id = $parentElement.data('options').id;
			} else{
				var id = '0';
			}

			var $ol = $("<ol>", {
				"class": classname,
				id: 'node_'+id
			}).appendTo($parentElement);

			// ��������� �������� ������
			for(var i = 0; i < elements.length; i++){
				$ol.append(this.GenerateElement(elements[i]));
			}
			if(hide_list){
				$ol.hide();
			}
			return $ol;
		}

	}

	$.fn.lTreeView = function(options, tree, nestedSortableOtions){
		return new lTreeView(options, tree, nestedSortableOtions, this);
	};

})(jQuery);
