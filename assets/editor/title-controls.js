(function (wp) {
	'use strict';

	const { InspectorControls } = wp.blockEditor;
	const { createHigherOrderComponent } = wp.compose;
	const { PanelBody, ToggleControl } = wp.components;
	const { createElement: el, Fragment } = wp.element;
	const { addFilter } = wp.hooks;
	const { __ } = wp.i18n;

	const removeSpeaker = 'mdb-speech-title--remove-speaker';
	const keepSpeaker = 'mdb-speech-title--keep-speaker';
	const articleTitle = 'mdb-speech-title--article-title';
	const sourceTitle = 'mdb-speech-title--source-title';

	function classes(className) {
		return (className || '').split(/\s+/).filter(Boolean);
	}

	function toggleClass(className, enabledClass, disabledClass, enabled) {
		const next = classes(className).filter(
			(value) => value !== enabledClass && value !== disabledClass
		);
		next.push(enabled ? enabledClass : disabledClass);
		return [...new Set(next)].join(' ');
	}

	const withSpeechTitleControls = createHigherOrderComponent(
		(BlockEdit) =>
			function SpeechTitleControls(props) {
				if (props.name !== 'core/post-title') {
					return el(BlockEdit, props);
				}

				const { attributes, setAttributes } = props;
				const classNames = classes(attributes.className);
				const speechContext = props.context?.postType === 'mdb_speech';
				const removeSpeakerChecked =
					classNames.includes(removeSpeaker) ||
					(speechContext &&
						!classNames.includes(removeSpeaker) &&
						!classNames.includes(keepSpeaker));
				const articleTitleChecked =
					classNames.includes(articleTitle) ||
					(speechContext &&
						!classNames.includes(articleTitle) &&
						!classNames.includes(sourceTitle));

				return el(
					Fragment,
					null,
					el(BlockEdit, props),
					el(
						InspectorControls,
						null,
						el(
							PanelBody,
							{
								title: __('Bundestagsrede', 'mdb-bundestag-speeches'),
								initialOpen: true,
							},
							el(ToggleControl, {
								label: __('Redner aus Titel entfernen', 'mdb-bundestag-speeches'),
								checked: removeSpeakerChecked,
								onChange: (enabled) =>
									setAttributes({
										className: toggleClass(
											attributes.className,
											removeSpeaker,
											keepSpeaker,
											enabled
										),
									}),
							}),
							el(ToggleControl, {
								label: __('Artikeltitel verwenden', 'mdb-bundestag-speeches'),
								checked: articleTitleChecked,
								onChange: (enabled) =>
									setAttributes({
										className: toggleClass(
											attributes.className,
											articleTitle,
											sourceTitle,
											enabled
										),
									}),
							})
						)
					)
				);
			},
		'withSpeechTitleControls'
	);

	addFilter('editor.BlockEdit', 'mdb/speeches/title-controls', withSpeechTitleControls);
})(window.wp);
