<?php

namespace App\Domain\Content\Media;

use RuntimeException;

/** A file that is not an image the platform accepts; the message says why. */
final class InvalidImage extends RuntimeException {}
