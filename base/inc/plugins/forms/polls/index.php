<?php

function getconf_Polls_list( $name )
{
	global $db;
	$polls = $db->Select('polls', "`showinblock`='1'");
	$polls_cnt = count($polls);
	$polls_data = array();
	for($i = 0; $i < $polls_cnt; $i++){
		$polls_data[] = array($polls[$i]['id'], $polls[$i]['question']);
	}
	return $polls_data;
}

?>