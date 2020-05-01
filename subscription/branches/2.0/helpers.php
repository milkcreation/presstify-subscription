<?php declare(strict_types=1);

use tiFy\Plugins\Subscription\Subscription;

if (!function_exists('subscription')) {
    function subscription(): ?Subscription
    {
        try {
            return Subscription::instance();
        } catch (Exception $e) {
            return null;
        }
    }
}
