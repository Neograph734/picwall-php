<?php

namespace PicWall;

/**
 * Represents source image data with its dimensions and weight.
 *
 * This class encapsulates all metadata needed for a single image in the collage,
 * including its path, dimensions, weight (importance), and calculated aspect ratio.
 */
class CollageImage implements CollageImageInterface
{
  /** @var string The file path or URL of the image */
  public string $path;

  /** @var int Original width in pixels */
  public int $width;

  /** @var int Original height in pixels */
  public int $height;

  /** @var float Importance of the image (affects allocated area in collage) */
  public float $weight;

  /** @var float Calculated aspect ratio (width divided by height) */
  public float $aspectRatio;

  /**
   * Creates a new CollageImage instance.
   *
   * @param string $path The file path or URL of the image
   * @param int $width Original width in pixels
   * @param int $height Original height in pixels
   * @param float $weight Importance factor (default: 1.0)
   */
  public function __construct(string $path, int $width, int $height, float $weight = 1.0)
  {
    $this->path = $path;
    $this->width = $width;
    $this->height = $height;
    $this->weight = $weight;
    $this->aspectRatio = ($height > 0) ? $width / $height : 1.0;
  }

  public function getPath(): string { return $this->path; }
  public function getWidth(): int { return $this->width; }
  public function getHeight(): int { return $this->height; }
  public function getWeight(): float { return $this->weight; }
  public function getAspectRatio(): float { return $this->aspectRatio; }
}
