<?php
/**
* @Copyright Copyright (C) 2013 - JoniJnm.es
* @license GNU/GPL http://www.gnu.org/copyleft/gpl.html
**/
defined ( '_JEXEC' ) or die ();

class Mod_LcaInstallerScript {
	function postflight($type, $parent) {
		$path = $parent->getParent()->getPath('extension_root');
		$name = $parent->get('name');
		if (JFile::exists("{$path}/{$name}.j16.xml")) {
			if ( JFile::exists("{$path}/{$name}.xml")) JFile::delete("{$path}/{$name}.xml");
			JFile::move("{$path}/{$name}.j16.xml", "{$path}/{$name}.xml");
		}
	}
}