<?php

	declare( strict_types = 1 );

	namespace App\Middleware;

	use Predis\Client;
	use Psr\Http\Message\ResponseInterface;
	use Psr\Http\Message\ServerRequestInterface;
	use Psr\Http\Server\RequestHandlerInterface;
	use Slim\Psr7\Response;

	class RateLimitMiddleware
	{
		private Client $redis;
		private int $limit;
		private int $window; // in seconds
		private ?string $jwtSecret;

		public function __construct( Client $redis , int $limit = 100 , int $window = 60 , ?string $jwtSecret = null )
		{
			$this->redis  = $redis;
			$this->limit  = $limit;
			$this->window = $window;
			$this->jwtSecret = $jwtSecret;
		}

		public function __invoke( ServerRequestInterface $req , RequestHandlerInterface $handler ): ResponseInterface
		{
			$ip = $req->getServerParams()[ 'REMOTE_ADDR' ] ?? 'unknown';
			$key = "rate:" . $this->getIdentifier( $req );
			$current = (int) $this->redis->get( $key );

			if( $current === 0 )
			{
				$this->redis->setex( $key , $this->window , 1 );
			}
			else
			{
				if( $current >= $this->limit )
				{
					$r = new Response();
					
					$r->getBody()->write(
						json_encode(
							[
								'error' 		 => 'Too many requests',
								'limit' 		 => $this->limit,
								'window_seconds' => $this->window
							]
						)
					);
					
					return $r->withStatus(429)->withHeader( 'Content-Type', 'application/json' );
				}
				
				$this->redis->incr( $key );
			}

			$response =  $handler->handle( $req );
			
			return $response
				->withHeader( 'X-RateLimit-Limit'     , (string) $this->limit )
				->withHeader( 'X-RateLimit-Remaining' , (string) max( 0 , $this->limit - $current ) );
		}
		
		// Gets unique user's identifier for rate limiting (user_id from JWT or IP-address)
		private function getIdentifier( ServerRequestInterface $req ): string
		{
			$authHeader = $req->getHeaderLine( 'Authorization' );
			if( $authHeader && str_starts_with( $authHeader , 'Bearer ' ) )
			{
				$token = trim( substr( $authHeader , 7 ) );
				try
				{
					$decoded = JWT::decode( $token , new Key( $this->jwtSecret , 'HS256' ) );
					if( isset( $decoded->sub ) )
					{
						return 'user:' . $decoded->sub;
					}
				}
				catch ( \Throwable $e )
				{
					// Invalid token, fallback to IP
				}
			}

			return 'ip:' . ( $req->getServerParams()[ 'REMOTE_ADDR' ] ?? 'unknown' );
		}
	}