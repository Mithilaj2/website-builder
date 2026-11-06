<?php
declare(strict_types=1);

namespace App\Core;

/** User-facing upload/validation problem; its message is safe to show. */
final class UploadException extends \RuntimeException
{
}
