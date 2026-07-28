<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$files = array_merge(
	glob( __DIR__ . '/Unit/*Test.php' ) ?: array(),
	glob( __DIR__ . '/Integration/*Test.php' ) ?: array()
);
foreach ( $files as $file ) {
	require_once $file;
}

$classes = array_filter(
	get_declared_classes(),
	static fn ( string $class ): bool => str_starts_with( $class, 'MDB\\Tests\\' ) && str_ends_with( $class, 'Test' )
);
$tests   = 0;
foreach ( $classes as $class ) {
	$instance = new $class();
	foreach ( get_class_methods( $instance ) as $method ) {
		if ( str_starts_with( $method, 'test_' ) ) {
			$instance->{$method}();
			++$tests;
		}
	}
}

echo "OK ({$tests} tests)\n";
