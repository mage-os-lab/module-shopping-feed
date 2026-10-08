<?php
declare(strict_types=1);

namespace MageOS\ShoppingFeed\Test\Integration;

use MageOS\ShoppingFeed\Model\Feed;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Framework\App\ResourceConnection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 */
class FeedStatePersistenceTest extends TestCase
{
    /** @dataProvider stateUpdates */
    #[DataProvider('stateUpdates')]
    public function testStateUpdateDoesNotDisableLaterConfigSaves(string $method, $value): void
    {
        $om = Bootstrap::getObjectManager();
        $feed = $om->create(Feed::class)->setType('generic')->setStoreId(1)
            ->setName('Feed state regression')->setStatus(0)->setUseMicrodata(0)
            ->setSchedules([])->setUploads([])->save();
        $feed->$method($value);
        $feed->getConfig()->setData('general_currency', 'CAD');
        $feed->save();

        $reloaded = $om->create(Feed::class)->load($feed->getId());
        self::assertSame('CAD', $reloaded->getConfig('general_currency'));
        self::assertFalse((bool)$feed->getData('no_after_save'));
    }

    public static function stateUpdates(): array
    {
        return [
            'status' => ['saveStatus', 0],
            'messages' => ['saveMessages', ['exported' => 2]],
        ];
    }

    public function testStatusSaveDoesNotSuppressTimestampOnLaterEdit(): void
    {
        $om = Bootstrap::getObjectManager();
        $feed = $om->create(Feed::class)->setType('generic')->setStoreId(1)
            ->setName('Timestamp state regression')->setStatus(0)->setUseMicrodata(0)
            ->setSchedules([])->setUploads([])->save();
        $resource = $om->get(ResourceConnection::class);
        $oldTimestamp = '2000-01-01 00:00:00';
        $resource->getConnection()->update(
            $resource->getTableName('mageos_shopping_feed_feed'),
            ['updated_at' => $oldTimestamp],
            ['id = ?' => $feed->getId()]
        );
        $feed->load($feed->getId());
        $feed->saveStatus(0);
        self::assertSame($oldTimestamp, $feed->getUpdatedAt());
        $feed->setName('Edited after status update')->save();
        self::assertNotSame($oldTimestamp, $om->create(Feed::class)->load($feed->getId())->getUpdatedAt());
    }
}
