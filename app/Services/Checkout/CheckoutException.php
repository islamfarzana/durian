<?php

namespace App\Services\Checkout;

use RuntimeException;

/**
 * An order that cannot be placed as the bag stands; the message is shown to the customer.
 */
class CheckoutException extends RuntimeException {}
