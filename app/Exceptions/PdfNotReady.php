<?php

namespace App\Exceptions;

use RuntimeException;

/** Thrown when a PDF cannot be produced yet (library missing or document not built). */
class PdfNotReady extends RuntimeException
{
}
