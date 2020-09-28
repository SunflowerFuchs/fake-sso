<?php
declare(strict_types=1);

namespace SunflowerFuchs\FakeSso;

use ErrorException;

class Config
{
    protected const CONFIG_FILE = __DIR__ . '/../conf/config.ini';

    protected array $config = [];

    /**
     * Config constructor.
     *
     * Should not be called directly, only induced by {@link getInstance}
     *
     * @param string $configFile
     * @throws ErrorException when config file is invalid
     */
    protected function __construct(string $configFile)
    {
        if (!file_exists($configFile)) {
            trigger_error("Config file \"${configFile}\" not found", E_USER_WARNING);
            return;
        }

        $config = parse_ini_file($configFile, false, INI_SCANNER_TYPED);
        if ($config === false) {
            throw new ErrorException("Error while parsing config file \"${configFile}\"");
        }

        $this->config = $config;
    }

    /**
     * Returns the current instance, or creates a new one if there isn't one
     *
     * @param string $configFile
     * @return static
     * @throws ErrorException
     */
    public static function getInstance(string $configFile = self::CONFIG_FILE): self
    {
        static $instances = [];

        if ($instances[$configFile] === null) {
            $instances[$configFile] = new static($configFile);
        }

        return $instances[$configFile];
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
     * @throws ErrorException
     */
    public static function showKnown(): bool
    {
        return (bool)static::getInstance()->getOption('showKnown') ?? true;
    }

    /**
     * Shortcut function for allowEmptyPw
     *
     * @return bool If not set, defaults to true
     * @throws ErrorException
     */
    public static function allowEmptyPw(): bool
    {
        return (bool)static::getInstance()->getOption('allowEmptyPw') ?? true;
    }

    /**
     * Shortcut function for additionalFields
     *
     * @return bool If not set, defaults to true
     * @throws ErrorException
     */
    public static function additionalFields(): bool
    {
        return (bool)static::getInstance()->getOption('additionalFields') ?? true;
    }

    /**
     * Shortcut function for clientSecret
     *
     * @return string If not set, returns empty string
     * @throws ErrorException
     */
    public static function clientSecret(): string
    {
        return (string)static::getInstance()->getOption('clientSecret') ?? '';
    }
}