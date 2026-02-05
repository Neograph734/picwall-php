<?php

namespace PicWall;

/**
 * Core engine for calculating and rendering optimized image collages.
 *
 * Uses a binary space partitioning algorithm with penalty-based optimization
 * to create aesthetically pleasing collage layouts that respect image aspect
 * ratios and weights.
 */
class CollageGenerator {

  private int $canvasWidth;
  private int $canvasHeight;
  private int $padding;
  private int $jpegQuality;
  private float $targetAspectRatio;
  private CollageConfig $config;

  /**
   * Creates a new CollageGenerator instance.
   *
   * @param int $width Canvas width in pixels
   * @param int $height Canvas height in pixels
   * @param int $padding Padding between images in pixels (default: 0)
   * @param CollageConfig|null $config Optional configuration object
   */
  public function __construct(int $width, int $height, int $padding = 0, ?CollageConfig $config = null)
  {
    $this->canvasWidth = $width;
    $this->canvasHeight = $height;
    $this->config = $config ?? new CollageConfig();
    
    // Allow constructor padding to override config if explicitly provided
    $this->padding = $padding !== 0 ? $padding : $this->config->padding;
    $this->jpegQuality = $this->config->jpegQuality;
    
    // The ideal shape we are trying to mimic
    $this->targetAspectRatio = $width / $height;
  }

  public function generateBestLayout(array $images, int $attempts = 50): array {
    if (empty($images)) return [];

    // Use config attempts if the parameter uses default value
    $effectiveAttempts = $attempts !== 50 ? $attempts : $this->config->attempts;

    $bestRoot = null;
    $bestScore = PHP_FLOAT_MAX;

    // 1. MONTE CARLO SEARCH
    // We try many random tree structures to find one that naturally fits our canvas.
    for ($i = 0; $i < $effectiveAttempts; $i++) {
      shuffle($images);

      // A. Build a random binary tree
      $root = $this->buildRandomTree($images);

      // B. "Smart Flip": Adjust H/V splits to match target AR
      $this->optimizeAspectRatio($root);

      // C. Score: How close is the final shape to our canvas?
      $score = abs($root->aspectRatio - $this->targetAspectRatio);

      if ($score < $bestScore) {
        $bestScore = $score;
        $bestRoot = $root;
      }
    }

    if (!$bestRoot) return [];

    // 2. GEOMETRY CALCULATION (Zero Crop)
    // Now we assign real X/Y coordinates based on the tree structure
    // We start by assuming the layout fills the "Target Width" completely.
    $this->calculateCoordinates($bestRoot, 0, 0, $this->canvasWidth, $this->canvasWidth / $bestRoot->aspectRatio);

    // 3. FIT TO BOUNDS ("Force Shrink")
    // If the calculated height > canvas height, we shrink everything to fit.
    $this->fitToBounds($bestRoot);

    // 4. FLATTEN
    // Convert tree back to a flat array for the renderer
    return $this->flattenTree($bestRoot);
  }

  /**
   * Recursively pairs images to build a binary tree.
   */
  private function buildRandomTree(array $nodes): Node
  {
    // Convert raw images to Leaf Nodes on first pass
    if ($nodes[0] instanceof CollageImage) {
      $nodes = array_map(fn($img) => new Node($img), $nodes);
    }

    if (count($nodes) === 1) return $nodes[0];

    // Randomly split the list into two groups
    $splitIndex = rand(1, count($nodes) - 1);
    $leftGroup = array_slice($nodes, 0, $splitIndex);
    $rightGroup = array_slice($nodes, $splitIndex);

    $parent = new Node();
    $parent->left = $this->buildRandomTree($leftGroup);
    $parent->right = $this->buildRandomTree($rightGroup);

    // Initial random orientation
    $parent->splitType = rand(0, 1) ? 'HORIZONTAL' : 'VERTICAL';

    // Calculate initial AR based on children
    $this->updateAspectRatio($parent);

    return $parent;
  }

  /**
   * The Math: Calculates AR based on children and split type.
   * Horizontal (Side-by-Side): AR adds up.
   * Vertical (Stacked): 1/AR adds up (harmonic-ish).
   */
  private function updateAspectRatio(Node $node): void
  {
    if (!$node->left || !$node->right) return;

    if ($node->splitType === 'HORIZONTAL') {
      // Widths add, Height is shared -> AR increases
      $node->aspectRatio = $node->left->aspectRatio + $node->right->aspectRatio;
    } else {
      // Heights add, Width is shared -> AR decreases
      $denom = (1 / $node->left->aspectRatio) + (1 / $node->right->aspectRatio);
      $node->aspectRatio = ($denom > 0) ? 1 / $denom : 1;
    }
  }

