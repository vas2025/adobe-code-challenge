<?php

	declare( strict_types = 1 );

	use Slim\App;
	use Doctrine\DBAL\DriverManager;
	use Dotenv\Dotenv;
	use Slim\Routing\RouteCollectorProxy;
	use App\Controllers\BooksController;
	use App\Controllers\AuthController;
	use App\Middleware\JwtMiddleware;
	use App\Middleware\RateLimitMiddleware;
	use Predis\Client as RedisClient;

	return function ( App $app )
	{
		// Initializing .env
		$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
		$dotenv->load();
		
		// CORS Middleware
		$app->add( function ( $request , $handler ) {
			$response = $handler->handle( $request );
			return $response
				->withHeader( 'Access-Control-Allow-Origin'  , $request->getHeaderLine('Origin') ?: '*' )
				->withHeader( 'Access-Control-Allow-Headers' , 'X-Requested-With, Content-Type, Accept, Origin, Authorization' )
				->withHeader( 'Access-Control-Allow-Methods' , 'GET, POST, PUT, DELETE, OPTIONS' )
				->withHeader( 'Access-Control-Allow-Credentials' , 'true' );
		});

		// Allow preflight requests (OPTIONS)
		$app->options( '/{routes:.+}' , function ( $request , $response ) {
			return $response;
		});
		
		// Initializing Redis
		$redis = new RedisClient([
			'scheme' => 'tcp',
			'host' => $_ENV[ 'REDIS_HOST' ] ?? '127.0.0.1',
			'port' => $_ENV[ 'REDIS_PORT' ] ?? 6379,
		]);

		// Adding rate limiter globally for all requests
		$app->add( new RateLimitMiddleware( $redis , 100 , 60 , $_ENV[ 'JWT_SECRET' ] ) );
	
		// Middleware for JSON
		$app->addBodyParsingMiddleware();
		
		// Test route
		$app->get( '/api/ping' , function ( $request , $response ) {
			$response->getBody()->write( json_encode( [ 'pong' => true ] ) );	
			return $response->withHeader( 'Content-Type', 'application/json' );
		});
		
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