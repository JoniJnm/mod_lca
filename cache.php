<?php 

/**
* @Copyright Copyright (C) 2012 - JoniJnm.es
* @license GNU/GPL http://www.gnu.org/copyleft/gpl.html
**/

class modLcaCache {	
	const CVERSION = "p4";
	var $params;
	var $on;
	var $expired;
	var $time;
	var $path;
	var $file;
	var $xml;
	var $show;
	var $modid;
	
	function __construct(&$params) {
		$this->params = $params;
		$this->on = $params->get('usecache', 1);
		$this->expired = false;
		$this->time = 60*$params->get('cache_time', 900);
		$this->path = JPATH_BASE.'/cache/mod_lca/';
	}
	function init() {
		ob_start();
		print_r($this->params);
		$html = ob_get_contents();
		@ob_end_clean();
		$md5 = md5($html.'_'.self::CVERSION);
		$this->file = $this->path.$md5.'.xml';
		$this->expired = $this->loadXML($this->file);
	}
	function loadXML($file) {
		if (!file_exists($file)) return true;
		$this->xml = @simplexml_load_file($this->file);
		if ($this->xml) {
			if (!isset($this->xml->modid) || !isset($this->xml->o_month) || !isset($this->xml->show) || !isset($this->xml->date) || !isset($this->xml->html)) {
				return true;
			}
		}
		else {
			//malformed, load manually
			$xml = file_get_contents($file);
			$this->xml = new stdclass;
			preg_match('#<modid>(.*?)</modid>#', $xml, $data); if (!$data) return true;
			$this->xml->modid = $data[1];
			preg_match('#<o_month>(.*?)</o_month>#', $xml, $data); if (!$data) return true;
			$this->xml->o_month = $data[1];
			preg_match('#<show>(.*?)</show>#', $xml, $data); if (!$data) return true;
			$this->xml->show = $data[1];
			preg_match('#<date>(.*?)</date>#', $xml, $data); if (!$data) return true;
			$this->xml->date = $data[1];
			preg_match("#<html><!\\[CDATA\\[(.*?)\\]\\]></html>#", $xml, $data); if (!$data) return true;
			$this->xml->html = $data[1];
		}
		return $this->xml->date < time() || !$this->xml->html;
	}
	function show() {
		$this->show = $this->xml->show;
		$this->modid = $this->xml->modid;
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
			file_put_contents($this->file, '<xml><modid>'.$this->modid.'</modid><o_month>'.$this->o_month.'</o_month><show>'.$this->show.'</show><date>'.(time()+$this->time).'</date><html><![CDATA['.$html.']]></html></xml>');
		}
	}
}