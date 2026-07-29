(function () {
	'use strict';

	const speakerId = document.getElementById('mdb-speaker-id');
	const speakerFilter = document.getElementById('mdb-speaker-filter');
	const speakerOptions = document.getElementById('mdb-speaker-options');
	if (speakerId && speakerFilter && speakerOptions) {
		speakerId.addEventListener('change', function () {
			const selected = Array.from(speakerOptions.options).find(function (option) {
				return option.value === speakerId.value.trim();
			});
			speakerFilter.value = selected && selected.dataset.filterIds
				? selected.dataset.filterIds
				: speakerId.value.trim();
		});
	}

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
