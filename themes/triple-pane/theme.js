function themeBeforeLayout() {
	if ($("content-insert")) {
		$("headlines-wrap-inner").setAttribute("design", 'sidebar');
		$("content-insert").setAttribute("region", "trailing");
		$("content-insert").setStyle({
			width: '50%',
			height: 'auto'});
	}
}

function themeAfterLayout() {
	$("headlines-toolbar").setStyle({
		'border-width': '1px 1px 0px 0px',
		'border-color': '#88b0f0',
		});
}

function resize_wide_headlines() {
	try {
		var rows = $$("div[id*=RROW]");

		for (var i = 0; i < rows.length; i++) {
			if (rows[i].offsetLeft == 2 && rows[i+1] != 2) {
				for (var j = i+1; j < rows.length; j++) {

//					if (rows[i].offsetHeight < rows[j].offsetHeight)
//						rows[i].insertAdjacentElement('beforebegin', rows[j]);

					if (rows[j].offsetLeft == 2) break;
				}

				if (rows[j-1]) {
					var dh = (rows[j-1].offsetHeight + rows[j-1].offsetTop) -
						(rows[i].offsetHeight + rows[i].offsetTop);

					if (dh > 0) {
						rows[i].style.height = (rows[i].offsetHeight + dh - 2) + 'px';


					}
				}
			}
		}

	} catch (e) {
		exception_error("resize_wide_headlines", e);
	}
}
