<?php

namespace PicWall;

use InvalidArgumentException;

/**
 * Configuration class for collage generation parameters.
 *
 * Holds tunable constants for rendering and layout optimization.
 */
class CollageConfig
{
  /** @var int Default number of layout optimization attempts */
  public int $attempts = 40;

  /** @var int Default padding between images in pixels */
  public int $padding = 0;

  /** @var int JPEG quality for rendered images (1-100) */
  public int $jpegQuality = 90;

  /**
   * Creates a new configuration with default values.
   *
   * All properties are public and can be modified after construction.
   */
  public function __construct()
  {
    // Default values are assigned via property initialization
  }

  /**
   * Creates a configuration instance from an array of values.
   *
   * Only known properties will be set; unknown keys are ignored.
   *
   * @param array<string, mixed> $options Associative array of configuration values
   * @return self New configuration instance with specified values
   */
  public static function fromArray(array $options): self
  {
    $config = new self();

    foreach ($options as $key => $value) {
      if (property_exists($config, $key)) {
        $config->$key = $value;
      }
    }

    return $config;
  }

  /**
   * Validates configuration values and throws exceptions for invalid settings.
   *
   * @throws \InvalidArgumentException If any configuration value is invalid
   * @return void
   */
  public function validate(): void
  {
    if ($this->jpegQuality < 1 || $this->jpegQuality > 100) {
      throw new InvalidArgumentException('jpegQuality must be between 1 and 100');
    }

    if ($this->attempts < 1) {
      throw new InvalidArgumentException('attempts must be at least 1');
    }

    if ($this->padding < 0) {
      throw new InvalidArgumentException('padding must be non-negative');
    }
  }
}
