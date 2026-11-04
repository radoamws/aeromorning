/* Admin > Statistiques: show the month picker or the date fields. */
(function () {
	var form = document.querySelector('.ams-filters');
	var mode = document.getElementById('ams-mode');
	if (!form || !mode) {
		return;
	}
	var sync = function () {
		form.setAttribute('data-mode', mode.value);
	};
	mode.addEventListener('change', sync);
	sync();

	// Printing: open the "show the others" lists so the PDF is complete.
	window.addEventListener('beforeprint', function () {
		document.querySelectorAll('.ams-more').forEach(function (d) {
			d.setAttribute('open', '');
		});
	});
})();
