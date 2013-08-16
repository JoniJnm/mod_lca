<?php

/**
* @Copyright Copyright (C) 2012 - JoniJnm.es
* @license GNU/GPL http://www.gnu.org/copyleft/gpl.html
**/

defined('_JEXEC') or die('Restricted access');

if (DEFINED("LCA_SHOWS")) {
    echo JText::_('MOD_LCA_BUY_PRO_FOR_MULTIPLE_INSTANCES');
    return;
}

require_once(dirname(__FILE__).'/helper.php');
require_once(dirname(__FILE__).'/cache.php');

$cache = new modLcaCache($params);
$helper = new modLcaHelper($params);
$cache->init();

if (!$cache->check()) {
	$data = $helper->getList();
	if (!$data) return;
}
define('LCA_SHOWS', 1);
$helper->addTags();
require(JModuleHelper::getLayoutPath('mod_lca'));
