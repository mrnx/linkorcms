<?php

// Удаление резервной копии

$name = RealPath2(System::config('backup_dir').$_GET['name']);
unlink($name);
GO(ADMIN_FILE.'?exe=fdbadmin&a=backups');