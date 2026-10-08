<?php
declare(strict_types=1);

namespace MageOS\ShoppingFeed\Test\Unit\Model\Inventory;

use MageOS\ShoppingFeed\Model\Inventory\Api;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Module\Manager;
use Magento\Framework\ObjectManagerInterface;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
class ApiTest extends TestCase
{
    public function testWebsiteQuantityExcludesDisabledPhysicalSources(): void
    {
        $stock = $this->createMock(\Magento\InventoryApi\Api\Data\StockInterface::class);
        $stock->method('getStockId')->willReturn(5);
        $resolver = $this->createMock(\Magento\InventorySalesApi\Api\StockResolverInterface::class);
        $resolver->method('execute')->willReturn($stock);
        $criteria = $this->createMock(\Magento\Framework\Api\SearchCriteria::class);
        $builder = $this->createMock(SearchCriteriaBuilder::class);
        $builder->method('addFilter')->willReturnSelf();
        $builder->method('create')->willReturn($criteria);
        $links = [];
        foreach (['enabled', 'disabled'] as $code) {
            $link = $this->createMock(\Magento\InventoryApi\Api\Data\StockSourceLinkInterface::class);
            $link->method('getSourceCode')->willReturn($code);
            $links[] = $link;
        }
        $results = $this->createMock(\Magento\InventoryApi\Api\Data\StockSourceLinkSearchResultsInterface::class);
        $results->method('getItems')->willReturn($links);
        $stockLinks = $this->createMock(\Magento\InventoryApi\Api\GetStockSourceLinksInterface::class);
        $stockLinks->method('execute')->willReturn($results);
        $sources = $this->createMock(\Magento\InventoryApi\Api\SourceRepositoryInterface::class);
        $sources->method('get')->willReturnCallback(function ($code) {
            $source = $this->createMock(\Magento\InventoryApi\Api\Data\SourceInterface::class);
            $source->method('isEnabled')->willReturn($code === 'enabled');
            return $source;
        });
        $objects = $this->createMock(ObjectManagerInterface::class);
        $objects->method('create')->willReturnMap([
            [\Magento\InventorySalesApi\Api\StockResolverInterface::class, [], $resolver],
            [\Magento\InventoryApi\Api\GetStockSourceLinksInterface::class, [], $stockLinks],
            [\Magento\InventoryApi\Api\SourceRepositoryInterface::class, [], $sources],
        ]);
        $items = [new \Magento\Framework\DataObject(['source_code' => 'enabled']),
            new \Magento\Framework\DataObject(['source_code' => 'disabled'])];
        $api = $this->getMockBuilder(Api::class)->disableOriginalConstructor()
            ->onlyMethods(['isMsiEnabled', 'getAllItems'])->getMock();
        $api->method('isMsiEnabled')->willReturn(true);
        $api->method('getAllItems')->willReturn($items);
        foreach (['objectManager' => $objects, 'searchCriteriaBuilder' => $builder] as $key => $value) {
            (new \ReflectionProperty($api, $key))->setValue($api, $value);
        }
        self::assertSame([$items[0]], $api->getItems(new \Magento\Framework\DataObject(), 'website'));
    }

    public function testReservationsUseTheResolvedWebsiteStockWithoutFilteringEventMetadata(): void
    {
        $manager = $this->createMock(Manager::class);
        $manager->method('isEnabled')->willReturn(true);
        $stock = $this->createMock(\Magento\InventoryApi\Api\Data\StockInterface::class);
        $stock->method('getStockId')->willReturn(5);
        $resolver = $this->createMock(\Magento\InventorySalesApi\Api\StockResolverInterface::class);
        $resolver->expects(self::once())->method('execute')->with('website', 'website-b')->willReturn($stock);
        $objects = $this->createMock(ObjectManagerInterface::class);
        $objects->method('create')->with(\Magento\InventorySalesApi\Api\StockResolverInterface::class)
            ->willReturn($resolver);
        $clauses = [];
        $select = $this->getMockBuilder(Select::class)->disableOriginalConstructor()
            ->onlyMethods(['from', 'where'])->getMock();
        $select->method('from')->willReturnSelf();
        $select->method('where')->willReturnCallback(function ($condition, $value = null) use (&$clauses, $select) {
            $clauses[] = [$condition, $value];
            return $select;
        });
        $connection = $this->createMock(AdapterInterface::class);
        $connection->method('select')->willReturn($select);
        $connection->method('fetchOne')->with($select)->willReturn('-3.5');
        $resource = $this->createMock(ResourceConnection::class);
        $resource->method('getConnection')->willReturn($connection);
        $resource->method('getTableName')->willReturnArgument(0);
        $api = new Api($manager, $objects, $this->createMock(SearchCriteriaBuilder::class), $resource);
        self::assertSame(-3.5, $api->getReservations('SKU', null, 'website-b'));
        self::assertSame([['sku = ?', 'SKU'], ['stock_id = ?', 5]], $clauses);
    }

    public function testDisabledMsiDoesNotQueryReservationTables(): void
    {
        $manager = $this->createMock(Manager::class);
        $manager->method('isEnabled')->willReturn(false);
        $resource = $this->createMock(ResourceConnection::class);
        $resource->expects(self::never())->method('getConnection');
        $api = new Api($manager, $this->createMock(ObjectManagerInterface::class),
            $this->createMock(SearchCriteriaBuilder::class), $resource);
        self::assertEquals(0, $api->getReservations('SKU'));
    }
}
