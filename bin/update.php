<?php
/**
 * Updates the bundled Iconoir icons to a tagged release.
 *
 * Usage: php bin/update.php v7.12.2
 *
 * Downloads the release's `icons/regular` and `icons/solid` folders and its
 * license from https://github.com/iconoir-icons/iconoir, replaces
 * `assets/regular`, `assets/solid` and `LICENSE-iconoir`, and records the
 * version in `assets/VERSION`. Needs `git`.
 *
 * @package Backdrop\Icons
 */

$tag  = $argv[1] ?? '';
$root = dirname( __DIR__ );

if ( ! preg_match( '/^v\d+\.\d+\.\d+$/', $tag ) ) {
	fwrite( STDERR, "Usage: php bin/update.php v7.12.2\n" );
	exit( 1 );
}

/**
 * Runs a command and exits with its output if it fails.
 *
 * @param string $command Command.
 */
function update_run( string $command ): void {
	exec( $command . ' 2>&1', $output, $status );

	if ( 0 !== $status ) {
		fwrite( STDERR, "Failed: {$command}\n" . implode( "\n", $output ) . "\n" );
		exit( 1 );
	}
}

/**
 * Deletes a folder and everything in it.
 *
 * @param string $path Folder path.
 */
function update_remove( string $path ): void {
	if ( ! is_dir( $path ) ) {
		return;
	}

	$items = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $path, FilesystemIterator::SKIP_DOTS ),
		RecursiveIteratorIterator::CHILD_FIRST
	);

	foreach ( $items as $item ) {
		$item->isDir() && ! $item->isLink() ? rmdir( $item->getPathname() ) : unlink( $item->getPathname() );
	}

	rmdir( $path );
}

$tmp = sys_get_temp_dir() . '/iconoir-' . getmypid();
update_remove( $tmp );

// Fetch only the icons and the license from the tag.
update_run( sprintf(
	'git clone --quiet --depth 1 --branch %s --filter=blob:none --sparse https://github.com/iconoir-icons/iconoir %s',
	escapeshellarg( $tag ),
	escapeshellarg( $tmp )
) );
update_run( sprintf( 'git -C %s sparse-checkout set --no-cone /LICENSE /icons/regular/ /icons/solid/', escapeshellarg( $tmp ) ) );

$count = [];

foreach ( [ 'regular', 'solid' ] as $style ) {
	$source = "{$tmp}/icons/{$style}";
	$target = "{$root}/assets/{$style}";

	if ( ! is_dir( $source ) ) {
		fwrite( STDERR, "The release has no icons/{$style} folder.\n" );
		exit( 1 );
	}

	update_remove( $target );
	mkdir( $target, 0777, true );

	$count[ $style ] = 0;

	foreach ( glob( "{$source}/*.svg" ) as $file ) {
		// Normalize line endings, and make the few icons drawn in black use
		// the text color like the rest.
		$svg = rtrim( str_replace( "\r\n", "\n", (string) file_get_contents( $file ) ) ) . "\n";
		$svg = str_replace( [ 'fill="black"', 'stroke="black"' ], [ 'fill="currentColor"', 'stroke="currentColor"' ], $svg );
		file_put_contents( "{$target}/" . basename( $file ), $svg );
		$count[ $style ]++;
	}
}

copy( "{$tmp}/LICENSE", "{$root}/LICENSE-iconoir" );
file_put_contents( "{$root}/assets/VERSION", ltrim( $tag, 'v' ) . "\n" );

update_remove( $tmp );

printf( "Updated to Iconoir %s: %d regular and %d solid icons.\n", $tag, $count['regular'], $count['solid'] );
