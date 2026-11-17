# Backdrop Icons

[Iconoir](https://iconoir.com) SVG icons for ClassicPress and WordPress themes and plugins, with helpers to output accessible inline icons. Built for [Backdrop](https://github.com/backdrop-dev/framework).

- **1,383 outline icons and 288 solid icons** from Iconoir 7.12.2, bundled in the package. Nothing loads from a CDN, so it meets the ClassicPress and WordPress.org rule against contacting other servers.
- **Inline SVG** that takes the color and size of the text around it, so you style icons with `color` and `font-size`.
- **Accessible by default**: decorative icons are hidden from screen readers; icons with a label are announced.

## Requirements

- PHP 7.4 or later
- Backdrop 2 (`backdrop-dev/framework` ^2.0), for the optional service provider

## Installation

```bash
composer require backdrop-dev/icons
```

The helper functions load with Composer's autoloader, so there's nothing to register.

## Usage

```php
use function Backdrop\Icons\icon;
use function Backdrop\Icons\display_icon;

// A decorative icon next to text: hidden from screen readers.
printf( '<button>%s %s</button>', icon( 'search' ), esc_html__( 'Search', 'my-theme' ) );

// An icon that stands alone needs a label.
display_icon( 'github', [ 'label' => __( 'GitHub', 'my-theme' ) ] );

// Solid style, fixed size, extra class.
echo icon( 'heart', [ 'style' => 'solid', 'size' => 20, 'class' => 'like-icon' ] );
```

Browse the icons and their names at [iconoir.com](https://iconoir.com). The name is the file name without `.svg`, such as `chat-bubble` or `arrow-right`.

### Options

| Option | Default | |
|---|---|---|
| `style` | `'regular'` | `'regular'` (outline) or `'solid'`. |
| `fallback` | `true` | Use the regular icon when there's no solid one. Only 288 icons have a solid version. |
| `size` | `'1em'` | Width and height. A number is in pixels; any CSS length works. `1em` matches the text size. |
| `label` | `''` | Accessible name. With one, the icon gets `role="img"` and `aria-label`; without one, `aria-hidden="true"`. |
| `class` | `''` | Extra classes. Every icon has `backdrop-icon` and `backdrop-icon-{name}`. |
| `stroke` | `1.5` | Stroke width for outline icons. |
| `attributes` | `[]` | Extra attributes for the `<svg>` element, such as `[ 'data-toggle' => 'menu' ]`. |

`icon()` returns an empty string for an unknown icon, and for any name that isn't lowercase letters, numbers and dashes, so a name can never point outside the icon folders. Values are escaped with `esc_attr()`.

### Other functions

```php
use function Backdrop\Icons\icon_exists;
use function Backdrop\Icons\icons;

icon_exists( 'mastodon' );          // true
icon_exists( 'mastodon', 'solid' ); // false

icons(); // The shared Backdrop\Icons\Icons registry.
```

### Adding or replacing icons

Add a folder of `{name}.svg` files. It's checked before the bundled icons, so a file named `search.svg` replaces Iconoir's search icon, and any other name adds a new icon:

```php
use function Backdrop\Icons\icons;

icons()->addPath( get_theme_file_path( 'assets/icons' ) );
icons()->addPath( get_theme_file_path( 'assets/icons/solid' ), 'solid' );
```

Plugins and child themes can do the same with the `backdrop/icons/paths` filter:

```php
add_filter( 'backdrop/icons/paths', function ( array $paths, string $style ) {
	if ( 'regular' === $style ) {
		array_unshift( $paths, __DIR__ . '/icons' );
	}
	return $paths;
}, 10, 2 );
```

Draw custom icons on a 24×24 grid with `stroke="currentColor"` or `fill="currentColor"` so they match.

### With a Backdrop application

The service provider is optional. It registers the same shared registry with the application:

```php
$app->provider( \Backdrop\Icons\Provider::class );

$app->resolve( 'icons' )->render( 'menu' );
```

### Styling

```css
.backdrop-icon {
	vertical-align: -0.125em; /* Sit on the text's baseline. */
	flex-shrink: 0;
}
```

A few solid icons draw small details in white, which show as white on any background.

## Credits for your theme or plugin

Iconoir is MIT licensed, which is compatible with the GPL. Both the ClassicPress directory and WordPress.org ask you to credit bundled resources. Add this to your `readme.txt`:

```
Iconoir, https://iconoir.com
(C) 2021 Luca Burgio, MIT License, https://opensource.org/licenses/MIT
Bundled in vendor/backdrop-dev/icons/assets.

Backdrop Icons, https://github.com/backdrop-dev/icons
(C) 2026 Benjamin Lu, GPLv2 or later, https://www.gnu.org/licenses/gpl-2.0.html
```

## Updating Iconoir

```bash
php bin/update.php v7.12.2
```

Replaces `assets/regular`, `assets/solid` and `LICENSE-iconoir` with the given Iconoir release, and records the version in `assets/VERSION`. Icons drawn in black are switched to `currentColor` so they follow the text color. CI re-imports the recorded version on every pull request and fails if the bundled icons don't match it.

## Testing

```bash
composer update
vendor/bin/phpunit
```

The tests cover rendering, accessibility attributes, escaping, invalid names, unique IDs for icons with clip paths, added folders and the solid fallback, and check that every bundled icon is valid, 24×24 SVG with no scripts, styles or fixed colors. CI runs them on PHP 7.4 to 8.5.

## License

Backdrop Icons is licensed under the [GNU General Public License v2.0 or later](LICENSE). The bundled icons are from [Iconoir](https://github.com/iconoir-icons/iconoir), © 2021 Luca Burgio, under the [MIT License](LICENSE-iconoir).
