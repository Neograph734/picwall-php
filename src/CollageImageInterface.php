<?php

declare(strict_types=1);

namespace PicWall;

/**
 * Represents source image data with its dimensions and weight.
 */
interface CollageImageInterface
{
    /**
     * Returns the file path or URL of the image.
     */
    public function getPath(): string;

    /**
     * Returns the original width in pixels.
     */
    public function getWidth(): int;

    /**
     * Returns the original height in pixels.
     */
    public function getHeight(): int;

    /**
     * Returns the importance weight of the image.
     */
    public function getWeight(): float;

    /**
     * Returns the calculated aspect ratio (width / height).
     */
    public function getAspectRatio(): float;
}
