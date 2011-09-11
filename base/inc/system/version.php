<?php

define('CMS_NAME', 'LinkorCMS'); // Имя системы
define('CMS_VERSION', '1.4'); // Текущая версия
define('CMS_BUILD', 'Test'); // Характеристика версии (Test, Alpha, Beta, Final)

define('CMS_UPDATE_PRODUCT', 'linkorcms1.4');
define('CMS_UPDATE_VERSION', '1.4.0');

define('CMS_VERSION_STR', CMS_NAME.' v'.CMS_VERSION.(CMS_BUILD != '' ? ' '.CMS_BUILD : ''));
