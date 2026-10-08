<?php

namespace MageOS\ShoppingFeed\Test\Unit\Observer\Promotions;

use Magento\Framework\Filesystem\Driver\File;
use MageOS\ShoppingFeed\Model\Promotions\Provider\Map;
use MageOS\ShoppingFeed\Observer\Promotions\Generator;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
class GeneratorTest extends TestCase
{
    /**
     * @var Generator
     */
    private $observer;

    /**
     * @var File&\PHPUnit\Framework\MockObject\MockObject
     */
    private $fileDriver;

    protected function setUp(): void
    {
        $this->fileDriver = $this->createMock(File::class);
        $this->observer = new Generator(
            $this->createMock('Magento\Framework\Json\Helper\Data'),
            $this->createMock('MageOS\ShoppingFeed\Model\Promotions\Provider'),
            $this->createMock(Map::class),
            $this->createMock('MageOS\ShoppingFeed\Model\Promotions\Provider\Collection'),
            $this->createMock('Magento\SalesRule\Model\RuleFactory'),
            $this->fileDriver
        );
    }

    public function testHeaderContainsRepeatedCurrentDestinations(): void
    {
        $header = $this->invoke('createFeedHeader');

        $this->assertSame(
            [
                'promotion_id',
                'product_applicability',
                'long_title',
                'promotion_effective_dates',
                'promotion_display_dates',
                'redemption_channel',
                'promotion_destination',
                'promotion_destination',
                'offer_type',
                'generic_redemption_code',
                'minimum_purchase_amount',
            ],
            $header
        );
    }

    /** @dataProvider missingPromotionConfiguration */
    #[\PHPUnit\Framework\Attributes\DataProvider('missingPromotionConfiguration')]
    public function testIncompleteConfigurationWritesAnEmptyPromotionFeed($config): void
    {
        $feed = $this->createMock(\MageOS\ShoppingFeed\Model\Feed::class);
        $feed->method('getConfig')->willReturnCallback(
            static fn ($key) => $key === 'promotions_enabled' ? true : $config
        );
        $feed->expects($this->once())->method('saveMessages')->with([
            'promotion_file' => '/test/promotions.txt', 'promotion_added' => 0,
        ]);
        $generator = $this->createMock(\MageOS\ShoppingFeed\Model\Generator::class);
        $generator->method('getLogger')->willReturn($this->createMock(\MageOS\ShoppingFeed\Model\Logger::class));
        $generator->method('isTestMode')->willReturn(false);
        $provider = $this->createMock(\MageOS\ShoppingFeed\Model\Promotions\Provider::class);
        $provider->method('getPromotionFile')->willReturn('/test/promotions.txt');
        $json = $this->createMock(\Magento\Framework\Json\Helper\Data::class);
        $json->method('jsonEncode')->willReturnCallback(static fn ($data) => json_encode($data));
        $this->observer = new Generator($json, $provider, $this->createMock(Map::class),
            $this->createMock(\MageOS\ShoppingFeed\Model\Promotions\Provider\Collection::class),
            $this->createMock(\Magento\SalesRule\Model\RuleFactory::class), $this->fileDriver);
        $header = implode("\t", $this->invoke('createFeedHeader')) . "\n";
        $this->fileDriver->expects($this->once())->method('filePutContents')->with('/test/promotions.txt.tmp', $header);
        $this->fileDriver->expects($this->once())->method('rename')->with('/test/promotions.txt.tmp', '/test/promotions.txt');
        $event = new \Magento\Framework\Event(['generator' => $generator, 'feed' => $feed]);
        self::assertSame($this->observer, $this->observer->execute(new \Magento\Framework\Event\Observer(['event' => $event])));
    }

    public static function missingPromotionConfiguration(): array
    {
        return [[null], [[]], [''], [['promotion' => 'invalid']], [['promotion' => [1 => null]]]];
    }

    public function testLineTargetsOnlineShoppingAdsAndFreeListings(): void
    {
        $rule = $this->createMock('Magento\SalesRule\Model\Rule');
        $map = $this->createMock(Map::class);
        $map->method('mapPromotionId')->willReturn('PROMO_1_9');
        $map->method('mapProductApplicability')->willReturn('all_products');
        $map->method('mapEffectiveDates')->willReturn('effective');
        $map->method('mapDisplayDates')->willReturn('display');
        $map->method('mapOfferType')->willReturn('no_code');
        $map->method('mapGenericRedemptionCode')->willReturn('');
        $map->method('mapMinimumPurchaseAmount')->willReturn('');

        $line = $this->invoke('createFeedLine', [1, $rule, $map, ['title' => 'Summer offer']]);

        $this->assertSame('online', $line[5]);
        $this->assertSame('shopping_ads', $line[6]);
        $this->assertSame('free_listings', $line[7]);
        $this->assertSame('no_code', $line[8]);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('promotionTitles')]
    public function testPromotionTitleCannotBreakTsvRows(array $row, string $expected): void
    {
        $rule = $this->getMockBuilder(\Magento\SalesRule\Model\Rule::class)
            ->disableOriginalConstructor()->onlyMethods([])->getMock();
        $rule->setData('name', "Default\t offer\nname");
        $line = $this->invoke('createFeedLine', [1, $rule, $this->createMock(Map::class), $row]);
        self::assertSame($expected, $line[2]);
        self::assertCount(11, explode("\t", implode("\t", $line)));
        self::assertStringNotContainsString("\n", implode("\t", $line));
        self::assertStringNotContainsString("\r", implode("\t", $line));
    }

    public static function promotionTitles(): array
    {
        return [
            [['title' => "Summer\t offer\r\nnow"], 'Summer offer now'],
            [[], 'Default offer name'],
            [['title' => null], 'Default offer name'],
            [['title' => 'Café 東京'], 'Café 東京'],
        ];
    }

    public function testPromotionFileIsReplacedAtomically(): void
    {
        $file = '/magento/pub/media/mageos-shopping-feed/promotions.txt';
        $temporary = $file . '.tmp';
        $this->fileDriver->method('isExists')->with($temporary)->willReturn(false);
        $this->fileDriver->expects($this->once())->method('filePutContents')->with($temporary, "header\nrow\n");
        $this->fileDriver->expects($this->once())->method('rename')->with($temporary, $file);

        $this->invoke('writePromotionFile', [$file, "header\nrow\n"]);
    }

    /**
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    private function invoke($method, array $arguments = [])
    {
        $reflection = new \ReflectionMethod($this->observer, $method);
        return $reflection->invokeArgs($this->observer, $arguments);
    }
}
