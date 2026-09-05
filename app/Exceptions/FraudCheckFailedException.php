<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown by FraudCheckService::check() when the simulated dependency
 * "fails" (see FraudCheckSettings). UsageController catches this
 * specifically to turn it into a 502, distinct from an unhandled
 * exception, since this represents a real, expected upstream failure
 * mode rather than a bug.
 */
class FraudCheckFailedException extends RuntimeException
{
    //
}
