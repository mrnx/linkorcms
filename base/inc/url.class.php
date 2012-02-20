<?php

class Url extends ArrayObject{

	public $Protocol = '';
	public $Host = '';
	public $Port = '';
	public $User = '';
	public $Password = '';
	public $Path = '';
	public $Document = '';
	public $Anchor = '';

	public function __construct( $Url = '' ){
		if($Url == '') return;
		$info = parse_url($Url);
		if($info === false) return;
		if(isset($info['scheme'])){
			$this->Protocol = $info['scheme'];
		}
		if(isset($info['host'])){
			$this->Host = $info['host'];
		}
		if(isset($info['port'])){
			$this->Port = $info['port'];
		}
		if(isset($info['user'])){
			$this->User = $info['user'];
		}
		if(isset($info['pass'])){
			$this->Password = $info['pass'];
		}
		if(isset($info['path'])){
			$this->Path = GetPathName($info['path']);
			$this->Document = GetFileName($info['path']);
		}
		if(isset($info['fragment'])){
			$this->Anchor = $info['fragment'];
		}
		if(isset($info['query'])){
			parse_str($info['query'], $params);
			parent::__construct($params);
		}else{
			parent::__construct();
		}
	}

	public function ToString(){
		$url = '';
		if($this->Protocol != ''){
			$url .= $this->Protocol.'://';
		}
		if($this->User != ''){
			$url .= rawurlencode($this->User);
			if($this->Password != ''){
				$url .= ':'.rawurlencode($this->Password);
			}
			$url .= '@';
		}
		if($this->Host != ''){
			$url .= $this->Host;
		}
		if($this->Port != ''){
			$url .= ':'.$this->Port;
		}
		$url .= $this->Path.$this->Document;
		if(count($this) > 0){
			$url .= '?'.http_build_query($this);
		}
		if($this->Anchor != ''){
			$url .= '#'.$this->Anchor;
		}
		return $url;
	}

	public function __toString(){
		return $this->ToString();
	}

}
