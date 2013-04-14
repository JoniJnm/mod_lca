<?php 

/**
* @Copyright Copyright (C) 2012 - JoniJnm.es
* @license GNU/GPL http://www.gnu.org/copyleft/gpl.html
**/

defined( '_JEXEC' ) or die( 'Restricted access' ); 

if ($cache->check()) {
	$cache->show();
}
else {
	$cache->start();
	$modid = rand(0, 9999);
	$o_year = $params->get("o_year", "desc") == "desc";
	$o_month = $params->get("o_month") == "desc";
	$show_number = $params->get("show_number", 1);
	$search = 0;
	$img = $params->get("img", 0);
	$collapse = $helper->getImg($img);
	$collapse = $collapse->collapse;
	$iyear = 1;
	$imonth = 1;

	echo '<ul class="lca">';
	foreach ($data->articulos as $year=>$months) {
		echo '<li class="lca">';
			echo '<span onclick="lca.f(0,'.$iyear.','.$modid.')" class="lca">';
			if ($img)
				echo '<img id="lca_'.$modid.'_0a_'.$iyear.'" class="lca" src="'.$collapse.'" alt="" />';
			else 
				echo '<span id="lca_'.$modid.'_0a_'.$iyear.'">'.$collapse.'</span>';
			echo ' '.$year.'</span>';
			if ($show_number)
				echo ' ('.$data->years[$year].')';
			echo '<ul class="lca" id="lca_'.$modid.'_0_'.$iyear.'" style="display: none">';
			foreach ($months as $month=>$articles) {
				if (count($articles)) {
					if ($o_year) {
						if ($o_month)
							$search = 1;
						elseif ($iyear == 1)
							$search = $imonth;
					}
					elseif ($iyear == count($data->articulos) && (!$o_month || !$search))
						$search = $imonth;	
					echo '<li class="lca">';
						echo '<span onclick="lca.f(1,'.$imonth.','.$modid.')" class="lca">';
						if ($img)
							echo '<img id="lca_'.$modid.'_1a_'.$imonth.'" class="lca" src="'.$collapse.'" alt="" />';
						else
							echo '<span id="lca_'.$modid.'_1a_'.$imonth.'">'.$collapse.'</span>';
						echo ' '.$month.'</span>';
						if ($show_number)
							echo ' ('.$data->meses[$year][$month].')';
						echo '<ul class="lca" id="lca_'.$modid.'_1_'.$imonth.'" style="display: none">';
						foreach ($articles as $article)
							 echo '<li class="lca">• '.$article.'</li>';
						echo '</ul>';
					echo '</li>';
					$imonth++;
				}
			}
			echo '</ul>';
		echo '</li>';
		$iyear++;
	}
	echo '</ul>';
	$cache->modid = $modid;
	$cache->show = ($o_year?1:count($data->articulos)).','.$search;
	$cache->end();
}

if ($params->get('allways_collapsed', 0)) {
	$show = array(array(), array());
}
else {
	$tmp = JRequest::getInt('lca'.$cache->modid.'_0', 0, 'COOKIE');
	if ($tmp > 0) {
		$show = array(
			array($tmp),
			array(JRequest::getInt('lca'.$cache->modid.'_1', 0, 'COOKIE'))
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
lca.onLoad(function() {
foreach ($show[0] as $s) {
	$s = (int)$s;
	if ($s > 0) 
		echo "		lca.f(0,".$s.",".$cache->modid.");\n"; 
}
foreach ($show[1] as $s) {
	$s = (int)$s;
	if ($s > 0)
		echo "		lca.f(1,".$s.",".$cache->modid.");\n"; 
}
});
echo "\n</script>\n";
