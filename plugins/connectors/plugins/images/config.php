<?php

// Директория с файлами (относительно корневой) и URL путь к папке с файлами относительно плагина
define('DIR_FILES', 'uploads');
define('URL_FILES', '../../../../uploads');

// Иконки файлов и URL путь к папке с иконками файлов относительно плагина
define('DIR_ICONS', 'scripts/tinymce/plugins/images/img/fileicons');
define('URL_ICONS', 'img/fileicons');

// Папка по умолчанию при первой загрузке
define('DIR_DEFAULT_PATH', '');

//Высота и ширина картинки до которой будет сжато исходное изображение и создана ссылка на полную версию
define('WIDTH_TO_LINK', 256);
define('HEIGHT_TO_LINK', 256);

//Атрибуты которые будут присвоены ссылке (для скриптов типа lightbox)
define('CLASS_LINK', 'lightview');
define('REL_LINK', 'lightbox');

// Включить GZip сжатие передаваемых данных
define('GZIP', true);

// Расширения картинок и разрешенные расширения для загружаемых файлов
define('ALLOWED_IMAGES', 'jpeg,jpg,gif,png');
define('ALLOWED_FILES', 'doc,docx,ppt,pptx,xls,xlsx,mdb,accdb,swf,zip,rar,rtf,pdf,psd,mp3,wma,jpeg,jpg,gif,png');

?>