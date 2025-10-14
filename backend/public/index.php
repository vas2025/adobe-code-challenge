<?php

	// Testing:
	// php -S localhost:8080 -t public
	// http://localhost:8080/api/ping

	declare( strict_types = 1);
	
	use Slim\Factory\AppFactory;
	
	require __DIR__ . '/../vendor/autoload.php';
	
	$app = AppFactory::create();
	
	(require __DIR__ . '/../src/bootstrap.php')($app);
	
	$app->run();