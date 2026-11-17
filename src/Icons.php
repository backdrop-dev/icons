<?php
/**
 * Icon registry and renderer.
 *
 * @package   Backdrop\Icons
 * @author    Benjamin Lu <benlumia007@gmail.com>
 * @copyright 2026 Benjamin Lu
 * @license   https://www.gnu.org/licenses/gpl-2.0.html GPL-2.0-or-later
 * @link      https://github.com/backdrop-dev/icons
 */

namespace Backdrop\Icons;

/**
 * Finds icon files and renders them as inline SVG.
 *
 * Icons are looked up by name and style ("regular" or "solid") in a list of
 * folders. The bundled Iconoir folder comes last, so folders added with
 * `addPath()` or the `backdrop/icons/paths` filter can replace or add icons.
 */
class Icons {

	/**
	 * Folders to look in, by style. Earlier folders win.
	 *
	 * @var array<string, string[]>
	 */
	protected $paths = [];

	/**
	 * SVG contents already read, by "style/name".
	 *
	 * @var array<string, string>
	 */
	protected $cache = [];

	/**
	 * Counts rendered icons that define IDs, to keep the IDs unique.
	 *
	 * @var int
	 */
	protected static $instances = 0;

	/**
	 * Sets up the bundled icon folders.
	 *
	 * @param string|null $assets Folder holding `regular/` and `solid/`. Defaults to the bundled Iconoir icons.
	 */
	public function __construct( ?string $assets = null ) {

		$assets = rtrim( $assets ?? dirname( __DIR__ ) . '/assets', '/' );

		foreach ( $this->styles() as $style ) {
			$this->paths[ $style ] = [ "{$assets}/{$style}" ];
		}
	}

	/**
	 * Returns the supported styles.
	 *
	 * @return string[]
	 */
	public function styles(): array {

		return [ 'regular', 'solid' ];
	}

	/**
	 * Adds a folder of `{name}.svg` files, checked before the bundled icons.
	 *
	 * @param string $path  Folder path.
	 * @param string $style Style the icons belong to.
	 */
	public function addPath( string $path, string $style = 'regular' ): void {

		if ( ! in_array( $style, $this->styles(), true ) ) {
			return;
		}

		array_unshift( $this->paths[ $style ], rtrim( $path, '/' ) );
		$this->cache = [];
	}

	/**
	 * Returns the folders searched for a style, most important first.
	 *
	 * @param  string $style Style.
	 * @return string[]
	 */
	public function paths( string $style = 'regular' ): array {

		$paths = $this->paths[ $style ] ?? [];

		if ( function_exists( 'apply_filters' ) ) {
			$paths = (array) apply_filters( 'backdrop/icons/paths', $paths, $style );
		}

		return array_values( array_filter( $paths, 'is_string' ) );
	}

	/**
	 * Whether an icon exists.
	 *
	 * @param  string $name  Icon name, such as "search".
	 * @param  string $style Style.
	 * @return bool
	 */
	public function exists( string $name, string $style = 'regular' ): bool {

		return '' !== $this->svg( $name, $style );
	}

