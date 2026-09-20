<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown for unrecoverable NewsAPI client errors - currently, missing
 * configuration - that must stop a fetch before any HTTP request is made.
 *
 * Per-request failures (rate limits, timeouts, a bad source) are
 * deliberately NOT exceptions: they are recorded on the client and
 * surfaced via NewsApiClient::getLastErrorMessage(), so that one bad
 * source or category does not abort an otherwise successful batch fetch.
 */
class NewsApiException extends RuntimeException {}
