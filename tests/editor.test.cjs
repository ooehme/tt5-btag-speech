const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const root = path.dirname(__dirname);
const blocks = new Map();
const variations = [];
const filters = new Map();
const noop = () => {};
const elements = [];

function createElement(type, props, ...children) {
	const element = { type, props: props || {}, children };
	elements.push(element);
	return element;
}

const InspectorControls = Symbol('InspectorControls');
const PanelBody = Symbol('PanelBody');
const RangeControl = Symbol('RangeControl');
const SelectControl = Symbol('SelectControl');
const ToggleControl = Symbol('ToggleControl');

global.window = {
	wp: {
		blockEditor: {
			InspectorControls,
			useBlockProps: () => ({}),
		},
		blocks: {
			registerBlockType: (name, settings) => blocks.set(name, settings),
			registerBlockVariation: (block, settings) => variations.push({ block, settings }),
		},
		compose: {
			createHigherOrderComponent: (enhancer) => enhancer,
		},
		components: {
			PanelBody,
			RangeControl,
			SelectControl,
			TextControl: noop,
			ToggleControl,
		},
		element: {
			createElement,
			Fragment: Symbol('Fragment'),
		},
		hooks: {
			addFilter: (hook, namespace, callback) => filters.set(namespace, { hook, callback }),
		},
		i18n: {
			__: (value) => value,
		},
	},
};

[
	'assets/editor/blocks.js',
	'assets/editor/query.js',
	'assets/editor/query-controls.js',
	'assets/editor/title-controls.js',
	'assets/editor.js',
].forEach((file) => {
	vm.runInThisContext(fs.readFileSync(path.join(root, file), 'utf8'), { filename: file });
});

assert.equal(blocks.size, 4, 'all dynamic blocks are registered');
assert.equal(typeof blocks.get('mdb/speech-video').edit, 'function');
assert.equal(blocks.get('mdb/speech-video').attributes.source.default, 'auto');

const query = variations.find(({ block, settings }) => block === 'core/query' && settings.name === 'mdb/speeches');
assert.ok(query, 'query variation is registered');
assert.equal(query.settings.attributes.query.postType, 'mdb_speech');
assert.equal(query.settings.attributes.query.orderBy, 'date');
assert.equal(query.settings.innerBlocks[0][0], 'core/post-template');
assert.equal(query.settings.innerBlocks[0][2][0][0], 'mdb/speech-video');
assert.equal(query.settings.innerBlocks[0][2][0][1].useArticleImage, true);
assert.ok(
	query.settings.innerBlocks[0][2][1][1].className.includes(
		'mdb-speech-title--remove-speaker'
	)
);
assert.ok(
	query.settings.innerBlocks[0][2][1][1].className.includes(
		'mdb-speech-title--article-title'
	)
);
assert.deepEqual(query.settings.allowedControls, ['search']);

const controlsFilter = filters.get('mdb/speeches/query-controls');
assert.equal(controlsFilter.hook, 'editor.BlockEdit');

const attributeChanges = [];
const SpeechQueryEdit = controlsFilter.callback(noop);
SpeechQueryEdit({
	name: 'core/query',
	attributes: {
		namespace: 'mdb/speeches',
		query: query.settings.attributes.query,
	},
	setAttributes: (changes) => attributeChanges.push(changes),
});

const perPageControl = elements.find(
	(element) => element.type === RangeControl && element.props.label === 'Elemente pro Seite'
);
const orderingControl = elements.find(
	(element) => element.type === SelectControl && element.props.label === 'Reihenfolge von'
);
const offsetControl = elements.find(
	(element) => element.type === RangeControl && element.props.label === 'Offset'
);
assert.ok(perPageControl, 'items-per-page slider is rendered');
assert.ok(orderingControl, 'ordering dropdown is rendered');
assert.ok(offsetControl, 'offset slider is rendered');

perPageControl.props.onChange(12);
orderingControl.props.onChange('title-asc');
offsetControl.props.onChange(1);

assert.equal(attributeChanges[0].query.perPage, 12);
assert.equal(attributeChanges[1].query.orderBy, 'title');
assert.equal(attributeChanges[1].query.order, 'asc');
assert.equal(attributeChanges[2].query.offset, 1);

const titleControlsFilter = filters.get('mdb/speeches/title-controls');
assert.equal(titleControlsFilter.hook, 'editor.BlockEdit');

const titleAttributeChanges = [];
const SpeechTitleEdit = titleControlsFilter.callback(noop);
SpeechTitleEdit({
	name: 'core/post-title',
	attributes: query.settings.innerBlocks[0][2][1][1],
	setAttributes: (changes) => titleAttributeChanges.push(changes),
});

const titleControl = elements.find(
	(element) => element.type === ToggleControl && element.props.label === 'Redner aus Titel entfernen'
);
const articleTitleControl = elements.find(
	(element) => element.type === ToggleControl && element.props.label === 'Artikeltitel verwenden'
);
assert.ok(titleControl, 'speaker-title toggle is attached to the title block');
assert.ok(articleTitleControl, 'article-title toggle is attached to the title block');
assert.equal(titleControl.props.checked, true);
assert.equal(articleTitleControl.props.checked, true);

titleControl.props.onChange(false);
articleTitleControl.props.onChange(false);
assert.ok(titleAttributeChanges[0].className.includes('mdb-speech-title--keep-speaker'));
assert.ok(!titleAttributeChanges[0].className.includes('mdb-speech-title--remove-speaker'));
assert.ok(titleAttributeChanges[1].className.includes('mdb-speech-title--source-title'));
assert.ok(!titleAttributeChanges[1].className.includes('mdb-speech-title--article-title'));

const videoAttributeChanges = [];
blocks.get('mdb/speech-video').edit({
	attributes: {
		...query.settings.innerBlocks[0][2][0][1],
		controls: true,
		autoplay: false,
		muted: false,
		poster: '',
		aspectRatio: '16/9',
	},
	setAttributes: (changes) => videoAttributeChanges.push(changes),
});
const articleImageControl = elements.find(
	(element) => element.type === ToggleControl && element.props.label === 'Artikelbild als Thumbnail'
);
assert.ok(articleImageControl, 'article-image toggle is attached to the video block');
assert.equal(articleImageControl.props.checked, true);
articleImageControl.props.onChange(false);
assert.equal(videoAttributeChanges[0].useArticleImage, false);

const videoVariations = variations.filter(({ block }) => block === 'mdb/speech-video');
assert.equal(videoVariations.length, 3, 'all video variations are registered');
assert.ok(!blocks.get('mdb/speech-video').usesContext.includes('mdb/useArticleImage'));

global.document = { addEventListener: noop };
vm.runInThisContext(fs.readFileSync(path.join(root, 'assets/view.js'), 'utf8'), {
	filename: 'assets/view.js',
});

console.log(`OK (${blocks.size} blocks, ${variations.length} variations)`);