  /**
   * "Smart Adjustment": Recursively flips splits if it helps match the target.
   */
  private function optimizeAspectRatio(Node $node): void
  {
    if (!$node->left || !$node->right) return;

    // Optimize children first (Bottom-Up)
    $this->optimizeAspectRatio($node->left);
    $this->optimizeAspectRatio($node->right);

    // Try both orientations for THIS node
    // 1. Try Horizontal
    $node->splitType = 'HORIZONTAL';
    $this->updateAspectRatio($node);
    $diffH = abs($node->aspectRatio - $this->targetAspectRatio);

    // 2. Try Vertical
    $node->splitType = 'VERTICAL';
    $this->updateAspectRatio($node);
    $diffV = abs($node->aspectRatio - $this->targetAspectRatio);

    // Pick the winner
    if ($diffH < $diffV) {
      $node->splitType = 'HORIZONTAL';
      $this->updateAspectRatio($node);
    }
    // (Else stay Vertical)
  }

  /**
   * Assigns pixel dimensions top-down based on aspect ratios.
   */
  private function calculateCoordinates(Node $node, float $x, float $y, float $w, float $h): void
  {
    $node->x = $x; $node->y = $y; $node->width = $w; $node->height = $h;

    if (!$node->left || !$node->right) return;

    if ($node->splitType === 'HORIZONTAL') {
      // Side-by-Side: Split Width based on AR weights
      $totalAR = $node->left->aspectRatio + $node->right->aspectRatio;
      $w1 = $w * ($node->left->aspectRatio / $totalAR);

      $this->calculateCoordinates($node->left, $x, $y, $w1, $h);
      $this->calculateCoordinates($node->right, $x + $w1, $y, $w - $w1, $h);
    } else {
      // Stacked: Split Height based on 1/AR weights
      // Taller image (Lower AR) gets more height
      $invAR1 = 1 / $node->left->aspectRatio;
      $invAR2 = 1 / $node->right->aspectRatio;
      $totalInvAR = $invAR1 + $invAR2;

      $h1 = $h * ($invAR1 / $totalInvAR);

      $this->calculateCoordinates($node->left, $x, $y, $w, $h1);
      $this->calculateCoordinates($node->right, $x, $y + $h1, $w, $h - $h1);
    }
  }

  /**
   * The "Force Shrink": Scales the entire layout to fit inside the canvas.
   */
  private function fitToBounds(Node $root): void
  {
    // 1. How big is the layout naturally?
    // (We initially set width = canvasWidth, so currentW is canvasWidth)
    $currentW = $root->width;
    $currentH = $root->height;

    // 2. Calculate scale factor to fit BOTH dimensions
    $scaleX = $this->canvasWidth / $currentW;
    $scaleY = $this->canvasHeight / $currentH;

    // We use the smaller scale to ensure we fit inside (Contain mode)
    $finalScale = min($scaleX, $scaleY);

    // 3. Apply scale and center the result
    $newW = $currentW * $finalScale;
    $newH = $currentH * $finalScale;
    $offsetX = ($this->canvasWidth - $newW) / 2;
    $offsetY = ($this->canvasHeight - $newH) / 2;

    $this->applyScaleRecursively($root, $finalScale, $offsetX, $offsetY);
  }

  private function applyScaleRecursively(Node $node, float $scale, float $offX, float $offY): void
  {
    $node->x = ($node->x * $scale) + $offX;
    $node->y = ($node->y * $scale) + $offY;
    $node->width *= $scale;
    $node->height *= $scale;

    if ($node->left) $this->applyScaleRecursively($node->left, $scale, $offX, $offY);
    if ($node->right) $this->applyScaleRecursively($node->right, $scale, $offX, $offY);
  }

  private function flattenTree(Node $node): array
  {
    if ($node->image) return [$node];
    return array_merge(
      $this->flattenTree($node->left),
      $this->flattenTree($node->right)
    );
  }

