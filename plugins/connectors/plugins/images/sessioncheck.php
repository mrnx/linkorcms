<?php

if(!($GLOBALS['userAuth'] === 1 && $GLOBALS['userAccess'] === 1 && System::user()->AllowCookie(System::user()->AdminCookieName, true))){
	exit('Access Denied!');
}

