<?php
declare(strict_types=1);

namespace SunflowerFuchs\FakeSso;

class Config
{
    protected static array $defaults = [
        'showKnown' => true,
        'additionalFields' => true,
        'clientSecret' => '',
    ];

    protected array $config = [];

    /**
     * Config constructor.
     *
     * Should not be called directly, only induced by {@link getInstance}
     */
    protected function __construct()
    {
        $this->config = [
            'showKnown' => filter_var($_ENV['SHOW_KNOWN'] ?? static::$defaults['showKnown'], FILTER_VALIDATE_BOOLEAN),
            'additionalFields' => filter_var($_ENV['ADDITIONAL_FIELDS'] ?? static::$defaults['additionalFields'], FILTER_VALIDATE_BOOLEAN),
            'clientSecret' => strval($_ENV['CLIENT_SECRET'] ?? static::$defaults['clientSecret']),
        ];
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
     * Returns the entire config as an array
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->config;
    }

    /**
     * Gets a single value from the config
     *
     * @param string $option
     *
     * @return mixed Returns whatever value the option is set to in the config; Defaults to null if option does not exists
     */
    public function getOption(string $option)
    {
        return $this->config[$option] ?? null;
    }

    // Shortcut functions

    /**
     * Shortcut function for showKnown
     *
     * @return bool If not set, defaults to true
     */
    public static function showKnown(): bool
    {
        return (bool)static::getInstance()->getOption('showKnown') ?? static::$defaults['showKnown'];
    }

    /**
     * Shortcut function for additionalFields
     *
     * @return bool If not set, defaults to true
     */
    public static function additionalFields(): bool
    {
        return (bool)static::getInstance()->getOption('additionalFields') ?? static::$defaults['additionalFields'];
    }

    /**
     * Shortcut function for clientSecret
     *
     * @return string If not set, returns empty string
     */
    public static function clientSecret(): string
    {
        return (string)static::getInstance()->getOption('clientSecret') ?? static::$defaults['clientSecret'];
    }
}