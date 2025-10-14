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

		public function __construct(Client $redis, int $limit = 100, int $window = 60)
		{
			$this->redis  = $redis;
			$this->limit  = $limit;
			$this->window = $window;
		}

		public function __invoke( ServerRequestInterface $req , RequestHandlerInterface $handler ): ResponseInterface
		{
			$ip = $req->getServerParams()[ 'REMOTE_ADDR' ] ?? 'unknown';
			$key = "rate:$ip";
			$current = (int) $this->redis->get($key);

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

			return $handler->handle( $req );
		}
	}