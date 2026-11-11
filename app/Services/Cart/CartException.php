<?php

namespace App\Services\Cart;

use RuntimeException;

/**
 * A cart change the shopper asked for that cannot be made; the message is shown to them.
 */
class CartException extends RuntimeException {}
