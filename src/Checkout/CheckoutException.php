<?php

declare(strict_types=1);

namespace TechNest\Checkout;

use DomainException;

/** The order could not be placed; the message is safe to show the customer. */
final class CheckoutException extends DomainException {}
