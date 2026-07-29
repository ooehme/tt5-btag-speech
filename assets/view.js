(function () {
	'use strict';

	function createPlayer(button) {
		const source = button.dataset.mdbSrc;

		if (!source) {
			return;
		}

		const player = document.createElement('video');
		player.preload = 'metadata';
		player.playsInline = true;
		player.controls = button.dataset.mdbControls === '1';
		player.autoplay = button.dataset.mdbAutoplay === '1';
		player.muted = button.dataset.mdbMuted === '1';
		if (button.dataset.mdbPoster) {
			player.poster = button.dataset.mdbPoster;
		}
		if (button.dataset.mdbSubtitle) {
			const track = document.createElement('track');
			track.kind = 'subtitles';
			track.src = button.dataset.mdbSubtitle;
			track.srclang = 'de';
			track.label = 'Deutsch';
			track.default = true;
			player.appendChild(track);
		}

		player.src = source;
		button.replaceWith(player);
		if (player.autoplay) {
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
