(function () {
	'use strict';

	const form = document.getElementById('mdb-speeches-sync-form');
	const progress = document.getElementById('mdb-speeches-sync-progress');
	if (!form || !progress) {
		return;
	}

	form.addEventListener('submit', function () {
		const submit = form.querySelector('[type="submit"]');
		progress.hidden = false;
		form.setAttribute('aria-busy', 'true');
		if (submit) {
			submit.disabled = true;
			submit.value = form.dataset.progressLabel || submit.value;
		}
	});
})();
