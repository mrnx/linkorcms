<?php

# LinkorCMS
# © 2006-2010 Галицкий Александр Николаевич (linkorcms@yandex.ru)
# Файл: rss.class.php
# Назначение: Класс для генерации RSS 2.0
class RssChannel
{
	# Настройки канала

	// Обязательные настройки канала
	public $title;
	public $link;
	public $description;
	
	// Необязательные параметры канала
	public $language = 'ru';
	public $copyright;
	public $pubDate;
	public $lastBuildDate;
	public $category;
	public $docs;
	public $generator;
	public $managingEditor;
	public $webMaster;
	public $cloud;
	public $ttl;
	public $rating;
	public $textInput;
	public $skipHours;
	public $skipDays;
	
	// Картинка к RSS-каналу
	public $image = false;
	public $image_url;
	public $image_title;
	public $image_link;
	public $image_width;
	public $image_height;
	public $image_description;

	public $items = array();
	public $DefaultEncoding = 'windows-1251';

	public function RSSChannel( $title, $link, $description )
	{
		$this->title = $title;
		$this->link = $link;
		$this->description = $description;
	}

	public function AddItem( $title, $description, $link = '', $pubDate = '', $guid = '', $comments = '', $category = '', $enclosure = '', $author = '', $source = '' )
	{
		$item = array();
		$item['title'] = $title;
		$item['description'] = $description;
		$item['link'] = $link;
		$item['pubDate'] = $pubDate;
		$item['guid'] = $guid;
		$item['comments'] = $comments;
		$item['category'] = $category;
		$item['enclosure'] = $enclosure;
		$item['author'] = $author;
		$item['source'] = $source;
		$this->items[] = $item;
	}

	public function XMLTag( $tabs, $name, $value, $params = '' )
	{
		if($value != ''){
			return str_repeat("\t", $tabs)."<$name".($params != '' ? " $params" : '').">$value</$name>\n";
		}else{
			return '';
		}
	}

	public function Generate()
	{
		$rss = "";
		$rss .= "<"."?"."xml version=\"1.0\" encoding=\"{$this->DefaultEncoding}\"?".">"."\n";
		$rss .= "<rss version=\"2.0\">\n";
		$rss .= "\t<channel>\n";
		$rss .= $this->XMLTag(2, 'title', $this->title);
		$rss .= $this->XMLTag(2, 'link', $this->link);
		$rss .= $this->XMLTag(2, 'description', $this->description);
		$rss .= $this->XMLTag(2, 'language', $this->language);
		$rss .= $this->XMLTag(2, 'copyright', $this->copyright);
		$rss .= $this->XMLTag(2, 'pubDate', $this->pubDate);
		$rss .= $this->XMLTag(2, 'lastBuildDate', $this->lastBuildDate);
		$rss .= $this->XMLTag(2, 'category', $this->category);
		$rss .= $this->XMLTag(2, 'docs', $this->docs);
		$rss .= $this->XMLTag(2, 'generator', $this->generator);
		$rss .= $this->XMLTag(2, 'managingEditor', $this->managingEditor);
		$rss .= $this->XMLTag(2, 'webMaster', $this->webMaster);
		$rss .= $this->XMLTag(2, 'cloud', $this->cloud);
		$rss .= $this->XMLTag(2, 'ttl', $this->ttl);
		$rss .= $this->XMLTag(2, 'textInput', $this->textInput);
		$rss .= $this->XMLTag(2, 'skipHours', $this->skipHours);
		$rss .= $this->XMLTag(2, 'skipDays', $this->skipDays);
		if($this->image){
			$rss .= "\t\t<image>\n";
			$rss .= $this->XMLTag(3, 'image_url', $this->image_url);
			$rss .= $this->XMLTag(3, 'image_title', $this->image_title);
			$rss .= $this->XMLTag(3, 'image_link', $this->image_link);
			$rss .= $this->XMLTag(3, 'image_width', $this->image_width);
			$rss .= $this->XMLTag(3, 'image_height', $this->image_height);
			$rss .= $this->XMLTag(3, 'image_description', $this->image_description);
			$rss .= "\t\t</image>\n";
		}
		foreach($this->items as $item){
			$rss .= "\t\t<item>\n";
			$rss .= $this->XMLTag(3, 'title', $item['title']);
			$rss .= $this->XMLTag(3, 'description', $item['description']);
			$rss .= $this->XMLTag(3, 'link', $item['link']);
			$rss .= $this->XMLTag(3, 'pubDate', $item['pubDate']);
			$rss .= $this->XMLTag(3, 'guid', $item['guid']);
			$rss .= $this->XMLTag(3, 'comments', $item['comments']);
			$rss .= $this->XMLTag(3, 'category', $item['category']);
			$rss .= $this->XMLTag(3, 'enclosure', $item['enclosure']);
			$rss .= $this->XMLTag(3, 'author', $item['author']);
			$rss .= $this->XMLTag(3, 'source', $item['source']);
			$rss .= "\t\t</item>\n";
		}
		$rss .= "\t</channel>\n";
		$rss .= "</rss>\n";
		return $rss;
	}
}
?>