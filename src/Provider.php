<?php
/**
 * Icons service provider.
 *
 * @package   Backdrop\Icons
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2026 Benjamin Lu
 * @license   https://www.gnu.org/licenses/gpl-2.0.html GPL-2.0-or-later
 * @link      https://github.com/backdrop-dev/icons
 */

namespace Backdrop\Icons;

use Backdrop\Core\ServiceProvider;

/**
 * Registers the shared icon registry with the application, as `icons` and
 * `Backdrop\Icons\Icons`. Optional: `icon()` works without it.
 */
class Provider extends ServiceProvider {

	/**
	 * Binds the registry used by the helper functions.
	 */
	public function register(): void {

		$this->app->instance( Icons::class, icons() );
		$this->app->alias( Icons::class, 'icons' );
	}
}
