<?php

declare(strict_types=1);

namespace MageOS\ShoppingFeed\Test\Unit\Model\Promotions;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Framework\ObjectManagerInterface;
use Magento\Quote\Model\Quote\ItemFactory;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\ShoppingFeed\Model\Feed\OutputPath;
use MageOS\ShoppingFeed\Model\Promotions\Provider;
use MageOS\ShoppingFeed\Model\Promotions\Provider\Collection;
use MageOS\ShoppingFeed\Model\Promotions\Provider\Map;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
class ProviderTest extends TestCase
{
    public function testExcludedPromotionRulesAreNeverLookedUpOrReferenced(): void
    {
        $feed = $this->createMock(\MageOS\ShoppingFeed\Model\Feed::class);
        $feed->method('getConfig')->willReturnCallback(static fn ($key) => $key === 'promotions_enabled'
            ? true : ['hash' => 'test', 'counter' => 1,
                'promotion' => [12 => ['include' => 0], 34 => ['include' => false]]]);
        $collection = $this->createMock(Collection::class);
        $collection->expects(self::never())->method('getPromotionRules');
        $provider = $this->getMockBuilder(Provider::class)->disableOriginalConstructor()
            ->onlyMethods(['getPromotionCache'])->getMock();
        $provider->method('getPromotionCache')->willReturn(false);
        (new \ReflectionProperty($provider, 'promotionsCollection'))->setValue($provider, $collection);
        (new \ReflectionProperty($provider, 'map'))->setValue($provider, $this->createMock(Map::class));
        $provider->setFeed($feed);
        self::assertSame([], $provider->getPromotionIds($this->createMock(\Magento\Catalog\Model\Product::class)));
    }

    public function testAssigningTheSameFeedDoesNotDiscardTheWarmCache(): void
    {
        $directories = $this->createMock(DirectoryList::class);
        $directories->method('getPath')->willReturn('/magento/var');
        $helper = $this->createMock(JsonHelper::class);
        $helper->method('jsonDecode')->willReturnCallback(static fn ($data) => json_decode($data, true));
        $driver = $this->createMock(File::class);
        $driver->method('isExists')->willReturn(true);
        $driver->method('isReadable')->willReturn(true);
        $driver->expects(self::once())->method('fileGetContents')
            ->willReturn('{"hash":"hash","cache":{"42":["PROMO"]}}');
        $provider = $this->createProvider($helper, $directories, $driver);
        $feed = $this->createMock(\MageOS\ShoppingFeed\Model\Feed::class);
        $method = new \ReflectionMethod($provider, 'getPromotionCache');
        $provider->setFeed($feed);
        self::assertSame(['PROMO'], $method->invoke($provider, 42, 'hash'));
        $provider->setFeed($feed);
        self::assertSame(['PROMO'], $method->invoke($provider, 42, 'hash'));
    }

    public function testGenerationBatchesCacheWritesAndKeepsEveryProduct(): void
    {
        $directories = $this->createMock(DirectoryList::class);
        $directories->method('getPath')->willReturn('/magento/var');
        $helper = $this->createMock(JsonHelper::class);
        $helper->method('jsonEncode')->willReturnCallback(static fn ($data) => json_encode($data));
        $driver = $this->createMock(File::class);
        $driver->method('isExists')->willReturn(false);
        $driver->expects(self::once())->method('filePutContents')->willReturnCallback(
            function ($path, $contents) {
                $cache = json_decode($contents, true);
                self::assertCount(100, $cache['cache']);
                self::assertSame(['PROMO-1'], $cache['cache'][1]);
                self::assertSame(['PROMO-100'], $cache['cache'][100]);
                return strlen($contents);
            }
        );
        $driver->expects(self::once())->method('rename');
        $provider = $this->createProvider($helper, $directories, $driver);
        $feed = $this->createMock(\MageOS\ShoppingFeed\Model\Feed::class);
        $provider->setFeed($feed);
        $method = new \ReflectionMethod($provider, 'setPromotionCache');
        $provider->beginCacheBatch();
        for ($id = 1; $id <= 100; $id++) {
            $provider->setFeed($feed);
            $method->invoke($provider, $id, 'hash', ['PROMO-' . $id]);
        }
        $provider->endCacheBatch();
        $provider->flushPromotionCache();
    }

    /** @dataProvider malformedWidgetConfiguration */
    #[DataProvider('malformedWidgetConfiguration')]
    public function testMalformedWidgetConfigurationDoesNotBreakRuleValidation($config): void
    {
        $feed = $this->createMock(\MageOS\ShoppingFeed\Model\Feed::class);
        $feed->method('getConfig')->willReturn($config);
        $rules = $this->createMock(\Magento\SalesRule\Model\ResourceModel\Rule\Quote\Collection::class);
        $rules->method('getAllIds')->willReturn([]);
        $collection = $this->createMock(Collection::class);
        $collection->method('getPromotionRules')->willReturn($rules);
        $collection->expects($this->never())->method('updatePromotionsData');
        $provider = (new \ReflectionClass(Provider::class))->newInstanceWithoutConstructor();
        (new \ReflectionProperty(Provider::class, 'promotionsCollection'))->setValue($provider, $collection);
        $provider->setFeed($feed);
        self::assertSame($provider, $provider->validateGooglePromotions());
    }

