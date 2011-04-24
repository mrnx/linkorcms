<?php

global $bases_path, $db;

function ConvertCommentsToPosts( $TableName )
{
	global $db;
	$db->RenameColl($TableName, 1, 'object_id');
	$db->RenameColl($TableName, 3, 'post_date');
	$db->EditColl($TableName, 4, Unserialize('a:5:{s:4:"name";s:9:"user_name";s:4:"type";s:7:"varchar";s:6:"length";i:255;s:7:"default";s:0:"";s:7:"notnull";b:1;}'));
	$db->EditColl($TableName, 5, Unserialize('a:5:{s:4:"name";s:13:"user_homepage";s:4:"type";s:7:"varchar";s:6:"length";i:255;s:7:"default";s:0:"";s:7:"notnull";b:1;}'));
	$db->EditColl($TableName, 6, Unserialize('a:5:{s:4:"name";s:10:"user_email";s:4:"type";s:7:"varchar";s:6:"length";i:255;s:7:"default";s:0:"";s:7:"notnull";b:1;}'));
	$db->RenameColl($TableName, 7, 'user_hideemail');
	$db->DeleteColl($TableName, 8); //icq
	$db->RenameColl($TableName, 8, 'post_message');
	$db->InsertColl($TableName, Unserialize('a:4:{s:4:"name";s:14:"post_parent_id";s:4:"type";s:3:"int";s:6:"length";i:11;s:7:"notnull";b:1;}'), 9);
}


// 1. Удаляем старые настройки

$db->Delete('config', "`id`='84' or `id`='85' or `id`='86'");

// 2. Добавляем новые настройки и группы

$db->Insert("config_groups","'','smtp','Параметры SMTP','','1'");
$gid = $db->GetLastId();
$db->Insert("config","'','$gid','use_smtp','0','1','Использовать SMTP','Отправлять ли почту с сайта через SMTP сервер','combo','function:yes_no','','1,int,false','1'");
$db->Insert("config","'','$gid','host','','1','Сервер','','edit:w400px','','','255,string,true','1'");
$db->Insert("config","'','$gid','port','25','1','Порт сервера','По умолчанию 25','edit:w60px','','','5,int,true','1'");
$db->Insert("config","'','$gid','login','','1','Имя пользователя','','edit:w200px','','','255,string,true','1'");
$db->Insert("config","'','$gid','password','','1','Пароль','','password:w200px','','','255,string,true','1'");

$db->Insert("config","'','1','default_timeone','Asia/Yekaterinburg','1','Временная зона сайта по умолчанию','','combo','function:timezone_identifiers','','255,string,true','1'");
$db->Insert("config","'','1','site_host','','0','Домен сайта','','','','','','1'");
$db->Insert("config","'','6','guestpost','1','1','Разрешить гостям комментировать','Разрешить незарегистрированным пользователям оставлять комментарии на сайте','combo','function:yes_no','','1,bool,false','1'");
$db->Insert("config","'','6','answers','0','1','Древовидные комментарии','Разрешить пользователям отвечать на комментарии','combo','function:yes_no','','1,bool,false','1'");
$db->Insert("config","'','6','maxtreelevel','5','1','Максимальная вложенность комментарий','','edit:w200px','','','11,int,false','1'");
$db->Insert("config","'','6','ennav','1','1','Постраничная навигация','Включить ли постраничную навигацию','combo','function:yes_no','','1,bool,false','1'");
$db->Insert("config","'','6','onpage','2','1','Сообщений на страницу','Количество сообщений первого уровня на страницу','edit:w200px','','','11,int,false','1'");
$db->Insert("config","'','6','decreasesort','0','1','Сортировка по возрастанию','Новые сообщения внизу','combo','function:yes_no','','1,bool,false','1'");
$db->Insert("config","'','6','disable_posts_engine','0','1','Отключить комментарии','Отключить систему комментариев на всем сайте','combo','function:yes_no','','1,bool,false','1'");
$db->Insert("config","'','6','show_kaptcha_for_members','1','1','Капча для пользователей','Показывать тест Капча для зарегистрированных пользователей','combo','function:yes_no','','1,bool,false','1'");
$db->Insert("config","'','1','default_template','IgrimTheme','0','Шаблон по умолчанию','','','','','','1'");

$db->Insert("config","'','5','file_exts','.rar, .zip, .7z, .bz2, .cab, .ace, .arj, .jar, .gzip, .tar, .tgz, .gz, .gif, .jpeg, .jpe, .jpg, .png, .bmp, .txt, .sql, .exe, .swf, .fla, .flv, .f4v, .f4p, .f4a, .f4b, .wav, .mp2, .mp3, .mp4, .ogv, .oga, .ogx, .ogg, .mid, .midi, .mmf, .mpeg, .mpe, .mpg, .mpa, .avi, .mpga, .pdf, .pds, .xls, .xl, .xla, .xlb, .xlc, .xld, .xlk, .xll, .xlm, .xlt, .xlv, .xlw, .doc, .dot, .wiz, .wzs, .docx, .xlsx, .odt, .odg, .odp, .ods, .odc, .odi, .odf, .odm, .pot, .ppa, .pps, .ppt, .pwz, .rtf','0','Разрешенные расширения файлов для загрузки','Перечислите через запятую расширения файлов через запятую разрешенных для загрузки через форму редактора файлов','','','','0,string,true','1'");
$db->Insert("config","'','5','files_dir','uploads/files/','0','Папка длязагрузки файлов','','','','','255,string,true','1'");

$db->Insert("config","'','1','specialoutlinks','1','1','Промежуточная страница для внешних ссылок','Использовать промежуточную страницу для перехода по внешним ссылкам из модулей','combo','function:yes_no','','1,bool,false','1'");

// 3. Обновляем таблицы комментариев

ConvertCommentsToPosts('articles_comments');
ConvertCommentsToPosts('downloads_comments');
ConvertCommentsToPosts('gallery_comments');
ConvertCommentsToPosts('news_comments');
ConvertCommentsToPosts('polls_comments');

$db->Delete('comments', "`table`='forum_posts'");

// 4. Обновление таблицы Users

$db->EditColl('users', 11, Unserialize('a:5:{s:4:"name";s:8:"timezone";s:4:"type";s:7:"varchar";s:6:"length";i:255;s:7:"default";s:0:"";s:7:"notnull";b:1;}'));

// 5. Обновление таблицы modules

$db->InsertColl('modules', Unserialize('a:4:{s:4:"name";s:5:"theme";s:4:"type";s:7:"varchar";s:6:"length";i:255;s:7:"notnull";b:1;}'), 10);

// 6. Установка модуля "Комментарии"

$db->Insert("modules","'','Комментарии','comments','1','0','LinkorCMS Development Group','','1','1','15','1',''");

// 7. Обновление таблицы downloads

$db->InsertColl('downloads', Unserialize('a:5:{s:4:"name";s:9:"size_type";s:4:"type";s:4:"char";s:6:"length";i:1;s:7:"default";s:1:"b";s:7:"notnull";b:1;}'), 3);

// 8. Устанавливаем плагин Out

$db->Insert("plugins","'','out','','4',''");

?>