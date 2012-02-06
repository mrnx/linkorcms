<?php

// Модуль для очистки кэша
TAddSubTitle('Управление кэшем');

function AdminCache( $action )
{
	switch ($action){
		case 'main':
			AdminCacheMain();
			break;
		case 'clear':
			AdminCacheClean();
			break;
		case 'cleanup':
			AdminCacheCleanup();
			break;
		}
}

if(isset($_GET['a'])){
	AdminCache($_GET['a']);
}else{
	AdminCache('main');
}

function AdminCacheMain(){
	global $config;
	$cache = LmFileCache::Instance();
	$groups = $cache->GetGroups();

	AddCenterBox('Управление кэшем');

	if(!$cache->Enabled){
		if(USE_CACHE){
			System::admin()->HighlightError('<strong style="color: #FF0000;">Внимание!</strong> Папка "'.$cache->Path.'" не доступна для записи. Функция кэширования отключена.');
		}else{
			System::admin()->HighlightError('<strong style="color: #FF0000;">Внимание!</strong> Функция кэширования отключена в конфигурационном файле "config/config.php".');
		}
	}


	$text = '<table cellspacing="0" cellpadding="0" class="cfgtable">';
	$text .= '<tr><th>Группа</th><th>Папка</th><th>Записей</th><th>Занимаемое место</th><th>Функции</th></tr>';

	$num_rows = 0;
	$total_size = 0;
	foreach($groups as $g){
		$file_size = 0;
		$num_files = 0;
		$folder = $cache->Path.$g;
		$files = scandir($folder);
		foreach($files as $file){
			if (($file!='.') && ($file!='..')){
				$f = $folder.'/'.$file;
				if(!is_dir($f)){
					$file_size += filesize($f);
				}
				$num_files++;
			}
		}
		$func = SpeedButton('Очистить', ADMIN_FILE.'?exe=cache&a=clear&group='.SafeDB($g, 255, str), 'images/admin/cleanup.png');
		$rows = floor($num_files / 2);
		$text .= '<tr>'
			.'<td>'.SafeDB($g, 255, str).'</td>'
			.'<td>'.SafeDB($folder, 255, str).'</td>'
			.'<td>'.$rows.'</td>'
			.'<td>'.FormatFileSize($file_size).'</td>'
			.'<td>'.$func.'</td>'
			.'</tr>';
		$num_rows += $rows;
		$total_size += $file_size;
	}

	$text .= '</table><br />';
	$text .= 'Итого <b>'.count($groups).'</b> групп(ы), <b>'.$num_rows.'</b> записей и <b>'.FormatFileSize($total_size).'</b> занято. <a href="'.ADMIN_FILE.'?exe=cache&a=cleanup" class="button">Очистить все группы</a>';
	$text .= '<br /><br />';
	AddText($text);
}

function AdminCacheClean()
{
	$group = $_GET['group'];
	$cache = LmFileCache::Instance();
	$cache->Clear($group);
	AdminCacheMain();
}

function AdminCacheCleanup()
{
	$cache = LmFileCache::Instance();
	$groups = $cache->GetGroups();
	foreach($groups as $g){
		$cache->Clear($g);
	}
	AdminCacheMain();
}

?>
