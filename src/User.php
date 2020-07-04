<?php

namespace Lamano\FakeSso;

use BadMethodCallException;
use Exception;
use InvalidArgumentException;
use SQLite3;

class User {
	protected static array $cache = [];

	public ?string $id    = null;
	public ?string $name  = null;
	public ?string $email = null;

	/**
	 * User constructor
	 *
	 * Either constructs a new user with the given id, or loads one from the database
	 *
	 * @param string $id
	 *
	 * @throws Exception
	 */
	public function __construct( string $id ) {
		$this->id = $id;

		if( static::exists() ) {
			$this->load();
		}
		else {
			$this->create();
		}
	}

	/**
	 * Creates a user and saves it in the database
	 *
	 * @throws Exception
	 */
	protected function create() : void {
		if( is_null( $this->id ) ) {
			throw new BadMethodCallException( __FUNCTION__ . ' called without $this->id being set.' );
		}

		$data                       = [];
		$data['id']                 = $this->id;
		$data['name']               = $this->name = bin2hex( random_bytes( 4 ) ) . ' ' . bin2hex( random_bytes( 4 ) );
		$data['email']              = $this->email = bin2hex( random_bytes( 6 ) ) . '@' . bin2hex( random_bytes( 5 ) ) . '.localhost';
		static::$cache[ $this->id ] = $data;

		$this->getDb()->query( "INSERT INTO users (id, name, email) VALUES ('${data['id']}', '${data['name']}', '${data['email']}')" );
	}

	/**
	 * Loads a user from the database
	 *
	 * @throws Exception
	 */
	protected function load() : void {
		if( is_null( $this->id ) ) {
			throw new BadMethodCallException( __FUNCTION__ . ' called without $this->id being set.' );
		}

		$id = $this->id;
		if( !isset( self::$cache[ $id ] ) ) {
			self::$cache = $this->getDb()->query( "SELECT * FROM users WHERE users.id = '${id}'" )->fetchArray();
		}
		$data = self::$cache[ $id ];

		$this->id    = $data['id'];
		$this->name  = $data['name'];
		$this->email = $data['email'];
	}

	/**
	 * Checks if a user exists in the database
	 *
	 * @throws Exception
	 */
	protected function exists() : bool {
		if( is_null( $this->id ) ) {
			throw new BadMethodCallException( __FUNCTION__ . ' called without $this->id being set.' );
		}

		$id = $this->id;
		if( !isset( self::$cache[ $id ] ) ) {
			self::$cache[ $id ] = $this->getDb()->query( "SELECT * FROM users WHERE users.id = '${id}'" )->fetchArray();
		}

		return is_array( self::$cache[ $id ] );
	}

	/**
	 * Returns an instance of the database
	 *
	 * The returned instance of the database will always be initialized with the users table
	 *
	 * @return SQLite3
	 * @throws Exception
	 */
	protected function getDb() : SQLite3 {
		static $db = null;

		if( !$db ) {
			$db = new SQLite3( '/data/users.sqlite', SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE );
			if( !$db->exec( 'CREATE TABLE IF NOT EXISTS users (id TEXT PRIMARY KEY, name TEXT, email TEXT)' ) ) {
				throw new Exception( 'Unknown error while creating users table.' );
			}
		}

		return $db;
	}

	/**
	 * Returns the access token for the given user
	 *
	 * @return string
	 * @throws Exception
	 */
	public function getToken() {
		return base64_encode( $this->id . '|' . bin2hex( random_bytes( 10 ) ) );
	}

	/**
	 * Load a user by the access token
	 *
	 * @param string $token
	 *
	 * @return static
	 * @throws Exception
	 */
	public static function getByToken( string $token ) : self {
		if( empty( $token ) ) {
			throw new InvalidArgumentException( "Invalid code" );
		}
		$token  = base64_decode( $token );
		$sepPos = strpos( $token, '|' );
		if( $sepPos === false ) {
			throw new InvalidArgumentException( "Invalid code" );
		}

		$id = substr( $token, 0, $sepPos );

		return new static( $id );
	}
}