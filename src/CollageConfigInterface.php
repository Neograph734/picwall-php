<?php

namespace PicWall;

/**
 * Configuration contract for collage generation parameters.
 */
interface CollageConfigInterface
{
    /**
     * Returns the number of layout optimization attempts.
     */
    public function getAttempts(): int;

    /**
     * Returns the padding between images in pixels.
     */
    public function getPadding(): int;

    /**
     * Returns the JPEG quality (1-100).
     */
    public function getJpegQuality(): int;

    /**
     * Validates configuration values.
     *
     * @throws \InvalidArgumentException If any configuration value is invalid
     */
    public function validate(): void;
}
