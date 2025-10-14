<?php
	declare( strict_types = 1 );

	namespace App\Controllers;

	use Doctrine\DBAL\Connection;
	use Firebase\JWT\JWT;
	use Firebase\JWT\Key;
	use Psr\Http\Message\ResponseInterface;
	use Psr\Http\Message\ServerRequestInterface;

	class AuthController
	{
		private Connection $db;
		private string $jwtSecret;

		public function __construct( Connection $db , string $jwtSecret )
		{
			$this->db = $db;
			$this->jwtSecret = $jwtSecret;
		}

		public function register( ServerRequestInterface $req , ResponseInterface $res ): ResponseInterface
		{
			$data = (array) $req->getParsedBody();
			$email = strtolower( trim( $data[ 'email' ] ?? '' ) );
			$password = $data[ 'password' ] ?? '';

			if( !$email || !$password )
			{
				return $this->json( $res , [ 'error' => 'Email and password required' ] , 400 );
			}

			$existing = $this->db->fetchAssociative ('SELECT id FROM users WHERE email = ?' , [ $email ] );
			
			if( $existing )
			{
				return $this->json( $res , [ 'error' => 'User already exists' ] , 409 );
			}

			$hash = password_hash( $password , PASSWORD_DEFAULT );
			
			$this->db->insert( 'users' , [
				'email'      => $email,
				'password'   => $hash,
				'created_at' => (new \DateTime())->format( 'Y-m-d H:i:s' ),
			]);

			return $this->json( $res , [ 'message' => 'User registered'] , 201 );
		}

		public function login( ServerRequestInterface $req , ResponseInterface $res ): ResponseInterface
		{
			$data = (array) $req->getParsedBody();
			$email = strtolower( trim( $data[ 'email' ] ?? '' ) );
			$password = $data[ 'password' ] ?? '';
			
			$user = $this->db->fetchAssociative( 'SELECT * FROM users WHERE email = ?' , [ $email ] );
			
			if( !$user || !password_verify( $password , $user[ 'password' ] ) )
			{
				return $this->json( $res , [ 'error' => 'Invalid credentials' ] , 401 );
			}

			$payload = [
				'sub'   => $user[ 'id' ],
				'email' => $user[ 'email' ],
				'iat'   => time(),
				'exp'   => time() + 3600
			];

			$token = JWT::encode( $payload , $this->jwtSecret , 'HS256' );

			return $this->json( $res , [ 'token' => $token ] );
		}

		private function json( ResponseInterface $res , array $data , int $status = 200 ): ResponseInterface
		{
			$res->getBody()->write( json_encode( $data ) );
			
			return $res->withStatus( $status )->withHeader( 'Content-Type', 'application/json' );
		}
	}