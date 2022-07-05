<?php
declare(strict_types=1);

namespace SunflowerFuchs\FakeSso;

use BadMethodCallException;
use Exception;
use InvalidArgumentException;
use SQLite3;

class User
{
    public string $id;
    public ?string $name = null;
    public ?string $email = null;

    /**
     * User constructor
     *
     * Either constructs a new user with the given id, or loads one from the database
     *
     * @param string $id
     *
     * @throws SsoException
     * @throws Exception
     */
    public function __construct(string $id)
    {
        $this->id = $id;

        if (static::exists()) {
            $this->load();
        } else {
            $this->create();
        }
    }

    /**
     * Creates a user and saves it in the database
     *
     * @throws Exception
     */
    protected function create(): void
    {
        $this->name = bin2hex(random_bytes(4)) . ' ' . bin2hex(random_bytes(4));

        // If the passed id is a valid email address, we use that, otherwise we generate a random one
        if (filter_var($this->id, FILTER_VALIDATE_EMAIL, FILTER_FLAG_EMAIL_UNICODE) !== false) {
            $this->email = $this->id;
        } else {
            $this->email = bin2hex(random_bytes(6)) . '@' . bin2hex(random_bytes(5)) . '.localhost';
        }

        $stmt = static::getDb()->prepare('INSERT INTO users (id, name, email) VALUES (:id, :name, :email)');
        $stmt->bindValue(':id', $this->id);
        $stmt->bindValue(':name', $this->name);
        $stmt->bindValue(':email', $this->email);
        $stmt->execute();
    }

    /**
     * Loads a user from the database
     *
     * @throws SsoException
     * @throws Exception
     */
    protected function load(): void
    {
        $stmt = static::getDb()->prepare("SELECT * FROM users WHERE users.id = :id");
        $stmt->bindValue(':id', $this->id);
        $data = $stmt->execute()->fetchArray();
        if (!$data) {
            throw new SsoException('User could not be loaded.', 500);
        }

        $this->name = $data['name'];
        $this->email = $data['email'];
    }

    /**
     * Checks if a user exists in the database
     *
     * @throws SsoException
     * @throws Exception
     */
    protected function exists(): bool
    {
        $stmt = static::getDb()->prepare("SELECT COUNT(*) as CNT FROM users WHERE users.id = :id");
        $stmt->bindValue(':id', $this->id);
        $data = $stmt->execute()->fetchArray();
        return $data && $data['CNT'] == 1;

    }

    /**
     * Returns an instance of the database
     *
     * The returned instance of the database will always be initialized with the users table
     *
     * @return SQLite3
     * @throws Exception
     */
    protected static function getDb(): SQLite3
    {
        static $db = null;

        if (!$db) {
            $db = new SQLite3('/data/users.sqlite', SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
            if (!$db->exec('CREATE TABLE IF NOT EXISTS users (id TEXT PRIMARY KEY, name TEXT, email TEXT)')) {
                throw new Exception('Unknown error while creating users table.');
            }
        }

        return $db;
    }

    /**
     * Returns the access token for the given user
     *
     * @return string
     * @throws SsoException
     * @throws Exception
     */
    public function getToken(): string
    {
        return base64_encode($this->id . '|' . bin2hex(random_bytes(10)));
    }

    /**
     * Load a user by the access token
     *
     * @param string $token
     *
     * @return static
     * @throws SsoException
     */
    public static function getByToken(string $token): self
    {
        $token = base64_decode($token);
        if ($token === false) {
            throw new SsoException("Invalid access token.", 401);
        }
        $sepPos = strpos($token, '|');
        if ($sepPos === false) {
            throw new SsoException("Invalid access token.", 401);
        }

        $id = substr($token, 0, $sepPos);
        return new static($id);
    }

    /**
     * Returns all identifiers from the database
     *
     * @return array
     * @throws Exception
     */
    public static function getAllIdentifiers(): array
    {
        $identifiers = [];
        $result = static::getDb()->query("SELECT id FROM users;");
        while ($id = $result->fetchArray()) {
            $identifiers[] = $id['id'];
        }
        return $identifiers;
    }
}