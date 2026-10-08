<?php
declare(strict_types=1);

namespace MageOS\ShoppingFeed\Test\Unit\Model\Product\Adapter;

use MageOS\ShoppingFeed\Model\Feed;
use MageOS\ShoppingFeed\Model\Inventory\Api;
use MageOS\ShoppingFeed\Model\Product\Adapter\AdapterAbstract;
use Magento\Catalog\Model\Product;
use Magento\CatalogInventory\Api\StockStateInterface;
use Magento\Framework\DataObject;
use Magento\Store\Model\Store;
use Magento\Store\Model\Website;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
class InventoryCountTest extends TestCase
{
    /** @dataProvider inventoryCases */
    #[DataProvider('inventoryCases')]
    public function testReservationsBelongToTheWebsiteStockAndNotIndividualSources(
        array $items,
        ?string $sourceCode,
        bool $reservations,
        float $reservationQuantity,
        float $expected
    ): void {
        $product = $this->createMock(Product::class);
        $product->method('getSku')->willReturn('MSI-SKU');
        $website = $this->createMock(Website::class);
        $website->method('getCode')->willReturn('website-a');
        $store = $this->createMock(Store::class);
        $store->method('getWebsite')->willReturn($website);
        $feed = $this->createMock(Feed::class);
        $feed->method('getStore')->willReturn($store);
        $feed->method('getConfig')->with('general_use_stock_reservations')->willReturn($reservations);
        $sourceItems = array_map(static fn (array $row) => new DataObject($row), $items);
        $api = $this->createMock(Api::class);
        $api->method('getAllItems')->with($product)->willReturn($sourceItems);
        $api->method('getItems')->with($product, 'website-a')->willReturn($sourceItems);
        $api->expects($reservations && $sourceCode === null ? $this->once() : $this->never())
            ->method('getReservations')->with('MSI-SKU', null, 'website-a')->willReturn($reservationQuantity);
        $adapter = (new \ReflectionClass(AdapterAbstract::class))->newInstanceWithoutConstructor();
        foreach (['product' => $product, 'feed' => $feed, 'sourceInventoryApi' => $api] as $name => $value) {
            (new \ReflectionProperty($adapter, $name))->setValue($adapter, $value);
        }
        self::assertEquals($expected, $adapter->getInventoryCount($sourceCode));
    }

    public static function inventoryCases(): array
    {
        $items = [
            ['source_code' => 'a', 'quantity' => 10, 'status' => 1],
            ['source_code' => 'b', 'quantity' => 10, 'status' => 1],
        ];
        return [
            'two sources one reservation' => [$items, null, true, -1, 19],
            'physical source a' => [$items, 'a', true, -1, 10],
            'physical source b' => [$items, 'b', true, -1, 10],
            'missing source' => [$items, 'missing', true, -1, 0],
            'reservations disabled' => [$items, null, false, -1, 20],
            'negative stock is clamped' => [$items, null, true, -30, 0],
            'disabled source item' => [[
                ['source_code' => 'a', 'quantity' => 10, 'status' => 0],
                ['source_code' => 'b', 'quantity' => 10, 'status' => 1],
            ], null, true, -1, 9],
            'fractional quantities' => [[
                ['source_code' => 'a', 'quantity' => 1.5, 'status' => 1],
                ['source_code' => 'b', 'quantity' => 0.75, 'status' => 1],
            ], null, true, -0.25, 2],
        ];
    }
}
