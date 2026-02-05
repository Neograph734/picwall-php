<?php

namespace PicWall;

class Node
{
  // Topology (Tree Structure)
  public ?Node $left = null;
  public ?Node $right = null;
  public string $splitType = 'NONE'; // 'HORIZONTAL' or 'VERTICAL'

  // Geometry (Calculated later)
  public float $x = 0;
  public float $y = 0;
  public float $width = 0;
  public float $height = 0;

  // Content
  public ?CollageImage $image;

  // The "Natural" Aspect Ratio of this node (and its children combined)
  public float $aspectRatio;

  public function __construct(?CollageImage $image = null)
  {
    $this->image = $image;
    // Leaf nodes start with their image's natural AR
    $this->aspectRatio = $image ? $image->aspectRatio : 1.0;
  }

}