<?php

// ��� ������������� ������.

function mod_initialization()
{
	global $system;
	$system['no_templates'] = true;
	$system['no_messages'] = true;
	$system['no_echo'] = true;
	$system['stop_hit'] = true;
}

function mod_finalization()
{
	//
}

?>