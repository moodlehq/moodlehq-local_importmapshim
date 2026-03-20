// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Script to update the es-module-shims polyfill bundled with local_importmapshim.
 *
 * Usage:
 *   node scripts/update-esm-shims.mjs <version>
 *
 * Example:
 *   node scripts/update-esm-shims.mjs 2.7.0
 *
 * @copyright  Meirza <meirza.arson@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import path from 'path';
import { fileURLToPath } from 'url';
import {
    download,
    updateThirdPartyLibsXml,
} from '../../../../scripts/lib/util.mjs';

const pluginDir = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const jsDir = path.join(pluginDir, 'js');
const filePath = path.join(jsDir, 'es-module-shims.js');

const version = process.argv[2];

if (!version) {
    console.error('Error: version argument is required.');
    console.error('Usage: node scripts/update-esm-shims.mjs <version>');
    console.error('Example: node scripts/update-esm-shims.mjs 2.7.0');
    process.exit(1);
}

const url = `https://ga.jspm.io/npm:es-module-shims@${version}/dist/es-module-shims.js`;

async function init() {
    console.log('Updating es-module-shims to version %s', version);

    console.log('✓ Downloading es-module-shims');
    await download(url, filePath);

    console.log('✓ Updating thirdpartylibs.xml');
    updateThirdPartyLibsXml(pluginDir, 'js', 'esm-shims', version);

    console.log('✓ The ESM module shims saved to ' + jsDir.replace(pluginDir, '[PLUGIN]'));
    console.log('Done!');
}

init().catch((err) => {
    console.error('Download failed:', err.message);
    process.exit(1);
});
