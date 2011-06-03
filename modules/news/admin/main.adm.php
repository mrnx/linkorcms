<?php

	if(!defined('VALID_RUN')){
		header("HTTP/1.1 404 Not Found");
		exit;
	}

	System::admin()->AddSubTitle('�������');

	$num = System::config('news/newsonpage'); //���������� �������� �� ��������
	if(isset($_REQUEST['page'])){
		$page = intval($_REQUEST['page']);
		if($page > 1){
			$pageparams = '&page='.$page;
		}
	}else{
		$page = 0;
		$pageparams = '';
	}

	$newsdb = System::database()->Select('news');
	$columns = array('title', 'date', 'hit_counter', 'comments_counter', 'view', 'enabled');
	$sortby = 'date';
	$desc = true;
	if(isset($_POST['sortby'])){
		$sortby = $columns[$_POST['sortby']];
		$desc = $_POST['desc'] == '1';
	}
	SortArray($newsdb, $sortby, $desc);

	// ������� �������
	UseScript('jquery_ui_table');
	$table = new jQueryUiTable();
	$table->listingUrl = ADMIN_FILE.'?exe=news&ajax';
	$table->total = count($newsdb);
	$table->onPage = 1;//$num;
	$table->page = $page;


	$table->AddColumn('���������');
	$table->AddColumn('����', 'left', true, true, true);
	$table->AddColumn('����������', 'right');
	$table->AddColumn('�����������', 'right');
	$table->AddColumn('��� �����', 'center');
	$table->AddColumn('������', 'center');
	$table->AddColumn('�������', 'center', false);

	foreach($newsdb as $news){
		$id = SafeDB($news['id'], 11, int);
		$aed = System::user()->CheckAccess2('news', 'news_edit');

		$status = System::admin()->SpeedStatus('���������', '��������', ADMIN_FILE.'?exe=news&a=changestatus&id='.$id.'&pv=main', $news['enabled'] == '1', 'images/bullet_green.png', 'images/bullet_red.png');
		$view = ViewLevelToStr(SafeDB($news['view'], 1, int));

		$allowComments = SafeDB($news['allow_comments'], 1, bool);
		$comments = SafeDB($news['comments_counter'], 11, int); // ���������� �����������

		$func = '';
		$func .= System::admin()->SpeedButton('�������������', ADMIN_FILE.'?exe=news&a=edit&id='.$id, 'images/admin/edit.png');
		$func .= System::admin()->SpeedButton('�������', ADMIN_FILE.'?exe=news&a=delnews&id='.$id, 'images/admin/delete.png');

		$table->AddRow(
			$id,
			'<b><a href="'.ADMIN_FILE.'?exe=news&a=edit&id='.$id.'">'.SafeDB($news['title'], 255, str).'</a></b>',
			TimeRender(SafeDB($news['date'], 11, int)),
			SafeDB($news['hit_counter'], 11, int),
			($allowComments ? $comments : '���������� �������'),
			$view,
			$status,
			$func
		);
	}

	if(isset($_GET['ajax'])){
		echo $table->GetRowsJson();
		exit;
	}else{
		System::admin()->AddTextBox('�������', $table->GetHtml());
	}

?>