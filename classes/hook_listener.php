<?php
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

namespace local_importmapshim;

/**
 * Hook listener for local_importmapshim.
 *
 * @package   local_importmapshim
 * @copyright Meirza <meirza.arson@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_listener {
    /**
     * Inject the es-module-shims polyfill script before top-of-body module scripts.
     *
     * @param \core\hook\output\before_standard_top_of_body_html_generation $hook
     */
    public static function inject_esm_shims(
        \core\hook\output\before_standard_top_of_body_html_generation $hook,
    ): void {
        global $CFG;

        $jsrev = empty($CFG->cachejs) ? -1 : (empty($CFG->jsrev) ? 1 : $CFG->jsrev);

        $path = \core\router\util::get_path_for_callable(
            [\local_importmapshim\route\controller\shims_controller::class, 'serve_shims'],
            ['revision' => $jsrev],
        );

        $hook->add_html(
            \core\output\html_writer::tag(
                tagname: 'script',
                contents: '',
                attributes: ['src' => $path->out()],
            ) . "\n"
        );
    }
}
