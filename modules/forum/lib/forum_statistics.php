<?php

# LinkorCMS
# LinkorCMS Development Group
# www.linkorcms.ru
# Лицензия LinkorCMS 1.2.
# Класс отвечающий за вывод статистики

class ForumStatistics{

	static protected $_instance;

	public $title;
	public $topic_authors_count;
	public $topics_count;
	public $reply_count;
	public $hits;
	public $active_topic_authors;

	public $_current; // id просматриваемого форума
	public $_author_count;
	public $_author_name;

	public $stop = false;


	public function Initialize( $title = '' ){
		$this->title = $title;
		$this->topic_authors_count = 0;
		$this->topics_count = 0;
		$this->reply_count = 0;
		$this->hits = 0;
		$this->active_topic_authors = '';

		$this->_current = '';
		$this->_author_count = array();
		$this->_author_name = array();
	}

	public function AddTopicAuthor( $id, $name ){
		$this->_author_count[] = $id;
		if(isset($this->_author_name[$id])){
			$this->_author_name[$id]['count'] += 1;
		}else{
			$this->_author_name[$id]['name'] = $name;
			$this->_author_name[$id]['id'] = $id;
			$this->_author_name[$id]['count'] = 1;
		}
	}

	public function ActiveTopicAuthors(){
		global $UFU;

		$this->topic_authors_count = count(array_unique($this->_author_count));
		$this->active_topic_authors = ' ';

		if(count($this->_author_name) > 0){
			SortArray($this->_author_name, 'count', true);
			$i = 0;
			foreach($this->_author_name as $author){
				$i++;
				if($UFU){
					$author_link = 'user/'.$author['id'];
					$usertopics_link = 'forum/usertopics'.$this->_current.'/'.$author['id'];
				}else{
					$author_link = 'index.php?name=user&amp;op=userinfo&amp;user='.$author['id'];
					$usertopics_link = 'index.php?name=forum&amp;op=usertopics&amp;user='.$author['id'];
				}
				$this->active_topic_authors .= '<a href="'.$author_link.'">'.$author['name'].'</a> <font size="1">[<a href="'.$usertopics_link.'">'.$author['count'].'</a>]</font>';
				if($i == 20 || count($this->_author_name) == $i){
					$this->active_topic_authors .= '.';
				}else{
					$this->active_topic_authors .= ', ';
				}
				if($i == 20){
					break;
				}
			}
		}

		return $this->active_topic_authors;
	}

	public function Render($block='forum_statistics'){
		global $site, $lang;
		$site->AddBlock($block, true, false, $block, 'module/forum_statistics.html');
		$site->AddBlock('statistics', true, false, 'stat');

		$vars = array();
		$vars['title'] = $this->title;
		$vars['active_topic_authors'] = $this->ActiveTopicAuthors();
		$vars['topic_authors_count'] = $this->topic_authors_count;
		$vars['topics_count'] = $this->topics_count;
		$vars['reply_count'] = $this->reply_count;
		$vars['hits'] = $this->hits;

		$vars['lang_topic_authors_count'] = $lang['author_topics'];
		$vars['lang_topics_count'] = $lang['topics'];
		$vars['lang_reply_count'] = $lang['reply'];
		$vars['lang_hits'] = $lang['hits'];
		$vars['lang_active_topic_authors'] = $lang['active_author_topics'];

		$site->Blocks['statistics']['vars'] = $vars;
	}

	/**
	 *
	 * @return ForumStatistics
	 */
	static public function Instance(){
		if(!(self::$_instance instanceof ForumStatistics)){
			self::$_instance = new ForumStatistics();
		}
		return self::$_instance;
	}

}

?>