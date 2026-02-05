# PicWall PHP 🖼️

A smart, weight-aware PHP image collage generator using Binary Space Partitioning.

Unlike traditional masonry grids or simple packers, **TreeNode Collage** builds a balanced layout structure from the bottom up. It respects your images' natural aspect ratios, ensuring **zero cropping** and **zero stretching**.

![License](https://img.shields.io/badge/license-MIT-blue.svg) ![PHP](https://img.shields.io/badge/php-8.0%2B-777bb4.svg)

## ✨ Key Features

* **Zero Crop / Zero Stretch:** Your photos are never cut or distorted. The layout adapts to the images, and images are contained within their calculated boxes.
* **Weighted Layouts:** Give specific images more prominence. A `weight: 2.0` image will naturally occupy roughly 2x the area of a `weight: 1.0` image.
* **Balanced Split Algorithm:** Uses a "Median Weight Split" to ensure images are distributed evenly across the tree, preventing tiny "sliver" images or dead pixels.
* **Smart Optimization:** Runs a Monte Carlo search to find the tree rotation that best fits your target canvas aspect ratio.
* **Dual Rendering:** Render to a GD `resource` (for saving JPEGs) or generating responsive HTML/CSS.

## 📦 Installation

    composer require neograph734/picwall-php

## 🚀 Quick Start
````
<?PHP
use PicWall\CollageGenerator;
use PicWall\CollageImage;
use PicWall\CollageConfig;

// 1. Configure the Generator (Canvas Width, Height, Padding)
$generator = new CollageGenerator(1200, 630, 10); 

// 2. Prepare your images with weights
// Arguments: Path, Original Width, Original Height, Weight
$images = [
    new CollageImage('img/photo1.jpg', 800, 600, 2.0), // Hero image (2x weight)
    new CollageImage('img/photo2.jpg', 600, 800, 1.0),
    new CollageImage('img/photo3.jpg', 1200, 800, 1.0),
    new CollageImage('img/photo4.jpg', 800, 800, 1.5),
];

// 3. Generate the Layout
// This calculates the perfect positions without modifying the images yet.
$layout = $generator->generateBestLayout($images, attempts: 50);

// 4. Render to an Image Resource
$canvas = $generator->renderToImage($layout);

// 5. Save the result
imagejpeg($canvas, 'output_collage.jpg', 90);
imagedestroy($canvas);
````

## 🧠 How it Works
1. The Balanced Weight Tree
Most collage algorithms randomly split lists of images. This often leads to one image getting 50% of the space while 10 others fight for the remaining 50%.

TreeNode Collage analyzes the total "Weight" of your image set and finds the mathematical center. It recursively splits the list so that the left and right branches of the tree always have equal visual weight. This ensures a balanced look where no image is accidentally "starved" of space.

2. Zero-Stretch Rendering
We believe photographers worked hard to frame their shots.

Traditional tools force images to fill a box ( object-fit: cover ), cutting off heads or details.

TreeNode Collage calculates the box, but then fits the image inside it (object-fit: contain). If the aspect ratios don't match perfectly, it adds clean whitespace (matte) to maintain the integrity of the photo.

## ⚙️ Configuration
You can pass a CollageConfig object to fine-tune the behavior.

    <?PHP
    $config = new CollageConfig();
    
    // Prevent images from becoming too small (percentage of total area)
    $config->minAreaPercentage = 0.05; // 5% minimum
    
    // Increase strictness on aspect ratio matching (less whitespace, potentially harder to fit)
    $config->aspectRatioPenaltyMultiplier = 1000;
    
    $generator = new CollageGenerator(1200, 630, 10, $config);

## 🌐 HTML Rendering
If you want to display the collage on a webpage without generating a flat JPEG, use renderToHTML. This generates a responsive div structure.

    <?PHP
    echo $generator->renderToHTML($layout);
Note: The HTML output uses absolute positioning relative to the container and object-fit: contain to preserve the layout integrity.

## 📄 License
MIT License. See LICENSE for details.