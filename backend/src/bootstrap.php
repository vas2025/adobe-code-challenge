<?php

	declare( strict_types = 1 );

	use Slim\App;
	use Doctrine\DBAL\DriverManager;
	use Dotenv\Dotenv;

	return function ( App $app )
	{
		// Initializing .env
		$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
		$dotenv->load();
	
		// Middleware for JSON
		$app->addBodyParsingMiddleware();
		
		// Setting up DB connection
		$connectionParams = [
			'dbname'   => $_ENV['DB_NAME'] ?? 'adobe',
			'user'     => $_ENV['DB_USER'] ?? 'postgres',
			'password' => $_ENV['DB_PASS'] ?? 'postgres',
			'host'     => $_ENV['DB_HOST'] ?? 'localhost',
			'port'     => $_ENV['DB_PORT'] ?? 5432,
			'driver'   => $_ENV['DB_DRIVER'] ?? 'pdo_pgsql',
		];
		
		$connection = DriverManager::getConnection($connectionParams);
		
		var_dump($connection);

		// Test route
		$app->get( '/api/ping' , function ( $request , $response ) use ($connection) {
			
			try
			{
				
				$connection->executeQuery( 'SELECT 1' );
				$response->getBody()->write(
					json_encode(
						[
							'pong' => true,
							'db'   => 'ok'
						]
					)
				);
				
			} catch (\Throwable $e) {
				
				$response->getBody()->write(
					json_encode(
						[
							'pong' => true,
							'db'   => 'error',
							'msg'  => $e->getMessage()
						]
					)
				);

			}
			
			return $response->withHeader( 'Content-Type', 'application/json' );
			
		});
	};