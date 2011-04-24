<?php
# LinkorCMS
# LinkorCMS Development Group
# www.linkorcms.ru
# Лицензия LinkorCMS 1.2.
# пометки о прочтении

function Forum_Marker_GetReadData() {
	global $db, $user;
	$read_array = array();
	if($user->Auth){
		$read_data = $db->Select('forum_topics_read', "`mid`='".$user->Get('u_id')."'");
		foreach($read_data as $data){
			$read_array[$data['tid']] = $data;
		}
	}
	return $read_array;
}

?>