<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when persisting an article would violate the unique `url`
 * constraint. This is expected to be rare: ArticleImportService already
 * checks for an existing row before inserting, so this normally only
 * fires when a concurrent import process wins a race for the same URL
 * between that check and the insert.
 *
 * Keeping this as a dedicated exception - rather than letting
 * Illuminate\Database\UniqueConstraintViolationException escape the
 * repository - means callers depend only on the repository contract, not
 * on Eloquent's exception types.
 */
class DuplicateArticleException extends RuntimeException {}