	/**
	 * Returns an icon's SVG file contents, or an empty string if there's no
	 * such icon. Names may only use lowercase letters, numbers and dashes, so
	 * a name can't point outside the icon folders.
	 *
	 * @param  string $name  Icon name.
	 * @param  string $style Style.
	 * @return string
	 */
	public function svg( string $name, string $style = 'regular' ): string {

		if ( ! preg_match( '/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $name ) || ! in_array( $style, $this->styles(), true ) ) {
			return '';
		}

		$key = "{$style}/{$name}";

		if ( ! isset( $this->cache[ $key ] ) ) {
			$this->cache[ $key ] = '';

			foreach ( $this->paths( $style ) as $path ) {
				$file = "{$path}/{$name}.svg";

				if ( is_file( $file ) && is_readable( $file ) ) {
					$this->cache[ $key ] = trim( (string) file_get_contents( $file ) );
					break;
				}
			}
		}

		return $this->cache[ $key ];
	}

	/**
	 * Renders an icon as inline SVG.
	 *
	 * @param  string $name Icon name, such as "search".
	 * @param  array  $args {
	 *     Optional.
	 *
	 *     @type string     $style      "regular" (default) or "solid".
	 *     @type bool       $fallback   Use the regular icon when there's no solid one. Default true. Only 288
	 *                                  of Iconoir's icons have a solid version.
	 *     @type int|string $size       Width and height. A number is in pixels. Default "1em", the text size.
	 *     @type string     $label      Accessible name. Without one, the icon is hidden from screen readers.
	 *     @type string     $class      Extra classes.
	 *     @type int|float  $stroke     Stroke width for outline icons. Default 1.5.
	 *     @type array      $attributes Extra attributes for the `<svg>` element.
	 * }
	 * @return string The SVG, or an empty string if there's no such icon.
	 */
	public function render( string $name, array $args = [] ): string {

		$args = array_merge( [
			'style'      => 'regular',
			'fallback'   => true,
			'size'       => '1em',
			'label'      => '',
			'class'      => '',
			'stroke'     => null,
			'attributes' => [],
		], $args );

		$svg = $this->svg( $name, (string) $args['style'] );

		if ( '' === $svg && 'solid' === $args['style'] && $args['fallback'] ) {
			$svg = $this->svg( $name, 'regular' );
		}

		if ( '' === $svg || ! preg_match( '/^<svg\b([^>]*)>(.*)<\/svg>$/s', $svg, $parts ) ) {
			return '';
		}

		preg_match_all( '/([a-zA-Z_:][\w:.-]*)="([^"]*)"/', $parts[1], $found, PREG_SET_ORDER );

		$attributes = [];

		foreach ( $found as $attribute ) {
			$attributes[ $attribute[1] ] = $attribute[2];
		}

		$size = is_numeric( $args['size'] ) ? (string) $args['size'] : trim( (string) $args['size'] );

		$attributes['width']  = $size;
		$attributes['height'] = $size;

		if ( null !== $args['stroke'] && is_numeric( $args['stroke'] ) && isset( $attributes['stroke-width'] ) ) {
			$attributes['stroke-width'] = (string) $args['stroke'];
		}

		$attributes['class'] = trim( "backdrop-icon backdrop-icon-{$name} " . (string) $args['class'] );

		$label = trim( (string) $args['label'] );

		if ( '' !== $label ) {
			$attributes['role']       = 'img';
			$attributes['aria-label'] = $label;
		} else {
			$attributes['aria-hidden'] = 'true';
		}

		$attributes['focusable'] = 'false';

		foreach ( (array) $args['attributes'] as $key => $value ) {
			if ( is_string( $key ) && preg_match( '/^[a-zA-Z_:][\w:.-]*$/', $key ) && is_scalar( $value ) ) {
				$attributes[ $key ] = (string) $value;
			}
		}

		$body = preg_replace( '/>\s+</', '><', trim( $parts[2] ) );

		// Icons that use clip paths define IDs. Make them unique so the same
		// icon can appear more than once on a page.
		if ( false !== strpos( $body, ' id="' ) ) {
			$suffix = '-' . ++static::$instances;
			$body   = preg_replace( '/\bid="([^"]+)"/', 'id="$1' . $suffix . '"', $body );
			$body   = preg_replace( '/url\(#([^)]+)\)/', 'url(#$1' . $suffix . ')', $body );
		}

		$html = '';

		foreach ( $attributes as $key => $value ) {
			$html .= sprintf( ' %s="%s"', $key, $this->escape( $value ) );
		}

		return "<svg{$html}>{$body}</svg>";
	}

	/**
	 * Escapes an attribute value, with WordPress's function when available.
	 *
	 * @param  string $value Value.
	 * @return string
	 */
	protected function escape( string $value ): string {

		return function_exists( 'esc_attr' )
			? esc_attr( $value )
			: htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );
	}
}
