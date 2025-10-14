<?php

	declare( strict_types = 1 );

	namespace App\Middleware;

	use Firebase\JWT\JWT;
	use Firebase\JWT\Key;
	use Psr\Http\Message\ResponseInterface;
	use Psr\Http\Message\ServerRequestInterface;
	use Psr\Http\Server\RequestHandlerInterface;
	use Slim\Psr7\Response;

	class JwtMiddleware
	{
		private string $secret;

		public function __construct( string $secret )
		{
			$this->secret = $secret;
		}

		public function __invoke( ServerRequestInterface $req , RequestHandlerInterface $handler ): ResponseInterface
		{
			$auth = $req->getHeaderLine( 'Authorization' );
			
			if( !preg_match( '/Bearer\s+(.*)$/i' , $auth , $matches ) )
			{
				return $this->unauthorized( 'Missing token' );
			}

			try
			{
				$decoded = JWT::decode( $matches[1] , new Key( $this->secret , 'HS256' ) );
				$req = $req->withAttribute( 'user' , $decoded );
			}
			catch ( \Throwable $e )
			{
				return $this->unauthorized( 'Invalid token: ' . $e->getMessage() );
			}

			return $handler->handle( $req );
		}

		private function unauthorized( string $msg ): Response
		{
			$r = new Response();
			$r->getBody()->write(
				json_encode(
					[
						'error' => $msg
					]
				)
			);
			
			return $r->withStatus(401)->withHeader( 'Content-Type', 'application/json' );
		}
	}