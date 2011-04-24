<?php

ini_set('highlight.string', '#008800');
ini_set('highlight.comment', '#969696');
ini_set('highlight.keyword', '#0000DD');
ini_set('highlight.default', '#444444');
ini_set('highlight.html', '#0000FF');

function BbCodePrepare( $string )
{
	return bbcode_custom($string);
}

function BbCodeTag( $tag, $part )
{
	switch($tag){
		case 'php':
			$part = str_replace('<br />', '', $part);
			$part = htmlspecialchars_decode($part);
			if(substr($part, 0, 2) != '<?'){
				$part = "<?\n".$part."\n?>";
			}
			$part = '<div class="bbcode_php">'.highlight_string($part, true).'</div>';
			break;
	}
	return $part;
}

function bbcode_custom( $text = '' )
{
	$preg = array(
	    '~\[s\](.*?)\[\/s\]~si'=>'<del>$1</del>',
	    '~\[b\](.*?)\[\/b\]~si'=>'<strong>$1</strong>',
	    '~\[i\](.*?)\[\/i\]~si'=>'<em>$1</em>',
	    '~\[u\](.*?)\[\/u\]~si'=>'<u>$1</u>',
	    '~\[color=(.*?)\](.*?)\[\/color\]~si'=>'<span style="color:$1;">$2</span>',
	    '~\[size=(.*?)\](.*?)\[\/size\]~si'=>'<span style="font-size:$1px;">$2</span>',
	    '~\[div=(.*?)\](.*?)\[\/div\]~si'=>'<div style="$1">$2</div>',
	    '~\[p=(.*?)\](.*?)\[\/p\]~si'=>'<p style="$1">$2</p>',
	    '~\[span=(.*?)\](.*?)\[\/span\]~si'=>'<span style="$1">$2</span>',
	    '~\[left (.*?)\](.*?)\[\/left\]~si'=>'<div style="text-align: left; $1">$2</div>',
	    '~\[left\](.*?)\[\/left\]~si'=>'<div style="text-align: left;">$1</div>',
	    '~\[right (.*?)\](.*?)\[\/right\]~si'=>'<div style="text-align: right; $1">$2</div>',
	    '~\[right\](.*?)\[\/right\]~si'=>'<div style="text-align: right;">$1</div>',
	    '~\[center (.*?)\](.*?)\[\/center\]~si'=>'<div style="text-align: center; $1">$2</div>',
	    '~\[center\](.*?)\[\/center\]~si'=>'<div style="text-align: center;">$1</div>',
	    '~\[justify\](.*?)\[\/justify\]~si'=>'<p style="text-align: justify;">$1</p>',
	    '~\[pleft\](.*?)\[\/pleft\]~si'=>'<p style="text-align: left;">$1</p>',
	    '~\[pright\](.*?)\[\/pright\]~si'=>'<p style="text-align: right;">$1</p>',
	    '~\[pcenter\](.*?)\[\/pcenter\]~si'=>'<p style="text-align: center;">$1</p>',
	    '~\[br\]~si'=>'<br clear="all">',
	    '~\[hr\]~si'=>'<hr color="#B5B5B5">',
	    '~\[line\]~si'=>'<hr>',
	    '~\[table\]~si'=>'<div><table border="1" cellspacing="1" cellpadding="1" width="50%" style="margin:10px;  float:left;" >',
	    '~\[\/table\]~si'=>'</table></div>',
	    '~\[tr\]~si'=>'<tr>',
	    '~\[\/tr\]~si'=>'</tr>',
	    '~\[td\]~si'=>'<td style="padding:10px;">',
	    '~\[\/td\]~si'=>'</td>',
	    '~\[th\]~si'=>'<th>',
	    '~\[\/th\]~si'=>'</th>',
	    '~\[\*\](.*?)\[\/\*\]~si'=>'<li>$1</li>',
	    '~\[\*\]~si'=>'<li>',
	    '~\[ul\](.*?)\[\/ul\]~si'=>"<ul>$1</li></ul>",
	    '~\[list\](.*?)\[\/list\]~si'=>"<ul>$1</li></ul>",
	    '~\[ol\](.*?)\[\/ol\]~si'=>'<ol>$1</li></ol>',
	    '~\[php\](.*?)\[\/php\]~sei'=>"'<span>'.BbCodeTag('php', '$1').'</span>'",
	    '~\[hide\](.*?)\[\/hide\]~sei'=>"'<div class=\"bbcode_hide\"><a href=\"javascript:onclick=ShowHide(\''.strlen(md5('$1')).substr(md5('$1'),0,3).'\')\">Скрытый текст</a><div id=\"'.strlen(md5('$1')).substr(md5('$1'),0,3).'\" style=\"visibility: hidden; display: none;\">$1</div></div>'",
	    '~\[h1\](.*?)\[\/h1\]~si'=>'<h1>$1</h1>',
	    '~\[h2\](.*?)\[\/h2\]~si'=>'<h2>$1</h2>',
	    '~\[h3\](.*?)\[\/h3\]~si'=>'<h3>$1</h3>',
	    '~\[h4\](.*?)\[\/h4\]~si'=>'<h4>$1</h4>',
	    '~\[h5\](.*?)\[\/h5\]~si'=>'<h5>$1</h5>',
	    '~\[h6\](.*?)\[\/h6\]~si'=>'<h6>$1</h6>',
	    '~\[video\](.*?)\[\/video\]~sei'=>"'<CENTER><div>'.strip_tags(htmlspecialchars_decode('$1'), '<object><param><embed>').'</div></CENTER>'",
	    '~\[code\](.*?)\[\/code\]~si'=>'<div class="bbcode_code"><code>$1</code></div>',
	    '~\[email\](.*?)\[\/email\]~sei'=>"AntispamEmail('$1')",
	    '~\[email=(.*?)\](.*?)\[\/email\]~sei'=>"'<a rel=\"noindex\" href=\"mailto:'.str_replace('@', '.at.','$1').'\">$2</a>'",
	    '~\[url\](.*?)\[\/url\]~sei'=>"'<a href=\"'.UrlRender('$1').'\" target=\"_blank\">$1</a>'",
	    '~\[url=(.*?)?\](.*?)\[\/url\]~sei'=>"'<a href=\"'.UrlRender('$1').'\"target=\"_blank\">$2</a>'",
	    '~\[img=(.*?)x(.*?)\](.*?)\[\/img\]~si'=>'<img src="$3" style="width: $1px; height: $2px" >',
	    '~\[img (.*?)\](.*?)\[\/img\]~si'=>'<img src="$2" title="$1" alt="$1">',
	    '~\[img\](.*?)\[\/img\]~si'=>'<a href="$1" target="_blank"><img src="$1"></a>',
	    '~\[quote\](.*?)\[\/quote\]~si'=>'<div class="bbcode_quote">$1</div>',
	    '~\[quote=(?:&quot;|"|\')?(.*?)["\']?(?:&quot;|"|\')?\](.*?)\[\/quote\]~si'=>'<div class="bbcode_quote"><strong>$1:</strong>$2</div>',
	);
	$text = preg_replace(array_keys($preg), array_values($preg), $text);
	return $text;
}

?>