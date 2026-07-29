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
	'assets/editor.js',
].forEach((file) => {
	vm.runInThisContext(fs.readFileSync(path.join(root, file), 'utf8'), { filename: file });
});

assert.equal(blocks.size, 4, 'all dynamic blocks are registered');
assert.equal(typeof blocks.get('mdb/speech-video').edit, 'function');
assert.equal(blocks.get('mdb/speech-video').attributes.source, undefined);
assert.equal(blocks.get('mdb/speech-video').attributes.poster, undefined);

const query = variations.find(({ block, settings }) => block === 'core/query' && settings.name === 'mdb/speeches');
assert.ok(query, 'query variation is registered');
assert.equal(query.settings.attributes.query.postType, 'mdb_speech');
assert.equal(query.settings.attributes.query.orderBy, 'date');
assert.equal(query.settings.innerBlocks[0][0], 'core/post-template');
assert.deepEqual(query.settings.innerBlocks[0][2][0], [
	'mdb/speech-video',
	{ display: 'click_to_load' },
]);
assert.deepEqual(query.settings.innerBlocks[0][2][1], [
	'core/post-title',
	{ isLink: true },
]);
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

blocks.get('mdb/speech-video').edit({
	attributes: {
		display: 'click_to_load',
		controls: true,
		autoplay: false,
		muted: false,
		aspectRatio: '16/9',
	},
	setAttributes: noop,
});
assert.ok(
	!elements.some((element) => element.props.label === 'Quelle'),
	'video source cannot be changed to an embed'
);
assert.ok(
	!elements.some((element) => element.props.label === 'Artikelbild als Thumbnail'),
	'featured image is the fixed video poster source'
);

assert.equal(
	variations.filter(({ block }) => block === 'mdb/speech-video').length,
	0,
	'no embed video variation is registered'
);

global.document = { addEventListener: noop };
vm.runInThisContext(fs.readFileSync(path.join(root, 'assets/view.js'), 'utf8'), {
	filename: 'assets/view.js',
});

console.log(`OK (${blocks.size} blocks, ${variations.length} variations)`);
