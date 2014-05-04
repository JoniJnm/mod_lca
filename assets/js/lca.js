/**
* @Copyright Copyright (C) 2014 - JoniJnm.es
* @license GNU/GPL http://www.gnu.org/copyleft/gpl.html
**/

var lca = {
	month_opened: 0,
	year_opened: 0,
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
	f: function(n, id) {
		var li = "lca_"+n+"_"+id;
		var a = "lca_"+n+"a_"+id;
		if (document.getElementById(li)) {
			if (document.getElementById(li).style.display == "none") {
				document.getElementById(li).style.display = "";
				document.cookie = 'lca'+n+'='+id+";path=/";
				lca.open(a);
			}
			else {
				document.getElementById(li).style.display = "none";
				document.cookie = 'lca'+n+'=0;expires=0;path=/';
				lca.close(a);
			}
		}
		var opened = n==1 ? lca.month_opened : lca.year_opened;
		li = 'lca_'+n+'_'+opened;
		if (document.getElementById(li) && ((n==1 && lca.month_opened != id) || (n!=1 && lca.year_opened != id))) {
			document.getElementById(li).style.display = "none";
			lca.close("lca_"+n+"a_"+opened);
		}
		if (n == 1)
			lca.month_opened = id;
		else
			lca.year_opened = id;
	},
	onLoad: function(func) {
		if (window.addEventListener) window.addEventListener("load", func, false);
		else if (window.attachEvent) window.attachEvent("onload", func);
		else (func)();
	}
};
