(function (wp) {
	'use strict';

	const { InspectorControls } = wp.blockEditor;
	const { createHigherOrderComponent } = wp.compose;
	const { PanelBody, RangeControl, SelectControl, ToggleControl } = wp.components;
	const { createElement: el, Fragment } = wp.element;
	const { addFilter } = wp.hooks;
	const { __ } = wp.i18n;

	const namespace = 'mdb/speeches';
	const orderingOptions = [
		{ label: __('Neueste bis älteste', 'mdb-bundestag-speeches'), value: 'date-desc' },
		{ label: __('Älteste bis neueste', 'mdb-bundestag-speeches'), value: 'date-asc' },
		{ label: __('Titel: A bis Z', 'mdb-bundestag-speeches'), value: 'title-asc' },
		{ label: __('Titel: Z bis A', 'mdb-bundestag-speeches'), value: 'title-desc' },
	];

	function orderingValue(query) {
		const value = `${query.orderBy || 'date'}-${query.order || 'desc'}`;
		return orderingOptions.some((option) => option.value === value) ? value : 'date-desc';
	}

	const withSpeechQueryControls = createHigherOrderComponent(
		(BlockEdit) =>
			function SpeechQueryControls(props) {
				const { attributes, name, setAttributes } = props;

				if (name !== 'core/query' || attributes.namespace !== namespace) {
					return el(BlockEdit, props);
				}

				const query = attributes.query || {};
				const updateQuery = (changes) => {
					setAttributes({
						query: {
							...query,
							...changes,
						},
					});
				};

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
								title: __('Darstellung', 'mdb-bundestag-speeches'),
								initialOpen: true,
							},
							el(RangeControl, {
								label: __('Elemente pro Seite', 'mdb-bundestag-speeches'),
								value: query.perPage ?? 6,
								min: 1,
								max: 100,
								onChange: (perPage) => updateQuery({ perPage }),
							}),
							el(SelectControl, {
								label: __('Reihenfolge von', 'mdb-bundestag-speeches'),
								value: orderingValue(query),
								options: orderingOptions,
								onChange: (value) => {
									const [orderBy, order] = value.split('-');
									updateQuery({ orderBy, order });
								},
							}),
							el(RangeControl, {
								label: __('Offset', 'mdb-bundestag-speeches'),
								help: __(
									'0 beginnt mit der ersten Rede, 1 überspringt die erste Rede.',
									'mdb-bundestag-speeches'
								),
								value: query.offset ?? 0,
								min: 0,
								max: 100,
								onChange: (offset) => updateQuery({ offset }),
							}),
							el(ToggleControl, {
								label: __('Redner aus Titel entfernen', 'mdb-bundestag-speeches'),
								help: __(
									'Entfernt nur in dieser Darstellung den Suffix „: Rede von …“.',
									'mdb-bundestag-speeches'
								),
								checked: attributes.mdbRemoveSpeakerFromTitle ?? false,
								onChange: (mdbRemoveSpeakerFromTitle) =>
									setAttributes({ mdbRemoveSpeakerFromTitle }),
							}),
							el(ToggleControl, {
								label: __('Artikeltitel verwenden', 'mdb-bundestag-speeches'),
								help: __(
									'Verwendet den Titel des verlinkten Bundestag-Artikels; andernfalls bleibt der normale Titel erhalten.',
									'mdb-bundestag-speeches'
								),
								checked: attributes.mdbUseArticleTitle ?? false,
								onChange: (mdbUseArticleTitle) => setAttributes({ mdbUseArticleTitle }),
							}),
							el(ToggleControl, {
								label: __('Artikelbild als Thumbnail', 'mdb-bundestag-speeches'),
								help: __(
									'Verwendet das Artikelbild als Video-Vorschaubild, falls eines verfügbar ist.',
									'mdb-bundestag-speeches'
								),
								checked: attributes.mdbUseArticleImage ?? false,
								onChange: (mdbUseArticleImage) => setAttributes({ mdbUseArticleImage }),
							})
						)
					)
				);
			},
		'withSpeechQueryControls'
	);

	addFilter('editor.BlockEdit', 'mdb/speeches/query-controls', withSpeechQueryControls);
})(window.wp);
