<?php

namespace PicWall;

/**
 * Represents a node in a binary space partitioning tree for collage layout.
 */
interface NodeInterface
{
    /**
     * Returns the left child node, or null if this is a leaf.
     */
    public function getLeft(): ?NodeInterface;

    /**
     * Returns the right child node, or null if this is a leaf.
     */
    public function getRight(): ?NodeInterface;

    /**
     * Returns the split type: 'HORIZONTAL', 'VERTICAL', or 'NONE'.
     */
    public function getSplitType(): string;

    /**
     * Returns the X coordinate of this node's region.
     */
    public function getX(): float;

    /**
     * Returns the Y coordinate of this node's region.
     */
    public function getY(): float;

    /**
     * Returns the width of this node's region.
     */
    public function getWidth(): float;

    /**
     * Returns the height of this node's region.
     */
    public function getHeight(): float;

    /**
     * Returns the image assigned to this node, or null for branch nodes.
     */
    public function getImage(): ?CollageImageInterface;

    /**
     * Returns the natural aspect ratio of this node (and its children combined).
     */
    public function getAspectRatio(): float;
}
