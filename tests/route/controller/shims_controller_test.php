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

namespace local_importmapshim\route\controller;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for the shims controller.
 *
 * @package   local_importmapshim
 * @category  test
 * @copyright 2026 Moodle Pty Ltd
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(shims_controller::class)]
final class shims_controller_test extends \advanced_testcase {
    /** @var int A fixed point in time to make cache headers deterministic. */
    private const FROZEN_TIME = 1735689600;

    /**
     * Reset the DI container after each test.
     */
    #[After]
    public function reset_container(): void {
        \core\di::reset_container();
    }

    public function test_serve_shims_invalid_revision_uses_short_cache_headers(): void {
        $clock = $this->mock_clock_with_frozen(self::FROZEN_TIME);
        $controller = new shims_controller($clock);

        $response = $controller->serve_shims(
            new ServerRequest('GET', '/shims/0'),
            new Response(),
            0,
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertFalse($response->hasHeader('ETag'));
        $this->assertFalse($response->hasHeader('Cache-Control'));

        $this->assert_common_headers(
            $response,
            $clock->now()->format(\DateTimeInterface::RFC7231),
            $clock->now()->add(new \DateInterval('PT2S'))->format(\DateTimeInterface::RFC7231),
        );
    }

    public function test_serve_shims_non_current_positive_revision_uses_short_cache_headers(): void {
        $clock = $this->mock_clock_with_frozen(self::FROZEN_TIME);
        $controller = new shims_controller($clock);
        $revision = time() + DAYSECS;

        $response = $controller->serve_shims(
            new ServerRequest('GET', "/shims/{$revision}"),
            new Response(),
            $revision,
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertFalse($response->hasHeader('ETag'));
        $this->assertFalse($response->hasHeader('Cache-Control'));

        $this->assert_common_headers(
            $response,
            $clock->now()->format(\DateTimeInterface::RFC7231),
            $clock->now()->add(new \DateInterval('PT2S'))->format(\DateTimeInterface::RFC7231),
        );
    }

    public function test_serve_shims_valid_revision_uses_immutable_cache_headers(): void {
        $clock = $this->mock_clock_with_frozen(self::FROZEN_TIME);
        $controller = new shims_controller($clock);
        $revision = time();
        $file = $this->get_shims_file_path();
        $expiry = $clock->now()->add(new \DateInterval('P1Y'));
        $maxage = $expiry->getTimestamp() - $clock->time();

        $response = $controller->serve_shims(
            new ServerRequest('GET', "/shims/{$revision}"),
            new Response(),
            $revision,
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($response->hasHeader('ETag'));
        $this->assertNotSame('', $response->getHeaderLine('ETag'));
        $this->assertSame("public, max-age={$maxage}, immutable", $response->getHeaderLine('Cache-Control'));

        $this->assert_common_headers(
            $response,
            $clock->now()->setTimestamp(filemtime($file))->format(\DateTimeInterface::RFC7231),
            $expiry->format(\DateTimeInterface::RFC7231),
        );
    }

    public function test_serve_shims_matching_etag_returns_304(): void {
        $clock = $this->mock_clock_with_frozen(self::FROZEN_TIME);
        $controller = new shims_controller($clock);
        $revision = time();

        $primingresponse = $controller->serve_shims(
            new ServerRequest('GET', "/shims/{$revision}"),
            new Response(),
            $revision,
        );
        $etag = $primingresponse->getHeaderLine('ETag');
        $this->assertNotSame('', $etag);

        $request = new ServerRequest('GET', "/shims/{$revision}", ['If-None-Match' => $etag]);

        $response = $controller->serve_shims($request, new Response(), $revision);

        $this->assertSame(304, $response->getStatusCode());
    }

    /**
     * Assert the headers shared by both cache modes.
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param string $lastmodified
     * @param string $expires
     */
    private function assert_common_headers(
        \Psr\Http\Message\ResponseInterface $response,
        string $lastmodified,
        string $expires,
    ): void {
        $this->assertSame(
            'inline; filename="es-module-shims.js"',
            $response->getHeaderLine('Content-Disposition'),
        );
        $this->assertSame(
            'application/javascript; charset=utf-8',
            $response->getHeaderLine('Content-Type'),
        );
        $this->assertSame('', $response->getHeaderLine('Pragma'));
        $this->assertSame('none', $response->getHeaderLine('Accept-Ranges'));
        $this->assertSame($lastmodified, $response->getHeaderLine('Last-Modified'));
        $this->assertSame($expires, $response->getHeaderLine('Expires'));
    }

    /**
     * Get the absolute path to the bundled shims script.
     *
     * @return string
     */
    private function get_shims_file_path(): string {
        $controller = new \ReflectionClass(shims_controller::class);

        return dirname($controller->getFileName(), 4) . '/js/shims/es-module-shims.js';
    }
}
