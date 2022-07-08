<?php

declare(strict_types=1);

namespace SunflowerFuchs\FakeSso;

class Config
{
    public static array $defaults = [
        'showKnown' => true,
        'additionalFields' => true,
        'clientSecret' => '',
        'dbFile' => '/data/users.sqlite'
    ];

    protected array $config = [];

    /**
     * Config constructor.
     *
     * Should not be called directly, only induced by {@link getInstance}
     */
    protected function __construct()
    {
        $this->setOption(
            'showKnown',
            filter_var($_ENV['SHOW_KNOWN'] ?? static::$defaults['showKnown'], FILTER_VALIDATE_BOOLEAN)
        );
        $this->setOption(
            'additionalFields',
            filter_var($_ENV['ADDITIONAL_FIELDS'] ?? static::$defaults['additionalFields'], FILTER_VALIDATE_BOOLEAN)
        );
        $this->setOption(
            'clientSecret',
            strval($_ENV['CLIENT_SECRET'] ?? static::$defaults['clientSecret'])
        );
        $this->setOption(
            'dbFile',
            strval($_ENV['DB_FILE'] ?? static::$defaults['dbFile'])
        );
    }

    /**
     * Returns the current instance, or creates a new one if there isn't one
     *
     * @return static
     */
    public static function getInstance(): self
    {
        static $instance = null;

        if ($instance === null) {
            $instance = new static();
        }

        return $instance;
    }

    /**
     * Gets a value from the config
     *
     * @param string $option
     *
     * @return mixed Returns whatever value the option is set to in the config; Defaults to null if option does not exist
     */
    public function getOption(string $option)
    {
        return $this->config[$option] ?? null;
    }

    /**
     * Sets a value in the config
     *
     * @param string $option
     * @param mixed $value
     *
     * @return void
     */
    public function setOption(string $option, $value): void
    {
        $this->config[$option] = $value;
    }

    // Shortcut functions

    /**
     * Shortcut function for showKnown
     *
     * @return bool If not set, defaults to true
     * @codeCoverageIgnore we don't need to test shortcut functions
     */
    public static function showKnown(): bool
    {
        return (bool)static::getInstance()->getOption('showKnown');
    }

    /**
     * Shortcut function for additionalFields
     *
     * @return bool If not set, defaults to true
     * @codeCoverageIgnore we don't need to test shortcut functions
     */
    public static function additionalFields(): bool
    {
        return (bool)static::getInstance()->getOption('additionalFields');
    }

    /**
     * Shortcut function for clientSecret
     *
     * @return string If not set, returns empty string
     * @codeCoverageIgnore we don't need to test shortcut functions
     */
    public static function clientSecret(): string
    {
        return (string)static::getInstance()->getOption('clientSecret');
    }

    /**
     * Shortcut function for dbFile
     *
     * @return string If not set, returns default
     * @codeCoverageIgnore we don't need to test shortcut functions
     */
    public static function dbFile(): string
    {
        return (string)static::getInstance()->getOption('dbFile');
    }
}