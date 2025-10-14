<?php

	declare( strict_types = 1 );

	use Slim\App;

	return function ( App $app )
	{
		$app->addBodyParsingMiddleware();

		// Test route
		$app->get( '/api/ping' , function ( $request , $response ) {
			
			$response->getBody()->write( json_encode( [ 'pong' => true ] ) );
			
			return $response->withHeader( 'Content-Type', 'application/json' );
			
		});
	};