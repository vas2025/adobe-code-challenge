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
	use Monolog\Logger;
	use Monolog\Handler\StreamHandler;

	return function ( App $app )
	{
		// Initializing .env
		$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
		$dotenv->load();

		// Initializing Logger
		$logger = new Logger( 'app' );
		$logger->pushHandler( new StreamHandler( __DIR__ . '/../logs/app.log' , Logger::DEBUG ) );
		
		// CORS Middleware
		$app->add( function ( $request , $handler ) {
			$response = $handler->handle( $request );
			return $response
				// Allow from any origin, specify specify allow list once in production
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

		// Read rate limiter params from environment of fallback to defaults
		$rateLimitMax    = isset( $_ENV[ 'RATE_LIMIT_MAX' ] ) ? (int) $_ENV[ 'RATE_LIMIT_MAX' ] : 100;
		$rateLimitWindow = isset( $_ENV[ 'RATE_LIMIT_WINDOW' ] ) ? (int) $_ENV[ 'RATE_LIMIT_WINDOW' ] : 60;
		$jwtSecret       = $_ENV['JWT_SECRET'] ?? null;

		// Adding rate limiter globally for all requests
		$app->add( new RateLimitMiddleware( $redis , $rateLimitMax , $rateLimitWindow , $jwtSecret ) );
	
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
		$booksController = new BooksController( $connection , $logger );
		$jwtMiddleware   = new JwtMiddleware( $_ENV[ 'JWT_SECRET' ] );

		$app->group( '/api/books' , function ( RouteCollectorProxy $group ) use ( $booksController ) {
			$group->get(    ''      , [ $booksController , 'list'   ] );
			$group->get(    '/{id}' , [ $booksController , 'get'    ] );
			$group->post(   ''      , [ $booksController , 'create' ] );
			$group->put(    '/{id}' , [ $booksController , 'update' ] );
			$group->delete( '/{id}' , [ $booksController , 'delete' ] );
		})->add( $jwtMiddleware );		
		
		// Default / Root route
		$app->get( '/' , function ( $request , $response ) {
			$response->getBody()->write(
				json_encode([
					'status'  => 'ok',
					'message' => 'Backend API is running'
				])
			);
			
			return $response
				->withHeader('Content-Type', 'application/json')
				->withStatus(200);
		});

		// API root route
		$app->get( '/api' , function ( $request , $response ) {
			$response->getBody()->write(
				json_encode([
					'status'  => 'ok',
					'message' => 'Welcome to Adobe Code Challenge API'
				])
			);
			
			return $response
				->withHeader( 'Content-Type' , 'application/json' )
				->withStatus(200);
		});

		// 404 fallback handler
		$app->map( [ 'GET' , 'POST' , 'PUT' , 'DELETE' , 'PATCH' ] , '/{routes:.+}' , function ( $request , $response ) {
			$response->getBody()->write(
				json_encode([
					'error'   => 'Not found',
					'path'    => (string) $request->getUri()->getPath(),
					'method'  => $request->getMethod(),
				])
			);
			
			return $response
				->withHeader( 'Content-Type' , 'application/json' )
				->withStatus(404);
		});
		
	};