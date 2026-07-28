(function () {
	'use strict';

	function createPlayer(button) {
		const kind = button.dataset.mdbKind;
		let source = button.dataset.mdbSrc;
		let player;

		if (!source) {
			return;
		}
		if (kind !== 'local' && button.dataset.mdbAutoplay === '1') {
			const url = new URL(source, window.location.href);
			url.searchParams.set('autoplay', '1');
			source = url.toString();
		}

		if (kind === 'local') {
			player = document.createElement('video');
			player.preload = 'metadata';
			player.playsInline = true;
			player.controls = button.dataset.mdbControls === '1';
			player.autoplay = button.dataset.mdbAutoplay === '1';
			player.muted = button.dataset.mdbMuted === '1';
			if (button.dataset.mdbPoster) {
				player.poster = button.dataset.mdbPoster;
			}
		} else {
			player = document.createElement('iframe');
			player.title = button.dataset.mdbTitle || 'Bundestagsrede';
			player.loading = 'lazy';
			player.allowFullscreen = true;
			player.referrerPolicy = 'origin';
			player.allow = 'geolocation; autoplay; fullscreen';
			player.setAttribute('sandbox', 'allow-same-origin allow-scripts allow-forms allow-modals allow-popups');
		}

		player.src = source;
		button.replaceWith(player);
		if (kind === 'local' && player.autoplay) {
			const playback = player.play();
			if (playback && typeof playback.catch === 'function') {
				playback.catch(function () {});
			}
		}
	}

	document.addEventListener('click', function (event) {
		const button = event.target.closest('.mdb-speech-video__load');
		if (button) {
			createPlayer(button);
		}
	});
})();