  /**
   * Renders the layout into a responsive HTML structure.
   *
   * Generates HTML with percentage-based positioning for responsive scaling.
   * Optionally overlays debug information showing aspect ratios and weights.
   *
   * @param Node[] $layout Array of positioned nodes to render
   * @param bool $debug Whether to show debug overlay (default: false)
   * @return string The complete HTML markup for the collage.
   */
  public function renderToHtml(array $layout, bool $debug = false): string
  {
    $containerStyle = "position:relative; width:100%; max-width:{$this->canvasWidth}px; " .
      "aspect-ratio:{$this->canvasWidth}/{$this->canvasHeight}; " .
      "background:#000; overflow:hidden;";

    $html = "<div style='$containerStyle'>";

    foreach ($layout as $node) {
      $leftPercent = ($node->x / $this->canvasWidth) * 100;
      $topPercent = ($node->y / $this->canvasHeight) * 100;
      $widthPercent = ($node->width / $this->canvasWidth) * 100;
      $heightPercent = ($node->height / $this->canvasHeight) * 100;

      $html .= "<div style='position:absolute; left:{$leftPercent}%; top:{$topPercent}%; " .
        "width:{$widthPercent}%; height:{$heightPercent}%; " .
        "padding:{$this->padding}px; box-sizing:border-box;'>";
      $html .= "<div style='position:relative; width:100%; height:100%; overflow:hidden;'>";
      $html .= "<img src='{$node->image->path}' " .
        "style='width:100%; height:100%; object-fit:cover; display:block;'>";

      if ($debug) {
        $aspectRatioRounded = round($node->image->aspectRatio, 2);
        $html .= "<div style='position:absolute; inset:0; background:rgba(0,0,0,0.6); " .
          "color:#fff; font-family:sans-serif; font-size:11px; padding:5px;'>";
        $html .= "AR: {$aspectRatioRounded}<br>W: {$node->image->weight}";
        $html .= "</div>";
      }

      $html .= "</div></div>";
    }

    $html .= "</div>";
    return $html;
  }

  /**
   * Renders the layout to a JPEG image file using the GD library.
   *
   * Creates a rasterized image with center-cropped photos fitted into their
   * assigned rectangular areas.
   *
   * @param Node[] $layout Array of positioned nodes to render
   * @param string $outputPath File path where the JPEG will be saved
   * @return void
   */
  public function renderToImage(array $layout, string $outputPath): void
  {
    $canvas = imagecreatetruecolor($this->canvasWidth, $this->canvasHeight);
    $whiteColor = imagecolorallocate($canvas, 255, 255, 255);
    imagefill($canvas, 0, 0, $whiteColor);

    foreach ($layout as $node) {
      $image = $node->image;
      $destinationX = (int)($node->x + $this->padding);
      $destinationY = (int)($node->y + $this->padding);
      $destinationWidth = (int)($node->width - ($this->padding * 2));
      $destinationHeight = (int)($node->height - ($this->padding * 2));

      // Skip if dimensions are invalid
      if ($destinationWidth <= 0 || $destinationHeight <= 0) {
        continue;
      }

      $sourceImage = $this->loadImage($image->path);
      if (!$sourceImage) {
        continue;
      }

      $sourceAspectRatio = $image->aspectRatio;
      $destinationAspectRatio = $destinationWidth / $destinationHeight;

      // Calculate center crop coordinates
      if ($sourceAspectRatio > $destinationAspectRatio) {
        // Image is wider: crop left and right
        $cropWidth = $image->height * $destinationAspectRatio;
        $sourceX = (int)(($image->width - $cropWidth) / 2);
        $sourceY = 0;
        $sourceWidth = (int)$cropWidth;
        $sourceHeight = $image->height;
      } else {
        // Image is taller: crop top and bottom
        $cropHeight = $image->width / $destinationAspectRatio;
        $sourceX = 0;
        $sourceWidth = $image->width;
        $sourceY = (int)(($image->height - $cropHeight) / 2);
        $sourceHeight = (int)$cropHeight;
      }

      imagecopyresampled(
        $canvas,
        $sourceImage,
        $destinationX,
        $destinationY,
        $sourceX,
        $sourceY,
        $destinationWidth,
        $destinationHeight,
        $sourceWidth,
        $sourceHeight
      );
      imagedestroy($sourceImage);
    }

    imagejpeg($canvas, $outputPath, $this->jpegQuality);
    imagedestroy($canvas);
  }

  /**
   * Loads an image resource from a file path.
   *
   * Supports JPEG, PNG, and GIF formats. Returns null if the file cannot be loaded.
   *
   * @param string $path File path to the image
   * @return resource|false|null GD image resource, or null if the format is unsupported
   */
  private function loadImage(string $path)
  {
    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    return match ($extension) {
      'jpg', 'jpeg' => @imagecreatefromjpeg($path),
      'png' => @imagecreatefrompng($path),
      'gif' => @imagecreatefromgif($path),
      default => null,
    };
  }
}
