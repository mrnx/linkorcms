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


// 1. ������� ������ ���������

$db->Delete('config', "`id`='84' or `id`='85' or `id`='86'");

// 2. ��������� ����� ��������� � ������

$db->Insert("config_groups","'','smtp','��������� SMTP','','1'");
$gid = $db->GetLastId();
$db->Insert("config","'','$gid','use_smtp','0','1','������������ SMTP','���������� �� ����� � ����� ����� SMTP ������','combo','function:yes_no','','1,int,false','1'");
$db->Insert("config","'','$gid','host','','1','������','','edit:w400px','','','255,string,true','1'");
$db->Insert("config","'','$gid','port','25','1','���� �������','�� ��������� 25','edit:w60px','','','5,int,true','1'");
$db->Insert("config","'','$gid','login','','1','��� ������������','','edit:w200px','','','255,string,true','1'");
$db->Insert("config","'','$gid','password','','1','������','','password:w200px','','','255,string,true','1'");

$db->Insert("config","'','1','default_timeone','Asia/Yekaterinburg','1','��������� ���� ����� �� ���������','','combo','function:timezone_identifiers','','255,string,true','1'");
$db->Insert("config","'','1','site_host','','0','����� �����','','','','','','1'");
$db->Insert("config","'','6','guestpost','1','1','��������� ������ ��������������','��������� �������������������� ������������� ��������� ����������� �� �����','combo','function:yes_no','','1,bool,false','1'");
$db->Insert("config","'','6','answers','0','1','����������� �����������','��������� ������������� �������� �� �����������','combo','function:yes_no','','1,bool,false','1'");
$db->Insert("config","'','6','maxtreelevel','5','1','������������ ����������� �����������','','edit:w200px','','','11,int,false','1'");
$db->Insert("config","'','6','ennav','1','1','������������ ���������','�������� �� ������������ ���������','combo','function:yes_no','','1,bool,false','1'");
$db->Insert("config","'','6','onpage','2','1','��������� �� ��������','���������� ��������� ������� ������ �� ��������','edit:w200px','','','11,int,false','1'");
$db->Insert("config","'','6','decreasesort','0','1','���������� �� �����������','����� ��������� �����','combo','function:yes_no','','1,bool,false','1'");
$db->Insert("config","'','6','disable_posts_engine','0','1','��������� �����������','��������� ������� ������������ �� ���� �����','combo','function:yes_no','','1,bool,false','1'");
$db->Insert("config","'','6','show_kaptcha_for_members','1','1','����� ��� �������������','���������� ���� ����� ��� ������������������ �������������','combo','function:yes_no','','1,bool,false','1'");
$db->Insert("config","'','1','default_template','IgrimTheme','0','������ �� ���������','','','','','','1'");

$db->Insert("config","'','5','file_exts','.rar, .zip, .7z, .bz2, .cab, .ace, .arj, .jar, .gzip, .tar, .tgz, .gz, .gif, .jpeg, .jpe, .jpg, .png, .bmp, .txt, .sql, .exe, .swf, .fla, .flv, .f4v, .f4p, .f4a, .f4b, .wav, .mp2, .mp3, .mp4, .ogv, .oga, .ogx, .ogg, .mid, .midi, .mmf, .mpeg, .mpe, .mpg, .mpa, .avi, .mpga, .pdf, .pds, .xls, .xl, .xla, .xlb, .xlc, .xld, .xlk, .xll, .xlm, .xlt, .xlv, .xlw, .doc, .dot, .wiz, .wzs, .docx, .xlsx, .odt, .odg, .odp, .ods, .odc, .odi, .odf, .odm, .pot, .ppa, .pps, .ppt, .pwz, .rtf','0','����������� ���������� ������ ��� ��������','����������� ����� ������� ���������� ������ ����� ������� ����������� ��� �������� ����� ����� ��������� ������','','','','0,string,true','1'");
$db->Insert("config","'','5','files_dir','uploads/files/','0','����� ����������� ������','','','','','255,string,true','1'");

$db->Insert("config","'','1','specialoutlinks','1','1','������������� �������� ��� ������� ������','������������ ������������� �������� ��� �������� �� ������� ������� �� �������','combo','function:yes_no','','1,bool,false','1'");

// 3. ��������� ������� ������������

ConvertCommentsToPosts('articles_comments');
ConvertCommentsToPosts('downloads_comments');
ConvertCommentsToPosts('gallery_comments');
ConvertCommentsToPosts('news_comments');
ConvertCommentsToPosts('polls_comments');

$db->Delete('comments', "`table`='forum_posts'");

// 4. ���������� ������� Users

$db->EditColl('users', 11, Unserialize('a:5:{s:4:"name";s:8:"timezone";s:4:"type";s:7:"varchar";s:6:"length";i:255;s:7:"default";s:0:"";s:7:"notnull";b:1;}'));

// 5. ���������� ������� modules

$db->InsertColl('modules', Unserialize('a:4:{s:4:"name";s:5:"theme";s:4:"type";s:7:"varchar";s:6:"length";i:255;s:7:"notnull";b:1;}'), 10);

// 6. ��������� ������ "�����������"

$db->Insert("modules","'','�����������','comments','1','0','LinkorCMS Development Group','','1','1','15','1',''");

// 7. ���������� ������� downloads

$db->InsertColl('downloads', Unserialize('a:5:{s:4:"name";s:9:"size_type";s:4:"type";s:4:"char";s:6:"length";i:1;s:7:"default";s:1:"b";s:7:"notnull";b:1;}'), 3);

// 8. ������������� ������ Out

$db->Insert("plugins","'','out','','4',''");

?>