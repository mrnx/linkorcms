<?php

define('CMS_NAME', 'LinkorCMS');
define('CMS_VERSION', '1.4'); // Текущая версия
define('CMS_VERSION_ID', 12); // Порядковый номер версии
define('CMS_BUILD', 'Test'); // Характеристика версии (Test, Alpha, Beta, Final)
define('CMS_TAG', 'linkorcms1.4'); // Тег для связывания версий. Поддержка форматов автоматических обновлений
define('CMS_VERSION_STR', CMS_NAME.' v'.CMS_VERSION.(CMS_BUILD != '' ? ' '.CMS_BUILD : ''));
