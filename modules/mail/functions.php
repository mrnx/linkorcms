<?php

function CalcListCounter( $topic_id, $inc )
{
	global $db, $config;
	$db->Select('mail_topics', "`id`='".$topic_id."'");
	$topic = $db->FetchRow();
	if($inc == true){
		$counter_val = $topic['count'] + 1;
	}else{
		$counter_val = $topic['count'] - 1;
	}
	$db->Update('mail_topics', "`count`='".$counter_val."'", "`id`='".$topic_id."'");
}

function CalcMailCounter( $topic_id, $inc )
{
	global $db, $config;
	$db->Select('mail_topics', "`id`='".$topic_id."'");
	$topic = $db->FetchRow();
	if($inc == true){
		$counter_val = $topic['send_count'] + 1;
		$date = ",last_send='".time()."'";
	}else{
		$counter_val = $topic['send_count'] - 1;
		$date = '';
	}
	$db->Update('mail_topics', "send_count='".$counter_val."'".$date, "`id`='".$topic_id."'");
}

?>