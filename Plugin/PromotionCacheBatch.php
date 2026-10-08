<?php
declare(strict_types=1);

namespace MageOS\ShoppingFeed\Plugin;

use MageOS\ShoppingFeed\Model\Generator;
use MageOS\ShoppingFeed\Model\Promotions\Provider;

class PromotionCacheBatch
{
    public function __construct(private Provider $provider)
    {
    }

    public function aroundRun(Generator $subject, callable $proceed)
    {
        $this->provider->beginCacheBatch();
        try {
            return $proceed();
        } finally {
            // Checkpoints and failed runs also retain the completed product cache entries.
            $this->provider->endCacheBatch();
        }
    }
}
