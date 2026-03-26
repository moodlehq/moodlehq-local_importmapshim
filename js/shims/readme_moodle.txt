This directory contains the bundled es-module-shims polyfill served by local_importmapshim.

To upgrade the polyfill to a newer version, run the helper script from the Moodle root:

  node local/importmapshim/scripts/update-esm-shims.mjs <version>

Example:

  node local/importmapshim/scripts/update-esm-shims.mjs 2.7.0

This will:
  1. Download the specified release of es-module-shims from the jspm CDN.
  2. Replace js/es-module-shims.js with the new build.
  3. Update the version entry in thirdpartylibs.xml.
