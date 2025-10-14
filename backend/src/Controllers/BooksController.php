<?php

	declare( strict_types = 1 );

	namespace App\Controllers;

	use Doctrine\DBAL\Connection;
	use Psr\Http\Message\ResponseInterface;
	use Psr\Http\Message\ServerRequestInterface;

	class BooksController
	{
		private Connection $db;

		public function __construct(Connection $db)
		{
			$this->db = $db;
		}

		// Get all books
		public function list( ServerRequestInterface $req , ResponseInterface $res ): ResponseInterface
		{
			$rows = $this->db->fetchAllAssociative( 'SELECT * FROM Books ORDER BY id' );
			$res->getBody()->write( json_encode( $rows ) );
			
			return $res->withHeader( 'Content-Type' , 'application/json' );
		}

		// Get specific book
		public function get( ServerRequestInterface $req , ResponseInterface $res , array $args ): ResponseInterface
		{
			$id = (int) $args[ 'id' ];
			$row = $this->db->fetchAssociative( 'SELECT * FROM Books WHERE id = ?' , [ $id ] );
			
			if( !$row )
			{
				$res->getBody()->write(
					json_encode(
						[
							'error' => 'Not found'
						]
					)
				);
				
				return $res->withStatus(404)->withHeader( 'Content-Type' , 'application/json' );
			}
			
			$res->getBody()->write( json_encode( $row ) );
			
			return $res->withHeader( 'Content-Type' , 'application/json' );
		}

		// Create a new book
		public function create( ServerRequestInterface $req , ResponseInterface $res ): ResponseInterface
		{
			$data = (array) $req->getParsedBody();
			
			$this->db->insert( 'Books' , [
				'title'       => $data[ 'title' ] ?? '',
				'author'      => $data[ 'author' ] ?? '',
				'description' => $data[ 'description' ] ?? '',
				'created_at'  => (new \DateTime())->format( 'Y-m-d H:i:s' ),
				'updated_at'  => (new \DateTime())->format( 'Y-m-d H:i:s' ),
			]);
			
			$id = $this->db->lastInsertId();
			$res->getBody()->write(
				json_encode(
					[
						'id' => $id
					]
				)
			);
			
			return $res->withStatus(201)->withHeader( 'Content-Type' , 'application/json' );
		}

		// Update existing book
		public function update( ServerRequestInterface $req , ResponseInterface $res , array $args ): ResponseInterface
		{
			$id = (int) $args[ 'id' ];
			$data = (array) $req->getParsedBody();

			$affected = $this->db->update( 'Books' , [
				'title'       => $data[ 'title' ] ?? '',
				'author'      => $data[ 'author' ] ?? '',
				'description' => $data[ 'description' ] ?? '',
				'updated_at'  => (new \DateTime())->format( 'Y-m-d H:i:s' ),
			], [ 'id' => $id ] );

			if( $affected === 0 )
			{
				$res->getBody()->write(
					json_encode(
						[
							'error' => 'Not found or no changes' 
						]
					)
				);
				
				return $res->withStatus(404)->withHeader( 'Content-Type' , 'application/json' );
			}
			
			$res->getBody()->write(
				json_encode(
					[
						'message' => 'Updated'
					]
				)
			);
			
			return $res->withHeader( 'Content-Type' , 'application/json' );
		}

		// Delete book
		public function delete( ServerRequestInterface $req , ResponseInterface $res , array $args ): ResponseInterface
		{
			$id = (int)$args[ 'id' ];
			$deleted = $this->db->delete( 'Books' , [ 'id' => $id ] );
			
			if( $deleted === 0 )
			{
				$res->getBody()->write(
					json_encode(
						[
							'error' => 'Not found'
						]
					)
				);
				
				return $res->withStatus(404)->withHeader( 'Content-Type' , 'application/json' );
			}
			
			$res->getBody()->write(
				json_encode(
					[
						'message' => 'Deleted'
					]
				)
			);
			
			return $res->withHeader( 'Content-Type' , 'application/json' );
		}
	}