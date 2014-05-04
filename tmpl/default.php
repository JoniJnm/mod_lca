<?php 

/**
* @Copyright Copyright (C) 2014 - JoniJnm.es
* @license GNU/GPL http://www.gnu.org/copyleft/gpl.html
**/

defined( '_JEXEC' ) or die( 'Restricted access' ); 

if ($cache->check()) {
	$cache->show();
}
else {
	$cache->start();
	$show_number = $params->get("show_number", 1);
	$search = 0;
	$img = $params->get("img", 0);
	$collapse = $helper->getImg($img);
	$collapse = $collapse->collapse;
	$isec = 1;
	$icat = 1;

	echo '<ul class="lca">';
	foreach ($data->articulos as $sec=>$cats) {
		echo '<li class="lca">';
			echo '<span onclick="lca.f(0,'.$isec.')" class="lca">';
			if ($img)
				echo '<img id="lca_0a_'.$isec.'" class="lca" src="'.$collapse.'" alt="" />';
			else 
				echo '<span id="lca_0a_'.$isec.'">'.$collapse.'</span>';
			echo ' '.$sec.'</span>';
			if ($show_number)
				echo ' ('.$data->secs[$sec].')';
			echo '<ul class="lca" id="lca_0_'.$isec.'" style="display: none">';
			if (!$data->cats) {
				//list1 | $cats => $articles;
				foreach ($cats as $article) {
					echo '<li class="lca">• '.$article.'</li>';
				}
			}
			else {
				foreach ($cats as $cat=>$articles) {
					if (!count($articles)) continue;
					if ($data->o_sec == 'desc') {
						if ($data->o_cat == 'desc')
						$search = 1;
						elseif ($isec == 1)
							$search = $icat;
					}
					elseif ($isec == count($data->articulos) && ($data->o_cat == 'asc' || !$search))
						$search = $icat;	
					echo '<li class="lca">';
						echo '<span onclick="lca.f(1,'.$icat.')" class="lca">';
						if ($img)
							echo '<img id="lca_1a_'.$icat.'" class="lca" src="'.$collapse.'" alt="" />';
						else
							echo '<span id="lca_1a_'.$icat.'">'.$collapse.'</span>';
						echo ' '.$cat.'</span>';
						if ($show_number)
							echo ' ('.$data->cats[$sec][$cat].')';
						echo '<ul class="lca" id="lca_1_'.$icat.'" style="display: none">';
						foreach ($articles as $article)
							 echo '<li class="lca">• '.$article.'</li>';
						echo '</ul>';
					echo '</li>';
					$icat++;
				}
			}
			echo '</ul>';
		echo '</li>';
		$isec++;
	}
	echo '</ul>';
	//buy pro version to hide copyright http://www.jonijnm.es/web/mod-lca.html
	echo '<div style="text-align:right;font-size:xx-small">Powered by <a title="Module LCA for Joomla" href="http://www.jonijnm.es">mod LCA</a></div>';
	$cache->show = ($data->o_sec=='desc'?1:count($data->articulos)).','.$search;
	$cache->end();
}

if ($params->get('allways_collapsed', 0)) {
	$show = array(array(), array());
}
else {
	$tmp = JRequest::getInt('lca0', '', 'COOKIE');
	if ($tmp > 0) {
		$show = array(
			array($tmp),
			array(JRequest::getInt('lca1', '', 'COOKIE'))
		);
	}
	else {
		$tmp = explode(',', $cache->show);
		$show = array(
			array($tmp[0]),
			array(isset($tmp[1]) ? $tmp[1] : 0)
		);
	}
}
	
echo "\n<script type=\"text/javascript\">\n";
echo "lca.onLoad(function() {\n";
foreach ($show[0] as $s) {
	$s = (int)$s;
	if ($s > 0) 
		echo "		lca.f(0,".$s.");\n"; 
}
foreach ($show[1] as $s) {
	$s = (int)$s;
	if ($s > 0)
		echo "		lca.f(1,".$s.");\n"; 
}
echo "\n});";
echo "\n</script>\n";
