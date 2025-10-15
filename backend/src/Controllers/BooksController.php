<?php

	namespace App\Controllers;

	use Doctrine\DBAL\Connection;
	use Psr\Log\LoggerInterface;
	use Slim\Psr7\Request;
	use Slim\Psr7\Response;
	use Throwable;

	class BooksController
	{
		private Connection $connection;
		private LoggerInterface $logger;

		public function __construct( Connection $connection , LoggerInterface $logger )
		{
			$this->connection = $connection;
			$this->logger = $logger;
		}
		
		// Helpers

		private function json( Response $response , $data , int $status = 200 ): Response
		{
			$response->getBody()->write( json_encode( $data , JSON_UNESCAPED_UNICODE ) );
			return $response
				->withHeader( 'Content-Type' , 'application/json' )
 				->withStatus( $status );
		}

		private function validateBookData( array $data , bool $isUpdate = false ): array
		{
			$errors = [];

			if( !$isUpdate || isset( $data[ 'title' ] ) )
			{
				if( empty( trim( $data['title'] ?? '' ) ) )
				{
					$errors[ 'title' ] = 'Title is required';
				}
				elseif( mb_strlen( $data['title'] ) > 255 )
				{
					$errors[ 'title' ] = 'Title must be ≤ 255 chars';
				}
			}

			if( !$isUpdate || isset( $data[ 'author' ] ) )
			{
				if( empty( trim( $data[ 'author' ] ?? '' ) ) )
				{
					$errors[ 'author' ] = 'Author is required';
				}
				elseif( mb_strlen( $data[ 'author' ] ) > 255 )
				{
					$errors[ 'author' ] = 'Author must be ≤ 255 chars';
				}
			}

			if( isset( $data[ 'description' ] ) && mb_strlen( $data[ 'description' ]) > 2000 )
			{
				$errors[ 'description' ] = 'Description must be ≤ 2000 chars';
			}

			return $errors;
		}

		// CRUD endpoints

		public function list( Request $request , Response $response ): Response
		{
			try
			{
				$queryParams = $request->getQueryParams();
				$limit = isset( $queryParams ['limit' ]) ? (int) $queryParams[ 'limit' ] : 100;
				$offset = isset( $queryParams[ 'offset' ]) ? (int) $queryParams[ 'offset' ] : 0;

				$qb = $this->connection->createQueryBuilder();
				$qb->select( '*' )
					->from( 'books' )
					->setFirstResult( $offset )
					->setMaxResults( $limit )
					->orderBy( 'created_at' , 'DESC' );

				$books = $qb->fetchAllAssociative();

				return $this->json( $response , $books );				
			}
			catch( Throwable $e )
			{
				$this->logger->error( 'Get all books failed' , [ 'error' => $e->getMessage() ] );
				
				return $this->json( $response , [ 'error' => 'Internal Server Error' ] , 500 );
			}
		}

		public function get( Request $request , Response $response , array $args ): Response
		{
			$id = (int) ( $args[ 'id' ] ?? 0 );
			
			if( $id <= 0 )
			{
				return $this->json( $response , [ 'error' => 'Invalid book ID' ] , 400 );
			}

			try
			{
				$qb = $this->connection->createQueryBuilder();
				$qb->select( '*' )
					->from( 'books' )
					->where( 'id = :id' )
					->setParameter( 'id' , $id );

				$book = $qb->fetchAssociative();

				if( !$book )
				{
					return $this->json( $response , [ 'error' => 'Book not found' ] , 404 );
				}

				return $this->json( $response , $book );
			}
			catch( Throwable $e )
			{
				$this->logger->error( 'Get book failed' , [ 'error' => $e->getMessage() ] );
				
				return $this->json( $response , [ 'error' => 'Internal Server Error' ] , 500 );
			}
		}

		public function create( Request $request , Response $response ): Response
		{
			$data = (array) $request->getParsedBody();
			$errors = $this->validateBookData( $data );

			if( $errors )
			{
				return $this->json( $response , [ 'errors' => $errors ] , 400 );
			}

			try {
				$qb = $this->connection->createQueryBuilder();
				$qb->insert( 'books' )
					->values([
						'title'       => ':title',
						'author'      => ':author',
						'description' => ':description',
						'created_at'  => 'NOW()',
						'updated_at'  => 'NOW()',
					])
					->setParameters([
						'title'       => trim( $data[ 'title' ] ),
						'author'      => trim( $data[ 'author' ] ),
						'description' => $data[ 'description' ] ?? null,
					]);

				$qb->executeStatement();

				return $this->json( $response , [ 'message' => 'Book created' ] , 201);
			}
			catch( Throwable $e )
			{
				$this->logger->error( 'Create book failed' , [ 'error' => $e->getMessage() ] );
				
				return $this->json( $response , [ 'error' => 'Internal Server Error' ] , 500 );
			}
		}

		public function update( Request $request , Response $response , array $args ): Response
		{
			$id = (int) ( $args[ 'id' ] ?? 0 );
			
			if( $id <= 0 )
			{
				return $this->json( $response , [ 'error' => 'Invalid book ID' ] , 400 );
			}

			$data = (array) $request->getParsedBody();
			$errors = $this->validateBookData( $data , true );

			if( $errors )
			{
				return $this->json( $response , [ 'errors' => $errors ] , 400 );
			}

			try
			{
				$fields = [];
				$params = [ 'id' => $id ];

				foreach ( [ 'title' , 'author' , 'description' ] as $field )
				{
					if( array_key_exists( $field , $data ) )
					{
						$fields[ $field ] = ':' . $field;
						$params[ $field ] = trim( (string) ( $data[ $field ] ?? '' ) );
					}
				}

				if( empty( $fields ) )
				{
					return $this->json( $response , [ 'message' => 'No data to update' ] , 400 );
				}

				$qb = $this->connection->createQueryBuilder();
				$qb->update( 'books' )
					->set( 'updated_at' , 'NOW()' )
					->where( 'id = :id' )
					->setParameters( $params );

				foreach( $fields as $col => $param )
				{
					$qb->set( $col , $param );
				}

				$updated = $qb->executeStatement();

				if( $updated === 0 )
				{
					return $this->json( $response , [ 'message' => 'Book not found or no changes' ] , 204 );
				}

				return $this->json( $response , [ 'message' => 'Book updated' ] , 200 );
			}
			catch( Throwable $e )
			{
				$this->logger->error( 'Update book failed' , [ 'error' => $e->getMessage() ] );
				
				return $this->json( $response , [ 'error' => 'Internal Server Error' ] , 500 );
			}
		}

		public function delete( Request $request , Response $response , array $args ): Response
		{
			$id = (int) ( $args['id'] ?? 0 );
			
			if( $id <= 0 )
			{
				return $this->json( $response , [ 'error' => 'Invalid book ID' ] , 400 );
			}

			try
			{
				$qb = $this->connection->createQueryBuilder();
				$qb->delete( 'books' )
					->where( 'id = :id' )
					->setParameter( 'id' , $id );

				$deleted = $qb->executeStatement();

				if( $deleted === 0 )
				{
					return $this->json( $response , [ 'error' => 'Book not found' ] , 404 );
				}

				return $this->json( $response , [ 'message' => 'Book deleted' ] , 200 );
			}
			catch( Throwable $e )
			{
				$this->logger->error( 'Delete book failed' , [ 'error' => $e->getMessage() ] );
				
				return $this->json( $response , [ 'error' => 'Internal Server Error' ] , 500 );
			}
		}
	}
