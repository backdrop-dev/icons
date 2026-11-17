<?php
/**
 * Helper functions.
 *
 * Loaded by Composer's autoloader, so they work without registering the
 * service provider. Each is defined only once, in case a theme and a plugin
 * both bundle this package.
 *
 * @package   Backdrop\Icons
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2026 Benjamin Lu
 * @license   https://www.gnu.org/licenses/gpl-2.0.html GPL-2.0-or-later
 * @link      https://github.com/backdrop-dev/icons
 */

namespace Backdrop\Icons;

if ( ! function_exists( __NAMESPACE__ . '\\icons' ) ) {
	/**
	 * Returns the shared icon registry.
	 *
	 * @return Icons
	 */
	function icons(): Icons {
		static $icons = null;

		if ( null === $icons ) {
			$icons = new Icons();
		}

		return $icons;
	}
}

if ( ! function_exists( __NAMESPACE__ . '\\icon' ) ) {
	/**
	 * Returns an icon as inline SVG, or an empty string if there's no such
	 * icon. See `Icons::render()` for the arguments.
	 *
	 * @param  string $name Icon name, such as "search".
	 * @param  array  $args Optional arguments: style, size, label, class, stroke, attributes.
	 * @return string
	 */
	function icon( string $name, array $args = [] ): string {
		return icons()->render( $name, $args );
	}
}

if ( ! function_exists( __NAMESPACE__ . '\\display_icon' ) ) {
	/**
	 * Prints an icon. See `icon()`.
	 *
	 * @param string $name Icon name.
	 * @param array  $args Optional arguments.
	 */
	function display_icon( string $name, array $args = [] ): void {
		echo icon( $name, $args ); // phpcs:ignore WordPress.Security.EscapeOutput -- Attributes are escaped, and the markup comes from the icon files.
	}
}

if ( ! function_exists( __NAMESPACE__ . '\\icon_exists' ) ) {
	/**
	 * Whether an icon exists.
	 *
	 * @param  string $name  Icon name.
	 * @param  string $style "regular" or "solid".
	 * @return bool
	 */
	function icon_exists( string $name, string $style = 'regular' ): bool {
		return icons()->exists( $name, $style );
	}
}
