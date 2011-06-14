<?php

if(!($GLOBALS['userAuth'] === 1 && $GLOBALS['userAccess'] === 1 && System::user()->AllowCookie('admin', true))){
	exit('Access Denied!');
}

?>
