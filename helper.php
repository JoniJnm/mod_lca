<?php

/**
* @Copyright Copyright (C) 2014 - JoniJnm.es
* @license GNU/GPL http://www.gnu.org/copyleft/gpl.html
**/

defined('_JEXEC') or die('Restricted access');

require_once (JPATH_SITE.'/components/com_content/helpers/route.php');

class modLcaHelper {
	var $params;
	var $itemid;
	var $j15;
	
	function __construct(&$params) {
		$this->j15 = version_compare(JVERSION, "1.6.0", "<=");
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
	function &getList() {
		$db = JFactory::getDBO();
		$date = JFactory::getDate();
		$user = JFactory::getUser();
		$nullDate	= $db->getNullDate();
		$now = method_exists($date, 'toMySQL') ? $date->toMySQL() : $date->toSql();
		
		$out = (object)array();
		$out->articulos = array();
		$out->years = array();
		$out->meses = array();
		
		if (!$this->params->get('show_pub_articles') && !$this->params->get('archived')) return $out;
		
		if ($this->j15) {
			if ($this->params->get('show_pub_articles') && $this->params->get('archived'))
				$state = '(a.state = -1 OR a.state = 1)';
			else if ($this->params->get('show_pub_articles'))
				$state = 'a.state = 1';
			else
				$state = 'a.state = -1';
		}
		else {
			if ($this->params->get('show_pub_articles') && $this->params->get('archived'))
				$state = '(a.state = 1 OR a.state = 2)';
			else if ($this->params->get('show_pub_articles'))
				$state = 'a.state = 1';
			else
				$state = 'a.state = 2';
		}
		
		$order = $this->params->get("order", 2);
		if ($order == 0) //created
			$order = 'a.created AS co,';
		elseif ($order == 1) //modified
			$order = 'CASE WHEN modified = '.$db->Quote($nullDate).' THEN a.created ELSE a.modified END AS co,';
		else //publised
			$order = 'a.publish_up AS co,';
		
		$section = $this->j15 ? 'a.sectionid,' : '';
		if ($this->j15)
			$secs = trim($this->params->get("secs", "")) ? " AND a.sectionid IN (".$this->params->get("secs", "").")" : "";
		else
			$secs = '';
		$cats = trim($this->params->get("cats", "")) ? " AND a.catid IN (".$this->params->get("cats", "").")" : "";
		$maxyears = $this->params->get("maxyears", 0);
		
		if ($user->id)
			$access = ' AND (a.access='.($this->j15?0:1).' OR a.access='.($this->j15?1:2).')';
		else
			$access = ' AND (a.access='.($this->j15?0:1).')';
		
		$o_article = $this->params->get("o_article", "desc");
		$query = 'SELECT '.
			$order.' '.
			$section.' '.
			'a.id, a.title, a.alias, a.catid, c.alias as calias'.
			' FROM #__content AS a'.
			' LEFT JOIN #__categories AS c ON c.id=a.catid'.
			($this->j15 ? ' LEFT JOIN #__sections AS s ON s.id=a.sectionid' : '').
			' WHERE ('.$state.' '.($this->j15?'AND s.id > 0':'').')' .
			$access.
			' AND ( a.publish_up = '.$db->Quote($nullDate).' OR a.publish_up <= '.$db->Quote($now).' )'.
			' AND ( a.publish_down = '.$db->Quote($nullDate).' OR a.publish_down >= '.$db->Quote($now).' )'.
			($this->j15?' AND s.published = 1':'').
			' AND c.published = 1'.
			$secs.
			$cats.
			' ORDER BY co '.$o_article;
		if ($this->params->get("maxarticles", 150) > 0)
			$query .= ' LIMIT '.$this->params->get("maxarticles", 150);
		$db->setQuery($query);
		$rows = $db->loadObjectList();
		if (!$rows) {
			$data = null;
			return $data;
		}

		$o_month = $this->params->get("o_month");
		$descOrderForMonths = $o_month == "desc" || ($o_month == "off" && $o_article == 'desc');
		$monthsArray = $this->getMonths($descOrderForMonths);
		
		foreach ($rows as $row) {
			$d = $this->getYear($row->co);
			if (!isset($out->articulos[$d])) 
				$out->articulos[$d] = $monthsArray;
		}
		
		krsort($out->articulos);
		
		if ($maxyears) {
			$i = 0;
			foreach ($out->articulos as $year=>$months) {
				if ($i == $maxyears) unset($out->articulos[$year]);
				else $i++;
			}
		}
		
		if ($this->params->get("o_year", "desc") == "asc") ksort($out->articulos);
		
		$cut_title   = $this->params->get("cut_title", 0);
		foreach ($rows as $row) {
			$d = $this->getYear($row->co);
			$m = $this->getMonth($row->co);
			
			if (isset($out->articulos[$d])) {
				$out->lastyear = $d;
				if ($this->j15)
					$url = ContentHelperRoute::getArticleRoute($row->id.":".$row->alias, $row->catid.":".$row->calias, $row->sectionid);
				else
					$url = ContentHelperRoute::getArticleRoute($row->id.":".$row->alias, $row->catid.":".$row->calias);
				if ($this->itemid && strpos($url, "&Itemid=") === false) $url .= '&Itemid='.$this->itemid;
				$link = JRoute::_($url);
				$month = $this->monthToString($m);
				if ($cut_title && strlen($row->title) > $cut_title)
					$row->title = substr($row->title, 0, $cut_title).'...';
				if ($this->params->get('date', 0))
					$out->articulos[$d][$month][] = '<span style="cursor:pointer" title="'.$this->getDate($row->co).'">'.$this->getDay($row->co).'</span> - <a href="'.$link.'">'.$row->title.'</a>';
				else
					$out->articulos[$d][$month][] = '<a href="'.$link.'">'.$row->title.'</a>';
				$out->years[$d] = isset($out->years[$d]) ? $out->years[$d]+1 : 1;
				if (!isset($out->meses[$d])) $out->meses[$d] = array();
				$out->meses[$d][$month] = isset($out->meses[$d][$month]) ? $out->meses[$d][$month]+1 : 1;
			}
		}
		return $out;
	}
	
	private function getItemid() {
		$db = JFactory::getDBO();
		if ($this->j15) {
			$db->setQuery('SELECT id FROM #__menu WHERE link="index.php?option=com_content&view=frontpage" AND home=1');
			$id = $db->loadResult();
			if ($id) return $id;
			$db->setQuery('SELECT id FROM #__menu WHERE link="index.php?option=com_content&view=frontpage" AND access=0 AND published=1 ORDER BY id DESC LIMIT 1');
			$id = $db->loadResult();
			if ($id) return $id;
			$db->setQuery('SELECT id FROM #__menu WHERE link LIKE "index.php?option=com_content%" AND access=0 AND published=1 ORDER BY id DESC LIMIT 1');
			$id = $db->loadResult();
		}
		else {
			$db->setQuery('SELECT id FROM #__menu WHERE link="index.php?option=com_content&view=featured" AND home=1');
			$id = $db->loadResult();
			if ($id) return $id;
			$db->setQuery('SELECT id FROM #__menu WHERE link="index.php?option=com_content&view=featured" AND access<=1 AND published=1 ORDER BY id DESC LIMIT 1');
			$id = $db->loadResult();
			if ($id) return $id;
			$db->setQuery('SELECT id FROM #__menu WHERE link LIKE "index.php?option=com_content%" AND access<=1 AND published=1 ORDER BY id DESC LIMIT 1');
			$id = $db->loadResult();
		}
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
	private function getMonths($desc) {
		$months = array();
		if ($desc) {
			for ($i=12;$i>0;$i--)
				$months[$this->monthToString($i)] = array();
		}
		else {
			for ($i=1;$i<=12;$i++)
				$months[$this->monthToString($i)] = array();
		}	
		return $months;
	}
	private function monthToString($month) {
		$data = array('', JText::_('JANUARY'), JText::_('FEBRUARY'), JText::_('MARCH'), JText::_('APRIL'), JText::_('MAY'), JText::_('JUNE'),
					JText::_('JULY'), JText::_('AUGUST'), JText::_('SEPTEMBER'), JText::_('OCTOBER'), JText::_('NOVEMBER'), JText::_('DECEMBER'));
		return $data[(int)$month];
	}
}