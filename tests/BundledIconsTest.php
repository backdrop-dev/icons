<?php
/**
 * Checks every bundled icon is safe and consistent to inline.
 *
 * @package Backdrop\Icons
 */

namespace Backdrop\Icons\Tests;

use PHPUnit\Framework\TestCase;

class BundledIconsTest extends TestCase {

	public function testVersionIsRecorded(): void {

		$version = trim( (string) file_get_contents( dirname( __DIR__ ) . '/assets/VERSION' ) );

		$this->assertMatchesRegularExpression( '/^\d+\.\d+\.\d+$/', $version );
	}

	public function testIconoirLicenseIsIncluded(): void {

		$license = (string) file_get_contents( dirname( __DIR__ ) . '/LICENSE-iconoir' );

		$this->assertStringContainsString( 'MIT License', $license );
	}

	public function testEveryIconIsSafeToInline(): void {

		$files = glob( dirname( __DIR__ ) . '/assets/{regular,solid}/*.svg', GLOB_BRACE );

		$this->assertGreaterThan( 1000, count( $files ) );

		$problems = [];

		foreach ( $files as $file ) {
			$name = basename( dirname( $file ) ) . '/' . basename( $file );
			$svg  = (string) file_get_contents( $file );

			if ( ! preg_match( '/^[a-z0-9]+(?:-[a-z0-9]+)*\.svg$/', basename( $file ) ) ) {
				$problems[] = "{$name}: name can't be used with icon()";
			}

			if ( ! preg_match( '/^<svg\b[^>]*\bviewBox="0 0 24 24"[^>]*>.*<\/svg>$/s', trim( $svg ) ) ) {
				$problems[] = "{$name}: not a single 24x24 <svg> element";
			}

			if ( preg_match( '/<(script|style|foreignObject|image|a)\b|\son[a-z]+=|xlink:href|javascript:/i', $svg ) ) {
				$problems[] = "{$name}: contains scripts, styles, links or external content";
			}

			if ( preg_match( '/(fill|stroke)="(?!none|currentColor|white)[^"]*"/', $svg ) ) {
				$problems[] = "{$name}: uses a fixed color instead of currentColor";
			}

			libxml_use_internal_errors( true );

			if ( false === simplexml_load_string( $svg ) ) {
				$problems[] = "{$name}: not valid XML";
			}

			libxml_clear_errors();
		}

		$this->assertSame( [], $problems );
	}
}
