<?php
declare(strict_types=1);

namespace MageOS\ShoppingFeed\Test\Integration;

use MageOS\ShoppingFeed\Model\Feed;
use MageOS\ShoppingFeed\Model\Promotions\Provider\Collection;
use Magento\Framework\App\ResourceConnection;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 */
class PromotionConfigurationTest extends TestCase
{
    public function testPromotionConfigurationCanBeCreatedAndUpdated(): void
    {
        $om = Bootstrap::getObjectManager();
        $feed = $om->create(Feed::class)->setType('google_shopping')->setStoreId(1)
            ->setName('Promotion configuration regression')->setStatus(0)->setUseMicrodata(0)
            ->setSchedules([])->setUploads([])->save();
        $resource = $om->get(ResourceConnection::class);
        $table = $resource->getTableName('mageos_shopping_feed_feed_config');
        $connection = $resource->getConnection();
        $where = ['feed_id = ?' => $feed->getId(), 'path = ?' => 'promotions_provider_widget'];
        $connection->delete($table, $where);

        $provider = $om->get(Collection::class);
        $first = ['counter' => 1, 'promotion' => [7 => ['include' => 1, 'title' => 'First']]];
        $provider->updatePromotionsData($feed, $first);
        self::assertSame($first, $feed->getConfig('promotions_provider_widget'));
        $second = ['counter' => 2, 'promotion' => [7 => ['include' => 0, 'title' => 'Updated']]];
        $provider->updatePromotionsData($feed, $second);
        self::assertSame($second, $feed->getConfig('promotions_provider_widget'));
        self::assertSame(1, (int)$connection->fetchOne(
            $connection->select()->from($table, 'COUNT(*)')->where('feed_id = ?', $feed->getId())
                ->where('path = ?', 'promotions_provider_widget')
        ));
    }
}
