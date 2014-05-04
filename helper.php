<?php

/**
* @Copyright Copyright (C) 2014 - JoniJnm.es
* @license GNU/GPL http://www.gnu.org/copyleft/gpl.html
**/

defined('_JEXEC') or die('Restricted access');

require_once (JPATH_SITE.'/components/com_content/helpers/route.php');

class modLcaHelper {
	private $params;
	private $itemid;
	
	function __construct(&$params) {
		$this->params = $params;
		$this->itemid = $this->getItemid();
	}
	function getImg($img) {
		$data = new stdClass;
		if ($img) {
			$data->expand = JURI::base()."modules/mod_lca/assets/img/expand.png";
			$data->collapse = JURI::base()."modules/mod_lca/assets/img/collapse.png";
		}
		else {
			$data->expand = "▼";
			$data->collapse = "►";
		}
		return $data;
	}
	function addTags() {
		if (!defined("LCA_HEADER_FUNCTION")) {
			define("LCA_HEADER_FUNCTION", 1);
			$document = JFactory::getDocument();
			$text = self::getImg(false);
			$img = self::getImg(true);
			$document->addScriptDeclaration('
				LCA_IMG_EXPAND = "'.$img->expand.'";
				LCA_IMG_COLLAPSE = "'.$img->collapse.'";
				LCA_TEXT_EXPAND = "'.$text->expand.'";
				LCA_TEXT_COLLAPSE = "'.$text->collapse.'";
			');
			$document->addStyleSheet(JURI::base().'modules/mod_lca/assets/css/style.css');
			$document->addScript(JURI::base().'modules/mod_lca/assets/js/lca.js');
		}
	}
	private function getRows() {
		$db = JFactory::getDBO();
		$date = JFactory::getDate();
		$user = JFactory::getUser();
		$nullDate = $db->getNullDate();
		$now = method_exists($date, 'toMySQL') ? $date->toMySQL() : $date->toSql();
		
		if (!$this->params->get('show_pub_articles') && !$this->params->get('archived')) return array();
		
		if ($this->params->get('show_pub_articles') && $this->params->get('archived'))
			$state = '(a.state = 1 OR a.state = 2)';
		else if ($this->params->get('show_pub_articles'))
			$state = 'a.state = 1';
		else
			$state = 'a.state = 2';
		
		$order = $this->params->get("order", 2);
		if ($order == 0) //created
			$order = 'a.created AS co,';
		elseif ($order == 1) //modified
			$order = 'CASE WHEN modified = '.$db->Quote($nullDate).' THEN a.created ELSE a.modified END AS co,';
		else //publised
			$order = 'a.publish_up AS co,';
		
		$cats = trim($this->params->get("cats", "")) ? " AND a.catid IN (".$this->params->get("cats", "").")" : "";
		
		if ($user->id)
			$access = ' AND (a.access=1 OR a.access=2)';
		else
			$access = ' AND (a.access=1)';
		
		$query = 'SELECT '.
			$order.' '.
			'a.id, a.title, a.alias, a.catid, c.alias as calias'.
			' FROM #__content AS a'.
			' LEFT JOIN #__categories AS c ON c.id=a.catid'.
			' WHERE ('.$state.')' .
			$access.
			' AND ( a.publish_up = '.$db->Quote($nullDate).' OR a.publish_up <= '.$db->Quote($now).' )'.
			' AND ( a.publish_down = '.$db->Quote($nullDate).' OR a.publish_down >= '.$db->Quote($now).' )'.
			' AND c.published = 1'.
			$cats.
			' ORDER BY co '.$this->params->get("o_article", "desc");
		if ($this->params->get("maxarticles", 150) > 0)
			$query .= ' LIMIT '.$this->params->get("maxarticles", 150);
		$db->setQuery($query);
		return $db->loadObjectList();
	}
	function getList() {
		$rows = $this->getRows();
		if (!$rows) return array();
		$list = $this->params->get("list", "year_month");
		if ($list == 'years_months') return $this->list2($rows, 'getYearByRow', 'getMonthByRow');
		elseif ($list == 'years') return $this->list1($rows, 'getYearByRow');
	}
	private function getArticleHTML(&$row) {
		$cut_title = $this->params->get("cut_title", 0);
		$url = ContentHelperRoute::getArticleRoute($row->id.":".$row->alias, $row->catid.":".$row->calias);
		if ($this->itemid && strpos($url, "&Itemid=") === false) $url .= '&Itemid='.$this->itemid;
		$url = JRoute::_($url);
		if ($cut_title && strlen($row->title) > $cut_title)
			$row->title = substr($row->title, 0, $cut_title).'...';
		if ($this->params->get('date', 0))
			return '<span style="cursor:pointer" title="'.$this->getDate($row->co).'">'.$this->getDay($row->co).'</span> - <a href="'.$url.'">'.$row->title.'</a>';
		else
			return '<a href="'.$url.'">'.$row->title.'</a>';
	}
	private function getYearByRow($row) {
		return $this->getYear($row->co);
	}
	private function getMonthByRow($row) {
		$m = $this->getMonth($row->co);
		return $this->monthToString($m);
	}
	private function getCategoryByRow($row) {
		return $row->ctitle;
	}
	private function list2(&$rows, $f_get_sec, $f_get_cat) {
		$out = $this->createDefaultOut();
		
		$cats = array();
		foreach ($rows as $row) {
			$cat = call_user_func(array($this, $f_get_cat), $row);
			if (!isset($cats[$cat])) $cats[$cat] = array();
		}
		if ($out->o_cat == 'desc') rsort($cats);
		else ksort($cats);
		
		foreach ($rows as $row) {
			$sec = call_user_func(array($this, $f_get_sec), $row);
			$cat = call_user_func(array($this, $f_get_cat), $row);
			
			if (isset($out->secs[$sec])) $out->secs[$sec]++;
			else $out->secs[$sec] = 1;
			
			if (!isset($out->cats[$sec])) $out->cats[$sec] = array();
			if (isset($out->cats[$sec][$cat])) $out->cats[$sec][$cat]++;
			else $out->cats[$sec][$cat] = 1;
			
			if (!isset($out->articulos[$sec])) $out->articulos[$sec] = $cats;
			$out->articulos[$sec][$cat][] = $this->getArticleHTML($row);
		}
		if ($out->o_sec == 'desc') krsort($out->articulos);
		else ksort($out->articulos);
		return $out;
	}
	private function &list1(&$rows, $f_get_sec) {
		$out = $this->createDefaultOut();
		
		foreach ($rows as $row) {
			$sec = call_user_func(array($this, $f_get_sec), $row);
			if (!isset($out->articulos[$sec])) $out->articulos[$sec] = array();
			$out->articulos[$sec][] = $this->getArticleHTML($row);
			if (isset($out->secs[$sec])) $out->secs[$sec]++;
			else $out->secs[$sec] = 1;
		}
		if ($out->o_sec == 'desc') krsort($out->articulos);
		else ksort($out->articulos);
		return $out;
	}
	private function &createDefaultOut() {
		$out = (object)array();
		$out->articulos = array();
		$out->secs = array();
		$out->cats = array();
		$out->o_sec = $this->params->get('o_sec', 'desc');
		$out->o_cat = $this->params->get('o_cat', 'desc');
		return $out;
	}
	private function getItemid() {
		$db = JFactory::getDBO();
		
		$db->setQuery('SELECT id FROM #__menu WHERE link="index.php?option=com_content&view=featured" AND home=1');
		$id = $db->loadResult();
		if ($id) return $id;
		$db->setQuery('SELECT id FROM #__menu WHERE link="index.php?option=com_content&view=featured" AND access<=1 AND published=1 ORDER BY id DESC LIMIT 1');
		$id = $db->loadResult();
		if ($id) return $id;
		$db->setQuery('SELECT id FROM #__menu WHERE link LIKE "index.php?option=com_content%" AND access<=1 AND published=1 ORDER BY id DESC LIMIT 1');
		$id = $db->loadResult();
		
		if ($id) return $id;
		return 0;
	}
	private function getDate($date) {
		$date = explode(" ", $date);
		return $date[0];
	}
	private function getYear($date) {
		$date = explode("-", $date);
		return $date[0];
	}
	private function getMonth($date) {
		$date = explode("-", $date);
		return $date[1];
	}
	private function getDay($date) {
		$date = $this->getDate($date);
		$date = explode("-", $date);
		return $date[2];
	}
	private function monthToString($month) {
		static $data = null;
		if ($data === null) {
			$data = array('', JText::_('JANUARY'), JText::_('FEBRUARY'), JText::_('MARCH'), JText::_('APRIL'), JText::_('MAY'), JText::_('JUNE'),
					JText::_('JULY'), JText::_('AUGUST'), JText::_('SEPTEMBER'), JText::_('OCTOBER'), JText::_('NOVEMBER'), JText::_('DECEMBER'));
		}
		return $data[intval($month)];
	}
}