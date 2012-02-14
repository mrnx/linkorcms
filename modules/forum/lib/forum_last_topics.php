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
	global $forum_lang;
	$text = '<span style="float:left;">'.$forum_lang['viewlasttopics'].'.:&nbsp;<form name="day" action="index.php?name=forum&op=lasttopics'.($forum_id>-1?'&forum='.$forum_id.'':'').'" method="get">
<input type="hidden" name="name" value="forum">
   '.($forum_id>-1?'<input type="hidden" name="forum" value="'.$forum_id.'">':'').'
 <input type="hidden" name="op" value="lasttopics">
<select name="day">
<option value="1" '.Forum_Last_Day(1).'>'.$forum_lang['viewlasttopics24'].'</option>
<option value="2" '.Forum_Last_Day(2).'>'.$forum_lang['viewlasttopics2'].'</option>
<option value="3" '.Forum_Last_Day(3).'>'.$forum_lang['viewlasttopics3'].'</option>
<option value="4" '.Forum_Last_Day(4).'>'.$forum_lang['viewlasttopics4'].'</option>
<option value="5" '.Forum_Last_Day(5).'>'.$forum_lang['viewlasttopics5'].'</option>
<option value="6" '.Forum_Last_Day(6).'>'.$forum_lang['viewlasttopics6'].'</option>
<option value="7" '.Forum_Last_Day(7).'>'.$forum_lang['viewlasttopics7'].'</option>
<option value="14" '.Forum_Last_Day(14).'>'.$forum_lang['viewlasttopics14'].'</option>
<option value="21" '.Forum_Last_Day(21).'>'.$forum_lang['viewlasttopics21'].'</option>
<option value="30" '.Forum_Last_Day(30).'>'.$forum_lang['viewlasttopics30'].'</option>
<option value="60" '.Forum_Last_Day(60).'>'.$forum_lang['viewlasttopics60'].'</option>
<option value="90" '.Forum_Last_Day(90).'>'.$forum_lang['viewlasttopics90'].'</option>
<option value="120" '.Forum_Last_Day(120).'>'.$forum_lang['viewlasttopics120'].'</option>
<option value="150" '.Forum_Last_Day(150).'>'.$forum_lang['viewlasttopics150'].'</option>
<option value="180" '.Forum_Last_Day(180).'>'.$forum_lang['viewlasttopics180'].'</option>
<option value="365" '.Forum_Last_Day(365).'>'.$forum_lang['viewlasttopics365'].'</option>
</select>
<input type="submit" value="Показать" align="middle">
</form></span>';

	return $text ;
}
