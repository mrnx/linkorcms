<?php

// Низкоуровневая конфигурация

define('USE_CACHE', false); // Использовать кэширование
define('PRINT_ERRORS', true); // Выводить ошибки сразу при их появлении(выводит все ошибки, даже если вывод ошибок отключен в конфигурации сайта)
define('LOCALE', "ru_RU.CP1251");  // Системная локаль

define('FORCE_BUILD_SYSTEM', false); // Собирать ядро при каждом запуске
define('BUILD_SYSTEM_WITH_CLASSES', true); // Собирать ядро вместе с классами
define('LOAD_SYSTEM_APART', true); // Загружать каждый модуль ядра отдельно (полезно при отладке)

// FIXME: Адрес страницы может измениться, а обновлять сий файл нельзя
define('CHECK_UPDATE_URL', 'http://updates.linkorcms.ru/index.php?api=checkupdate'); // Адрес страницы проверки новой версии системы
