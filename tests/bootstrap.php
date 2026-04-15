<?php

use WorDBless\Load;

// Keep WP core location configurable to match composer.json "wordpress-install-dir".
$wordpressInstallDir = getenv('WORDPRESS_INSTALL_DIR') ?: '../../../';
$wordpressPath = dirname(__DIR__) . '/' . $wordpressInstallDir;
$wpContentPath = $wordpressPath . '/wp-content';

if (! file_exists($wpContentPath)) {
	mkdir($wpContentPath, 0777, true);
}

if (! file_exists($wpContentPath . '/themes')) {
	mkdir($wpContentPath . '/themes', 0777, true);
}

copy(
    dirname( __DIR__ ) . '/vendor/automattic/wordbless/src/dbless-wpdb.php',
    $wpContentPath . '/db.php'
);

$theme_base_name = basename( dirname( __DIR__ ) );
$src = realpath( dirname( dirname( __DIR__ ) ) . '/' . $theme_base_name );
$dest = $wpContentPath . '/themes/' . $theme_base_name;

if ( is_dir($src) && ! file_exists($dest) ) {
	symlink($src, $dest);
}

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

Load::load();
