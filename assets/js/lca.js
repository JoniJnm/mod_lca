/**
* @Copyright Copyright (C) 2013 - JoniJnm.es
* @license GNU/GPL http://www.gnu.org/copyleft/gpl.html
**/

var lca = {
	months_opened: [],
	years_opened: [],
	open: function(id) {
		if (typeof(document.getElementById(id).src) == 'undefined')
			document.getElementById(id).innerHTML = LCA_TEXT_EXPAND;
		else
			document.getElementById(id).src = LCA_IMG_EXPAND;
	},
	close: function(id) {
		if (typeof(document.getElementById(id).src) == 'undefined')
			document.getElementById(id).innerHTML = LCA_TEXT_COLLAPSE;
		else
			document.getElementById(id).src = LCA_IMG_COLLAPSE;
	},
	f: function(n, id, modid) {
		var li = "lca_"+modid+"_"+n+"_"+id;
		var a = "lca_"+modid+"_"+n+"a_"+id;
		if (document.getElementById(li)) {
			if (document.getElementById(li).style.display == "none") {
				document.getElementById(li).style.display = "";
				document.cookie = 'lca'+modid+"_"+n+'='+id+";path=/";
				lca.open(a);
			}
			else {
				document.getElementById(li).style.display = "none";
				document.cookie = 'lca'+modid+"_"+n+'=0;expires=0;path=/';
				lca.close(a);
			}
		}
		if (!lca.months_opened[modid]) lca.months_opened[modid] = 0;
		if (!lca.years_opened[modid]) lca.years_opened[modid] = 0;
		var opened = n==1 ? lca.months_opened[modid] : lca.years_opened;
		li = 'lca_'+modid+'_'+n+'_'+opened;
		if (document.getElementById(li) && ((n==1 && lca.months_opened[modid] != id) || (n!=1 && lca.years_opened[modid] != id))) {
			document.getElementById(li).style.display = "none";
			lca.close("lca_"+modid+"_"+n+"a_"+opened);
		}
		if (n == 1)
			lca.months_opened[modid] = id;
		else
			lca.years_opened[modid] = id;
	}
}
