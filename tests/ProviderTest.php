<?php
/**
 * Tests for the service provider.
 *
 * @package Backdrop\Icons
 */

namespace Backdrop\Icons\Tests;

use Backdrop\Core\Application;
use Backdrop\Icons\Icons;
use Backdrop\Icons\Provider;
use PHPUnit\Framework\TestCase;

use function Backdrop\Icons\icons;

class ProviderTest extends TestCase {

	public function testRegistersTheSharedRegistry(): void {

		$app = new Application();
		$app->provider( Provider::class );

		$this->assertSame( icons(), $app->resolve( Icons::class ) );
		$this->assertSame( icons(), $app->resolve( 'icons' ) );
	}
}
