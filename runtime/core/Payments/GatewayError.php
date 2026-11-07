<?php
declare(strict_types=1);

namespace SiteRuntime\Payments;

/** A gateway call failed (network, credentials, unexpected answer). The message is safe to show to the site owner. */
final class GatewayError extends \RuntimeException
{
}
