<?php

# LinkorCMS
# � 2006-2009 �������� ��������� ���������� (linkorcms@yandex.ru)
# ����: rss.php
# ����������: ��������� RSS

define('RSS_SCRIPT', true);
define('VALID_RUN', true);

require 'config/init.php'; // ������������ � �������������

@header("Content-Type: text/xml");
@header("Cache-Control: no-cache");
@header("Pragma: no-cache");

$rss_title = '������� �� '.$config['general']['site_url'];
$rss_link = $config['general']['site_url'];
$rss_description = 'RSS ����� ����� '.$config['general']['site_url'].'.';
$rss = new RssChannel($rss_title, $rss_link, $rss_description);
$rss->pubDate = gmdate('D, d M Y H:i:s').' GMT';
$rss->generator = CMS_NAME.' '.CMS_VERSION;
$rss->managingEditor = 'support@linkorcms.ru';
$rss->webMaster = $config['general']['site_email'];
$num = 10; // ���� �������� 10 ���������� �� ���������
$news = $db->Select('news', "`enabled`='1'");
SortArray($news, 'date', true);

foreach($news as $s){
	$title = SafeDB($s['title'], 255, str);
	$description = SafeDB($s['start_text'], 4048, str);
	$link = htmlspecialchars(GetSiteUrl().Ufu('index.php?name=news&op=readfull&news='.$s['id'].'&topic='.$s['topic_id'], 'news/{topic}/{news}/'));
	$pubDate = gmdate('D, d M Y H:i:s', $s['date']).' GMT';
	$rss->AddItem($title, $description, $link, $pubDate, $link);
}

echo $rss->Generate();

?>