<?php

# Последние созданные темы

function Forum_Last_Inttotime($int = 1) {
	if($int>0) {
		$time = time() -($int*86400) ;
		return  "`start_date`>'$time'";
	}
	else {
		$time = time() -86400 ;
		return  "`start_date`>'$time'";
	}
}

function Forum_Last_Day($mday) {
	if(isset($_GET['day'])) {
		$day=SafeEnv($_GET['day'],11,int);
	}else {
		$day=1;
	}
	if($mday == $day)
		return 'selected="selected"';

}

function Forum_Last_Combo($forum_id=-1) {
	global $lang;
	$text = '<span style="float:left;">'.$lang['viewlasttopics'].'.:&nbsp;<form name="day" action="index.php?name=forum&op=lasttopics'.($forum_id>-1?'&forum='.$forum_id.'':'').'" method="get">
<input type="hidden" name="name" value="forum">
   '.($forum_id>-1?'<input type="hidden" name="forum" value="'.$forum_id.'">':'').'
 <input type="hidden" name="op" value="lasttopics">
<select name="day">
<option value="1" '.Forum_Last_Day(1).'>'.$lang['viewlasttopics24'].'</option>
<option value="2" '.Forum_Last_Day(2).'>'.$lang['viewlasttopics2'].'</option>
<option value="3" '.Forum_Last_Day(3).'>'.$lang['viewlasttopics3'].'</option>
<option value="4" '.Forum_Last_Day(4).'>'.$lang['viewlasttopics4'].'</option>
<option value="5" '.Forum_Last_Day(5).'>'.$lang['viewlasttopics5'].'</option>
<option value="6" '.Forum_Last_Day(6).'>'.$lang['viewlasttopics6'].'</option>
<option value="7" '.Forum_Last_Day(7).'>'.$lang['viewlasttopics7'].'</option>
<option value="14" '.Forum_Last_Day(14).'>'.$lang['viewlasttopics14'].'</option>
<option value="21" '.Forum_Last_Day(21).'>'.$lang['viewlasttopics21'].'</option>
<option value="30" '.Forum_Last_Day(30).'>'.$lang['viewlasttopics30'].'</option>
<option value="60" '.Forum_Last_Day(60).'>'.$lang['viewlasttopics60'].'</option>
<option value="90" '.Forum_Last_Day(90).'>'.$lang['viewlasttopics90'].'</option>
<option value="120" '.Forum_Last_Day(120).'>'.$lang['viewlasttopics120'].'</option>
<option value="150" '.Forum_Last_Day(150).'>'.$lang['viewlasttopics150'].'</option>
<option value="180" '.Forum_Last_Day(180).'>'.$lang['viewlasttopics180'].'</option>
<option value="365" '.Forum_Last_Day(365).'>'.$lang['viewlasttopics365'].'</option>
</select>
<input type="submit" value="Показать" align="middle">
</form></span>';

	return $text ;
}

?>