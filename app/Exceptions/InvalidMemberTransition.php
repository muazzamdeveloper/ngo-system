<?php

namespace App\Exceptions;

use RuntimeException;

/** Thrown when a member's status cannot move to the requested status. */
class InvalidMemberTransition extends RuntimeException
{
}
