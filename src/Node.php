<?php

declare(strict_types=1);

namespace PicWall;

class Node implements NodeInterface
{
  // Topology (Tree Structure)
  public ?NodeInterface $left = null;
  public ?NodeInterface $right = null;
  public string $splitType = 'NONE'; // 'HORIZONTAL' or 'VERTICAL'

  // Geometry (Calculated later)
  public float $x = 0;
  public float $y = 0;
  public float $width = 0;
  public float $height = 0;

  // Content
  public ?CollageImageInterface $image;

  // The "Natural" Aspect Ratio of this node (and its children combined)
  public float $aspectRatio;

  public function __construct(?CollageImageInterface $image = null)
  {
    $this->image = $image;
    // Leaf nodes start with their image's natural AR
    $this->aspectRatio = $image ? $image->aspectRatio : 1.0;
  }

  public function getLeft(): ?NodeInterface { return $this->left; }
  public function getRight(): ?NodeInterface { return $this->right; }
  public function getSplitType(): string { return $this->splitType; }
  public function getX(): float { return $this->x; }
  public function getY(): float { return $this->y; }
  public function getWidth(): float { return $this->width; }
  public function getHeight(): float { return $this->height; }
  public function getImage(): ?CollageImageInterface { return $this->image; }
  public function getAspectRatio(): float { return $this->aspectRatio; }
}