    public static function malformedWidgetConfiguration(): array
    {
        return [[null], [''], [['promotion' => 'invalid']], [['promotion' => 1]]];
    }

    public function testPromotionCacheIsReloadedWhenConfigurationHashChanges(): void
    {
        $directoryList = $this->createMock(DirectoryList::class);
        $directoryList->method('getPath')->with('var')->willReturn('/magento/var');

        $helper = $this->createMock(JsonHelper::class);
        $helper->method('jsonDecode')->willReturnCallback(
            static fn (string $value): array => json_decode($value, true)
        );

        $fileDriver = $this->createMock(File::class);
        $fileDriver->method('isExists')->willReturn(true);
        $fileDriver->method('isReadable')->willReturn(true);
        $fileDriver->expects($this->exactly(2))->method('fileGetContents')->willReturnOnConsecutiveCalls(
            '{"hash":"first","cache":{"42":["FIRST"]}}',
            '{"hash":"second","cache":{"42":["SECOND"]}}'
        );

        $provider = $this->createProvider($helper, $directoryList, $fileDriver);
        $method = new \ReflectionMethod($provider, 'getHashCache');

        $this->assertSame(['42' => ['FIRST']], $method->invoke($provider, 'first'));
        $this->assertSame(['42' => ['SECOND']], $method->invoke($provider, 'second'));
    }

    public function testPromotionCacheIsReplacedAtomically(): void
    {
        $cacheFile = '/magento/var/cache/mageos_shopping_feed_promotions.cache';
        $temporaryFile = $cacheFile . '.' . getmypid() . '.tmp';

        $directoryList = $this->createMock(DirectoryList::class);
        $directoryList->method('getPath')->with('var')->willReturn('/magento/var');

        $helper = $this->createMock(JsonHelper::class);
        $helper->expects($this->once())->method('jsonEncode')->willReturn('{"cache":[],"hash":"hash"}');

        $fileDriver = $this->createMock(File::class);
        $fileDriver->method('isExists')->willReturn(false);
        $fileDriver->expects($this->once())->method('createDirectory')->with('/magento/var/cache');
        $fileDriver->expects($this->once())
            ->method('filePutContents')
            ->with($temporaryFile, '{"cache":[],"hash":"hash"}');
        $fileDriver->expects($this->once())->method('rename')->with($temporaryFile, $cacheFile);

        $provider = $this->createProvider($helper, $directoryList, $fileDriver);

        $method = new \ReflectionMethod($provider, 'setPromotionCache');
        $method->invoke($provider, 42, 'hash', ['PROMO']);
    }

    public function testMultipleIncludedRulesAreFilteredAsSeparateIds(): void
    {
        $feed = $this->createMock(\MageOS\ShoppingFeed\Model\Feed::class);
        $config = [
            'promotions_enabled' => true,
            'promotions_provider_widget' => [
                'hash' => 'test', 'counter' => 1,
                'promotion' => [12 => ['include' => 1], 34 => ['include' => 1]],
            ],
        ];
        $feed->method('getConfig')->willReturnCallback(static fn($key) => $config[$key] ?? null);
        $rules = $this->createMock(\Magento\SalesRule\Model\ResourceModel\Rule\Quote\Collection::class);
        $rules->expects($this->once())->method('addFieldToFilter')->with('rule_id', ['in' => [12, 34]])->willReturnSelf();
        $rules->method('getIterator')->willReturn(new \ArrayIterator([]));
        $collection = $this->createMock(Collection::class);
        $collection->method('getPromotionRules')->with($feed)->willReturn($rules);
        $provider = $this->getMockBuilder(Provider::class)->disableOriginalConstructor()
            ->onlyMethods(['getPromotionCache', 'setPromotionCache'])->getMock();
        $provider->method('getPromotionCache')->willReturn(false);
        foreach (['promotionsCollection' => $collection, 'map' => $this->createMock(Map::class)] as $name => $value) {
            (new \ReflectionProperty(Provider::class, $name))->setValue($provider, $value);
        }
        $provider->setFeed($feed);
        $this->assertSame([], $provider->getPromotionIds($this->createMock(\Magento\Catalog\Model\Product::class)));
    }

    private function createProvider(JsonHelper $helper, DirectoryList $directoryList, File $fileDriver): Provider
    {
        return new Provider(
            $helper,
            $this->createMock(StoreManagerInterface::class),
            $this->createMock(ItemFactory::class),
            $directoryList,
            $this->createMock(ObjectManagerInterface::class),
            $this->createMock(Map::class),
            $this->createMock(Collection::class),
            $this->createMock(OutputPath::class),
            $fileDriver
        );
    }
}
