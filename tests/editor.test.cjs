const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const root = path.dirname(__dirname);
const blocks = new Map();
const variations = [];
const noop = () => {};

global.window = {
	wp: {
		blockEditor: {
			InspectorControls: noop,
			useBlockProps: () => ({}),
		},
		blocks: {
			registerBlockType: (name, settings) => blocks.set(name, settings),
			registerBlockVariation: (block, settings) => variations.push({ block, settings }),
		},
		components: {
			PanelBody: noop,
			SelectControl: noop,
			TextControl: noop,
			ToggleControl: noop,
		},
		element: {
			createElement: noop,
			Fragment: Symbol('Fragment'),
		},
		i18n: {
			__: (value) => value,
		},
	},
};

['assets/editor/blocks.js', 'assets/editor/query.js', 'assets/editor.js'].forEach((file) => {
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

const videoVariations = variations.filter(({ block }) => block === 'mdb/speech-video');
assert.equal(videoVariations.length, 3, 'all video variations are registered');

global.document = { addEventListener: noop };
vm.runInThisContext(fs.readFileSync(path.join(root, 'assets/view.js'), 'utf8'), {
	filename: 'assets/view.js',
});

console.log(`OK (${blocks.size} blocks, ${variations.length} variations)`);
