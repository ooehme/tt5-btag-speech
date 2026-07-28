(function (wp) {
	'use strict';

	const { registerBlockVariation } = wp.blocks;
	const { __ } = wp.i18n;

	registerBlockVariation('core/query', {
		name: 'mdb/speeches',
		title: __('Bundestagsreden', 'mdb-bundestag-speeches'),
		description: __('Frei gestaltbare Liste synchronisierter Bundestagsreden.', 'mdb-bundestag-speeches'),
		icon: 'video-alt3',
		attributes: {
			namespace: 'mdb/speeches',
			query: {
				perPage: 6,
				pages: 0,
				offset: 0,
				postType: 'mdb_speech',
				order: 'desc',
				orderBy: 'date',
				author: '',
				search: '',
				exclude: [],
				sticky: '',
				inherit: false,
			},
		},
		innerBlocks: [
			[
				'core/post-template',
				{},
				[
					['mdb/speech-video', { source: 'auto', display: 'click_to_load' }],
					['core/post-title', { isLink: true }],
					['core/post-date'],
					['mdb/speech-topic'],
					['mdb/speech-source-link'],
				],
			],
			[
				'core/query-pagination',
				{},
				[
					['core/query-pagination-previous'],
					['core/query-pagination-numbers'],
					['core/query-pagination-next'],
				],
			],
			['core/query-no-results', {}, [['core/paragraph', { content: __('Keine Bundestagsreden gefunden.', 'mdb-bundestag-speeches') }]]],
		],
		allowedControls: ['order', 'search'],
		scope: ['inserter'],
		isActive: (attributes) => attributes.namespace === 'mdb/speeches',
	});
})(window.wp);
