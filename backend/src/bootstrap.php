<?php

	declare( strict_types = 1 );

	use Slim\App;
	use Doctrine\DBAL\DriverManager;
	use Dotenv\Dotenv;
	use Slim\Routing\RouteCollectorProxy;
	use App\Controllers\BooksController;
	use App\Controllers\AuthController;
	use App\Middleware\JwtMiddleware;

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

		// AUTH routes
		$authController = new AuthController( $connection , $_ENV[ 'JWT_SECRET' ] );
		$app->post( '/api/auth/register', [ $authController , 'register' ] );
		$app->post( '/api/auth/login',    [ $authController , 'login' ] );
		
		// CRUD routes
		$booksController = new BooksController($connection);
		$jwtMiddleware   = new JwtMiddleware($_ENV['JWT_SECRET']);

		$app->group( '/api/books' , function ( RouteCollectorProxy $group ) use ( $booksController ) {
			$group->get(    ''      , [ $booksController , 'list'   ] );
			$group->get(    '/{id}' , [ $booksController , 'get'    ] );
			$group->post(   ''      , [ $booksController , 'create' ] );
			$group->put(    '/{id}' , [ $booksController , 'update' ] );
			$group->delete( '/{id}' , [ $booksController , 'delete' ] );
		})->add( $jwtMiddleware );		
		
	};