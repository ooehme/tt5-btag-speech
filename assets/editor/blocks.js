(function (wp) {
	'use strict';

	const { registerBlockType } = wp.blocks;
	const { InspectorControls, useBlockProps } = wp.blockEditor;
	const { PanelBody, SelectControl, TextControl, ToggleControl } = wp.components;
	const { createElement: el, Fragment } = wp.element;
	const { __ } = wp.i18n;

	const videoAttributes = {
		display: { type: 'string', default: 'click_to_load' },
		controls: { type: 'boolean', default: true },
		autoplay: { type: 'boolean', default: false },
		muted: { type: 'boolean', default: false },
		aspectRatio: { type: 'string', default: '16/9' },
	};

	function videoEdit({ attributes, setAttributes }) {
		const blockProps = useBlockProps({ className: 'mdb-speech-video-editor' });
		return el(
			Fragment,
			null,
			el(
				InspectorControls,
				null,
				el(
					PanelBody,
					{ title: __('Videoeinstellungen', 'mdb-bundestag-speeches') },
					el(SelectControl, {
						label: __('Darstellung', 'mdb-bundestag-speeches'),
						value: attributes.display,
						options: [
							{ label: __('Direkt', 'mdb-bundestag-speeches'), value: 'direct' },
							{ label: __('Erst nach Klick laden', 'mdb-bundestag-speeches'), value: 'click_to_load' },
							{ label: __('Nur Link', 'mdb-bundestag-speeches'), value: 'link' },
						],
						onChange: (display) => setAttributes({ display }),
					}),
					el(SelectControl, {
						label: __('Seitenverhältnis', 'mdb-bundestag-speeches'),
						value: attributes.aspectRatio,
						options: ['16/9', '4/3', '1/1', '21/9'].map((value) => ({ label: value, value })),
						onChange: (aspectRatio) => setAttributes({ aspectRatio }),
					}),
					el(ToggleControl, {
						label: __('Steuerelemente', 'mdb-bundestag-speeches'),
						checked: attributes.controls,
						onChange: (controls) => setAttributes({ controls }),
					}),
					el(ToggleControl, {
						label: __('Automatisch abspielen', 'mdb-bundestag-speeches'),
						checked: attributes.autoplay,
						onChange: (autoplay) => setAttributes({ autoplay }),
					}),
					el(ToggleControl, {
						label: __('Stumm', 'mdb-bundestag-speeches'),
						checked: attributes.muted,
						onChange: (muted) => setAttributes({ muted }),
					})
				)
			),
			el(
				'div',
				blockProps,
				el('span', { className: 'dashicons dashicons-video-alt3', 'aria-hidden': true }),
				el('strong', null, __('Lokales Video der aktuellen Rede', 'mdb-bundestag-speeches')),
				el('small', null, `${attributes.display} · ${attributes.aspectRatio}`)
			)
		);
	}

	function fieldEdit(label) {
		return function Edit() {
			return el('p', useBlockProps(), label);
		};
	}

	registerBlockType('mdb/speech-video', {
		apiVersion: 3,
		title: __('Bundestag-Video', 'mdb-bundestag-speeches'),
		icon: 'video-alt3',
		category: 'mdb-speeches',
		attributes: videoAttributes,
		usesContext: ['postId', 'postType'],
		edit: videoEdit,
		save: () => null,
	});

	registerBlockType('mdb/speech-topic', {
		apiVersion: 3,
		title: __('Tagesordnungspunkt', 'mdb-bundestag-speeches'),
		icon: 'editor-ol',
		category: 'mdb-speeches',
		usesContext: ['postId', 'postType'],
		edit: fieldEdit(__('Tagesordnungspunkt der aktuellen Rede', 'mdb-bundestag-speeches')),
		save: () => null,
	});

	registerBlockType('mdb/speech-session', {
		apiVersion: 3,
		title: __('Sitzung', 'mdb-bundestag-speeches'),
		icon: 'groups',
		category: 'mdb-speeches',
		usesContext: ['postId', 'postType'],
		edit: fieldEdit(__('Sitzung der aktuellen Rede', 'mdb-bundestag-speeches')),
		save: () => null,
	});

	registerBlockType('mdb/speech-source-link', {
		apiVersion: 3,
		title: __('Bundestag-Quellenlink', 'mdb-bundestag-speeches'),
		icon: 'external',
		category: 'mdb-speeches',
		usesContext: ['postId', 'postType'],
		attributes: {
			label: { type: 'string', default: __('Originalquelle: Deutscher Bundestag', 'mdb-bundestag-speeches') },
			openInNewTab: { type: 'boolean', default: false },
		},
		edit: ({ attributes, setAttributes }) =>
			el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __('Linkeinstellungen', 'mdb-bundestag-speeches') },
						el(TextControl, {
							label: __('Linktext', 'mdb-bundestag-speeches'),
							value: attributes.label,
							onChange: (label) => setAttributes({ label }),
						}),
						el(ToggleControl, {
							label: __('In neuem Tab öffnen', 'mdb-bundestag-speeches'),
							checked: attributes.openInNewTab,
							onChange: (openInNewTab) => setAttributes({ openInNewTab }),
						})
					)
				),
				el('p', useBlockProps(), attributes.label)
			),
		save: () => null,
	});
})(window.wp);
