<?php
/**
 * Tests for rendering icons.
 *
 * @package Backdrop\Icons
 */

namespace Backdrop\Icons\Tests;

use Backdrop\Icons\Icons;
use PHPUnit\Framework\TestCase;

use function Backdrop\Icons\display_icon;
use function Backdrop\Icons\icon;
use function Backdrop\Icons\icon_exists;
use function Backdrop\Icons\icons;

class IconsTest extends TestCase {

	/**
	 * A folder of test icons, removed after each test.
	 *
	 * @var string
	 */
	protected $custom = '';

	protected function tearDown(): void {

		if ( $this->custom && is_dir( $this->custom ) ) {
			array_map( 'unlink', glob( "{$this->custom}/*" ) );
			rmdir( $this->custom );
		}

		parent::tearDown();
	}

	/**
	 * Creates a folder with one icon in it.
	 */
	protected function customFolder( string $name, string $svg ): string {

		$this->custom = sys_get_temp_dir() . '/backdrop-icons-' . uniqid();
		mkdir( $this->custom );
		file_put_contents( "{$this->custom}/{$name}.svg", $svg );

		return $this->custom;
	}

	public function testRendersADecorativeIcon(): void {

		$svg = ( new Icons() )->render( 'search' );

		$this->assertStringStartsWith( '<svg ', $svg );
		$this->assertStringEndsWith( '</svg>', $svg );
		$this->assertStringContainsString( 'viewBox="0 0 24 24"', $svg );
		$this->assertStringContainsString( 'width="1em" height="1em"', $svg );
		$this->assertStringContainsString( 'class="backdrop-icon backdrop-icon-search"', $svg );
		$this->assertStringContainsString( 'aria-hidden="true"', $svg );
		$this->assertStringContainsString( 'focusable="false"', $svg );
		$this->assertStringNotContainsString( 'role="img"', $svg );
		$this->assertStringNotContainsString( "\n", $svg );
	}

	public function testLabelMakesTheIconAnImage(): void {

		$svg = ( new Icons() )->render( 'github', [ 'label' => 'GitHub "profile"' ] );

		$this->assertStringContainsString( 'role="img"', $svg );
		$this->assertStringContainsString( 'aria-label="GitHub &quot;profile&quot;"', $svg );
		$this->assertStringNotContainsString( 'aria-hidden', $svg );
	}

	public function testSize(): void {

		$icons = new Icons();

		$this->assertStringContainsString( 'width="20" height="20"', $icons->render( 'menu', [ 'size' => 20 ] ) );
		$this->assertStringContainsString( 'width="2rem" height="2rem"', $icons->render( 'menu', [ 'size' => '2rem' ] ) );
	}

	public function testClassesAndAttributesAreEscaped(): void {

		$svg = ( new Icons() )->render( 'menu', [
			'class'      => 'extra "quoted"',
			'attributes' => [
				'data-test'   => '<value>',
				'bad attr'    => 'skipped',
				'onload"x'    => 'skipped',
				'data-array'  => [ 'skipped' ],
			],
		] );

		$this->assertStringContainsString( 'class="backdrop-icon backdrop-icon-menu extra &quot;quoted&quot;"', $svg );
		$this->assertStringContainsString( 'data-test="&lt;value&gt;"', $svg );
		$this->assertStringNotContainsString( 'skipped', $svg );
	}

	public function testStrokeWidth(): void {

		$icons = new Icons();

		$this->assertStringContainsString( 'stroke-width="1.5"', $icons->render( 'menu' ) );
		$this->assertStringContainsString( 'stroke-width="2"', $icons->render( 'menu', [ 'stroke' => 2 ] ) );
		$this->assertStringNotContainsString( 'stroke-width="bad"', $icons->render( 'menu', [ 'stroke' => 'bad' ] ) );
	}

	public function testSolidStyle(): void {

		$icons = new Icons();

		$this->assertTrue( $icons->exists( 'heart', 'solid' ) );
		$this->assertNotSame( $icons->render( 'heart' ), $icons->render( 'heart', [ 'style' => 'solid' ] ) );
	}

	public function testSolidFallsBackToRegular(): void {

		$icons = new Icons();

		$this->assertFalse( $icons->exists( 'search', 'solid' ) );
		$this->assertSame( $icons->render( 'search' ), $icons->render( 'search', [ 'style' => 'solid' ] ) );
		$this->assertSame( '', $icons->render( 'search', [ 'style' => 'solid', 'fallback' => false ] ) );
	}

	/**
	 * @dataProvider invalidIcons
	 */
	public function testInvalidOrMissingIconsRenderNothing( string $name, string $style ): void {

		$icons = new Icons();

		$this->assertSame( '', $icons->render( $name, [ 'style' => $style ] ) );
		$this->assertFalse( $icons->exists( $name, $style ) );
	}

	public function invalidIcons(): array {

		return [
			'unknown icon'       => [ 'not-a-real-icon', 'regular' ],
			'unknown style'      => [ 'search', 'outline' ],
			'path traversal'     => [ '../solid/heart', 'regular' ],
			'absolute path'      => [ '/etc/passwd', 'regular' ],
			'uppercase'          => [ 'Search', 'regular' ],
			'empty'              => [ '', 'regular' ],
			'trailing dash'      => [ 'search-', 'regular' ],
			'file extension'     => [ 'search.svg', 'regular' ],
		];
	}

	public function testIdsAreUniqueEachTime(): void {

		$icons  = new Icons();
		$first  = $icons->render( 'git' );
		$second = $icons->render( 'git' );

		preg_match( '/ id="([^"]+)"/', $first, $first_id );
		preg_match( '/ id="([^"]+)"/', $second, $second_id );

		$this->assertNotEmpty( $first_id );
		$this->assertNotSame( $first_id[1], $second_id[1] );
		$this->assertStringContainsString( "url(#{$first_id[1]})", $first );
		$this->assertStringContainsString( "url(#{$second_id[1]})", $second );
	}

	public function testAddedFoldersComeFirst(): void {

		$icons  = new Icons();
		$folder = $this->customFolder( 'search', '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/></svg>' );

		$icons->addPath( $folder );

		$this->assertStringContainsString( '<circle cx="12" cy="12" r="4"/>', $icons->render( 'search' ) );
		$this->assertTrue( $icons->exists( 'menu' ), 'Bundled icons are still found.' );
		$this->assertSame( $folder, $icons->paths()[0] );
	}

	public function testAddedFoldersCanAddNewIcons(): void {

		$icons = new Icons();
		$icons->addPath( $this->customFolder( 'brand-logo', '<svg viewBox="0 0 24 24"><path d="M0 0h24v24H0z"/></svg>' ) );

		$this->assertTrue( $icons->exists( 'brand-logo' ) );
		$this->assertFalse( $icons->exists( 'brand-logo', 'solid' ) );
	}

	public function testUnknownStylePathIsIgnored(): void {

		$icons = new Icons();
		$icons->addPath( sys_get_temp_dir(), 'outline' );

		$this->assertSame( [], $icons->paths( 'outline' ) );
	}

	public function testHelperFunctions(): void {

		$this->assertSame( icons(), icons() );
		$this->assertSame( icons()->render( 'search' ), icon( 'search' ) );
		$this->assertTrue( icon_exists( 'search' ) );
		$this->assertFalse( icon_exists( 'search', 'outline' ) );

		ob_start();
		display_icon( 'search', [ 'label' => 'Search' ] );
		$this->assertSame( icon( 'search', [ 'label' => 'Search' ] ), ob_get_clean() );
	}
}
