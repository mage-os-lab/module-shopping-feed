<?php

declare(strict_types=1);

namespace MageOS\ShoppingFeed\Test\Unit\Model\Product\Mapper\LocalInventory;

use MageOS\ShoppingFeed\Model\Feed;
use MageOS\ShoppingFeed\Model\Inventory\Api;
use MageOS\ShoppingFeed\Model\Logger;
use MageOS\ShoppingFeed\Model\Product\Adapter\AdapterAbstract;
use MageOS\ShoppingFeed\Model\Product\Mapper\LocalInventory\Simple\Availability;
use Magento\Catalog\Model\Product;
use Magento\CatalogInventory\Model\Stock\Item;
use Magento\CatalogInventory\Model\Stock\Status;
use Magento\CatalogInventory\Model\StockRegistryProvider;
use Magento\Framework\DataObject;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
class AvailabilityTest extends TestCase
{
    /** @dataProvider sourceCases */
    #[DataProvider('sourceCases')]
    public function testLocalSourceAvailabilityDoesNotUseOnlineBackorders(int $qty, int $status, string $expected): void
    {
        $feed = $this->createMock(Feed::class);
        $feed->method('getConfig')->willReturn(false);
        $adapter = $this->createMock(AdapterAbstract::class);
        $adapter->method('getFeed')->willReturn($feed);
        $adapter->method('getProduct')->willReturn($this->createMock(Product::class));
        $adapter->method('getData')->with('inventory_source_item')->willReturn(new DataObject(['quantity' => $qty, 'status' => $status]));
        $stockItem = (new \ReflectionClass(Item::class))->newInstanceWithoutConstructor();
        $stockItem->setData(['item_id' => 1, 'is_in_stock' => 1, 'qty' => 0, 'backorders' => 1, 'manage_stock' => 1]);
        $registry = $this->createMock(StockRegistryProvider::class);
        $registry->method('getStockItem')->willReturn($stockItem);
        $mapper = new Availability($this->createMock(Logger::class), $this->createMock(Status::class), $this->createMock(Api::class), $registry);
        $mapper->addAdapter($adapter);
        $this->assertSame($expected, $mapper->getStockStatus($adapter));
    }

    public function testCompositeBackordersDoNotOverrideLocalQuantity(): void
    {
        foreach ([\MageOS\ShoppingFeed\Model\Product\Mapper\LocalInventory\Configurable\Availability::class,
            \MageOS\ShoppingFeed\Model\Product\Mapper\LocalInventory\Grouped\Availability::class] as $class) {
            foreach ([0 => 'out_of_stock', 10 => 'in_stock'] as $qty => $expected) {
                $child = $this->createMock(AdapterAbstract::class);
                $child->method('getInventoryCount')->willReturn($qty);
                $adapter = $this->createMock(AdapterAbstract::class);
                $adapter->method('getData')->willReturnCallback(fn($key) => $key === 'associated_product_adapters' ? [$child] : null);
                $adapter->method('getProduct')->willReturn($this->createMock(Product::class));
                $filter = $this->createMock(\MageOS\ShoppingFeed\Model\Product\Filter::class);
                $filter->method('cleanField')->willReturnArgument(0);
                $adapter->method('getFilter')->willReturn($filter);
                $item = (new \ReflectionClass(Item::class))->newInstanceWithoutConstructor();
                $item->setData(['item_id' => 1, 'is_in_stock' => 1, 'qty' => 0, 'backorders' => 1, 'manage_stock' => 1]);
                $registry = $this->createMock(StockRegistryProvider::class);
                $registry->method('getStockItem')->willReturn($item);
                $mapper = new $class($this->createMock(Logger::class), $this->createMock(Status::class), $this->createMock(Api::class), $registry);
                $mapper->addAdapter($adapter);
                $this->assertSame($expected, $mapper->map(['column' => 'availability']), $class);
            }
        }
    }

    public static function sourceCases(): array
    {
        return [[0, 1, 'out_of_stock'], [10, 1, 'in_stock'], [10, 0, 'out_of_stock']];
    }

    public function testLocalQuantityAndAvailabilityIgnoreStockLevelReservations(): void
    {
        $product = $this->createMock(Product::class);
        $product->method('getSku')->willReturn('SKU');
        $feed = $this->createMock(Feed::class);
        $feed->method('getConfig')->willReturn(true);
        $adapter = $this->createMock(AdapterAbstract::class);
        $adapter->method('getFeed')->willReturn($feed);
        $adapter->method('getProduct')->willReturn($product);
        $adapter->method('getData')->with('inventory_source_item')->willReturn(
            new DataObject(['quantity' => 1, 'status' => 1, 'source_code' => 'local'])
        );
        $filter = $this->createMock(\MageOS\ShoppingFeed\Model\Product\Filter::class);
        $filter->method('cleanField')->willReturnArgument(0);
        $adapter->method('getFilter')->willReturn($filter);
        $api = $this->createMock(Api::class);
        $api->expects(self::never())->method('getReservations');
        $quantity = new \MageOS\ShoppingFeed\Model\Product\Mapper\LocalInventory\Simple\Quantity(
            $this->createMock(Logger::class), $api
        );
        $quantity->addAdapter($adapter);
        self::assertSame('1', $quantity->map());
        $availability = new Availability($this->createMock(Logger::class), $this->createMock(Status::class),
            $api, $this->createMock(StockRegistryProvider::class));
        $availability->addAdapter($adapter);
        self::assertSame('in_stock', $availability->getStockStatus($adapter));
    }

    /** @dataProvider sourceCases */
    #[DataProvider('sourceCases')]
    public function testConfigurableParentHonorsChildStatusAtTheCurrentSource(
        int $qty,
        int $status,
        string $expected
    ): void {
        $product = $this->createMock(Product::class);
        $child = $this->createMock(AdapterAbstract::class);
        $child->method('getProduct')->willReturn($product);
        $child->method('getInventoryCount')->with('local')->willReturn($qty);
        $adapter = $this->createMock(AdapterAbstract::class);
        $adapter->method('getData')->willReturnCallback(static fn ($key) =>
            $key === 'associated_product_adapters' ? [$child] : new DataObject(['source_code' => 'local']));
        $filter = $this->createMock(\MageOS\ShoppingFeed\Model\Product\Filter::class);
        $filter->method('cleanField')->willReturnArgument(0);
        $adapter->method('getFilter')->willReturn($filter);
        $api = $this->createMock(Api::class);
        $api->method('getAllItems')->with($product)->willReturn([
            new DataObject(['source_code' => 'local', 'status' => $status]),
            new DataObject(['source_code' => 'another-source', 'status' => 1]),
        ]);
        $mapper = new \MageOS\ShoppingFeed\Model\Product\Mapper\LocalInventory\Configurable\Availability(
            $this->createMock(Logger::class), $this->createMock(Status::class), $api,
            $this->createMock(StockRegistryProvider::class)
        );
        $mapper->addAdapter($adapter);
        $this->assertSame($expected, $mapper->map(['column' => 'availability']));
    }
}
