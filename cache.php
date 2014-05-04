<?php 

/**
* @Copyright Copyright (C) 2014 - JoniJnm.es
* @license GNU/GPL http://www.gnu.org/copyleft/gpl.html
**/

class modLcaCache {	
	const CVERSION = "f6";
	
	public $show;
	
	private $params;
	private $on;
	private $expired;
	private $time;
	private $path;
	private $file;
	private $xml;
	
	function __construct(&$params) {
		$this->params = $params;
		$this->on = $params->get('usecache', 1);
		$this->expired = false;
		$this->time = 60*$params->get('cache_time', 900);
		$this->path = JPATH_BASE.'/cache/mod_lca/';
	}
	function init() {
		$user = JFactory::getUser();
		$loged = $user->id ? 1 : 0;
		$str = print_r($this->params, true).'_'.$loged.'_'.self::CVERSION;
		$md5 = md5($str);
		$this->file = $this->path.$md5.'.xml';
		$this->expired = $this->loadXML($this->file);
	}
	function show() {
		$this->show = $this->xml->show;
		echo $this->xml->html;
	}
	function check() {
		return $this->on && !$this->expired;
	}
	function start() {
		if ($this->on && $this->expired)
			ob_start();
	}
	function end() {
		if ($this->on && $this->expired) {
			$html = ob_get_contents();
			@ob_end_clean();
			echo $html;
			if (file_exists($this->file))
				unlink($this->file);
			if (!file_exists($this->path))
				mkdir($this->path);
			file_put_contents($this->file, '<xml><show>'.$this->show.'</show><date>'.(time()+$this->time).'</date><html><![CDATA['.$html.']]></html></xml>');
		}
	}
	private function loadXML($file) {
		if (!file_exists($file)) return true;
		$this->xml = @simplexml_load_file($this->file);
		if ($this->xml) {
			if (!isset($this->xml->show) || !isset($this->xml->date) || !isset($this->xml->html)) {
				return true;
			}
		}
		else {
			//malformed, load manually
			$xml = file_get_contents($file);
			$this->xml = new stdclass;
			preg_match('#<show>(.*?)</show>#', $xml, $data); if (!$data) return true;
			$this->xml->show = $data[1];
			preg_match('#<date>(.*?)</date>#', $xml, $data); if (!$data) return true;
			$this->xml->date = $data[1];
			preg_match("#<html><!\\[CDATA\\[(.*?)\\]\\]></html>#", $xml, $data); if (!$data) return true;
			$this->xml->html = $data[1];
		}
		return $this->xml->date < time() || !$this->xml->html;
	}
}
