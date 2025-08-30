/*
  @license
	Rollup.js v4.49.0
	Wed, 27 Aug 2025 07:24:52 GMT - commit b12c061d27d63062b91c1830a698de53fd6c2067

	https://github.com/rollup/rollup

	Released under the MIT License.
*/
export { version as VERSION, defineConfig, rollup, watch } from './shared/node-entry.js';
import './shared/parseAst.js';
import '../native.js';
import 'node:path';
import 'path';
import 'node:process';
import 'node:perf_hooks';
import 'node:fs/promises';
