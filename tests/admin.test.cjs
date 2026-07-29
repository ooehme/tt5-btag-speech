const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const listeners = {};
const speakerId = {
	value: '12404',
	addEventListener: (event, callback) => {
		listeners[event] = callback;
	},
};
const speakerFilter = { value: '' };
const speakerOptions = {
	options: [
		{
			value: '12404',
			dataset: { filterIds: '21244 OR 12404' },
		},
	],
};

global.document = {
	getElementById: (id) => ({
		'mdb-speaker-id': speakerId,
		'mdb-speaker-filter': speakerFilter,
		'mdb-speaker-options': speakerOptions,
	}[id] || null),
};

vm.runInThisContext(
	fs.readFileSync(path.join(path.dirname(__dirname), 'assets/admin.js'), 'utf8'),
	{ filename: 'assets/admin.js' }
);

listeners.change();
assert.equal(speakerFilter.value, '21244 OR 12404');

speakerId.value = '99999';
listeners.change();
assert.equal(speakerFilter.value, '99999');

console.log('OK (Rednerfilter-Autofill)');
