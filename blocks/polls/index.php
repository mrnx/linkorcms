<?php

// Блок Голосования
// LinkorCMS Development Group

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

global $config, $db;

$pid = $config['polls']['default_vote'];
$db->Select('polls', "`showinblock`='1' and `active`='1' and `id`='".$pid."'");

$vars['title'] = $title;

if($db->NumRows() == 0 && $user->Auth && $user->isAdmin()){
	$vars['content'] = '<center><font color="#FF0000">Нет активного голосования.</font></center>';
	return;
}elseif($db->NumRows() == 0){
	$enabled = false;
	return;
}

$poll = $db->FetchRow();

// Проверяем отвечал ли пользователь

$ip = getip();
if($user->Auth){
	$uid = $user->Get('u_id');
}else{
	$uid = -1;
}

$db->Select('polls_voices', "`poll_id`='$pid' and (`user_ip`='$ip' or `user_id`='$uid')");
$viewresult = $db->NumRows() != 0; // Если пользователь уже голосовал то показать результаты

// Подсчитываем количество ответов
$answers = unserialize($poll['answers']);
$c = count($answers);
$num_voices = 0; // Количество ответов
for($i = 0; $i < $c; $i++){
	$num_voices += $answers[$i][2];
}

// Показать результаты
if($viewresult){
	if($num_voices){
		$per_c = 100 / $num_voices;
	}else{
		$per_c = 100;
	}
	$tempvars['content'] = 'block/content/poll_results.html';
	$vars['poll_title'] = SafeDB($poll['question'], 255, str);
	$site->AddBlock('poll_block_results', true, true, 'poll');
	$variants_vars = array();
	for($i = 0; $i < $c; $i++){
		if($answers[$i][0] != ''){
			$variants_vars['color'] = SafeDB($answers[$i][1], 255, str);
			$variants_vars['title'] = SafeDB($answers[$i][0], 255, str);
			$variants_vars['num_voices'] = SafeDB($answers[$i][2], 11, int);
			$variants_vars['value'] = (round($per_c * $answers[$i][2]));
			$site->AddSubBlock('poll_block_results', true, $variants_vars);
		}
	}
	$vars['lnum_voices'] = 'Всего ответов';
	$vars['num_voices'] = $num_voices;
	$vars['lcomments'] = 'Комментировать';
	$vars['comments'] = SafeDB($poll['com_counter'], 11, int);
	$vars['poll_url'] = Ufu('index.php?name=polls&op=viewresult&poll_id='.$pid, 'polls/{poll_id}/results/');
	$vars['lothers'] = 'Другие опросы';
	$vars['others_url'] = Ufu('index.php?name=polls', '{name}/');

// Показать форму ответов
}else{
	$tempvars['content'] = 'block/content/poll.html';
	$vars['form_action'] = Ufu('index.php?name=polls&op=voice&poll_id='.$pid, 'polls/{poll_id}/voice/');
	$vars['poll_title'] = SafeDB($poll['question'], 255, str);
	$site->AddBlock('poll_block_variants', true, true, 'variant');
	$variants_vars = array();
	for($i = 0; $i < $c; $i++){
		if($answers[$i][0] != ''){
			$variants_vars['color'] = SafeDB($answers[$i][1], 255, str);
			$variants_vars['title'] = SafeDB($answers[$i][0], 255, str);
			if($poll['multianswers'] == '1'){
				$variants_vars['control'] = $site->Check('voice[]', $i);
			}else{
				$variants_vars['control'] = $site->Radio('voice[]', $i);
			}
			$site->AddSubBlock('poll_block_variants', true, $variants_vars);
		}
	}
	$vars['lnum_voices'] = 'Ответов';
	$vars['num_voices'] = $num_voices;
	$vars['lcomments'] = 'Комментариев';
	$vars['comments'] = SafeDB($poll['com_counter'], 11, int);
	$vars['poll_showresults'] = ($config['polls']['show_results'] == '1' || $user->isAdmin());
	$vars['lshowresults'] = 'Итоги опроса';
	$vars['poll_url'] = Ufu("index.php?name=polls&op=viewresult&poll_id=$pid", 'polls/{poll_id}/results/');
	$vars['lothers'] = 'Другие опросы';
	$vars['others_url'] = Ufu('index.php?name=polls', '{name}/');
}

?>