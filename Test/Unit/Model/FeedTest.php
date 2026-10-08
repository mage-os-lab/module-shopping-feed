<?php
/**
 * RocketWeb
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category  RocketWeb
 * @package   MageOS_ShoppingFeed
 * @copyright Copyright (c) 2016 RocketWeb (http://rocketweb.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author    Rocket Web Inc.
 */

namespace MageOS\ShoppingFeed\Test\Unit\Model;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Class FeedTest
 */
#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
class FeedTest extends ModelFramework
{
    /**
     * @var \MageOS\ShoppingFeed\Model\Feed
     */
    protected $model;

    /**
     * @var int
     */
    protected $feedId = 8;

    /**
     * @var \MageOS\ShoppingFeed\Model\Serializer
     */
    protected $serializer;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->serializer = new \Magento\Framework\Serialize\Serializer\Json();
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        parent::setUp();

        $this->scheduleCollectionMock = $this->objectManagerHelper->getCollectionMock(
            'MageOS\ShoppingFeed\Model\ResourceModel\Feed\Schedule\Collection',
            [$this->scheduleMock]
        );

        $this->expectSelf($this->scheduleCollectionMock, 'setFeedFilter');
        $this->expectReturn($this->scheduleCollectionFactoryMock, 'create', $this->scheduleCollectionMock);


        $this->configCollectionMock = $this->objectManagerHelper->getCollectionMock(
            'MageOS\ShoppingFeed\Model\ResourceModel\Feed\Config\Collection',
            [$this->configMock]
        );

        $this->expectSelf($this->configCollectionMock, 'setFeedFilter');
        $this->expectReturn($this->configCollectionFactoryMock, 'create', $this->configCollectionMock);

        $this->feed = $this->objectManagerHelper->getObject(
            'MageOS\ShoppingFeed\Model\Feed',
            [
                'context' => $this->contextMock,
                'registry' => $this->registryMock,
                'scheduleCollectionFactory' => $this->scheduleCollectionFactoryMock,
                'scheduleFactory' => $this->scheduleFactoryMock,
                'configCollectionFactory' => $this->configCollectionFactoryMock,
                'configFactory' => $this->configFactoryMock,
                'feedTypesConfig' => $this->feedTypesConfigMock,
                'localeDate' => $this->localeDateMock,
                'storeObject' => $this->storeMock,
                'priceCurrency' => $this->priceCurrencyMock,
                'serializer' => $this->serializer,
                'resource' => $this->resource,
                'resourceCollection' => $this->resourceCollection,
                'data' => [
                    'id' => $this->feedId,
                    'name' => 'Foo',
                    'store_id' => 2,
                    'type' => 'generic'
                ]
            ]
        );
    }

    public function testUiFormSavePreservesStructuredAndLiteralColumnParameters(): void
    {
        $columns = [['column' => 'custom', 'attribute' => 'vendor_custom', 'order' => 10,
            'param' => ['zero' => 0, 'false' => false, 'literal' => "line 1\nline 2\t\\d+ " . '${literal}']]];
        $this->feed->setData('config', new \Magento\Framework\DataObject(['columns_product_columns' => $columns]));
        $this->feed->beforeSave();
        self::assertSame($columns, $this->feed->getConfig()->getData('columns_product_columns'));
    }

    public function testConstructorFeedTypeInitializesItsConfig(): void
    {
        $this->feedTypesConfigMock->method('getFeed')->with('generic')->willReturn([
            'default_feed_config' => ['general' => ['currency' => 'USD']]
        ]);
        $feed = $this->objectManagerHelper->getObject(\MageOS\ShoppingFeed\Model\Feed::class, [
            'context' => $this->contextMock,
            'registry' => $this->registryMock,
            'resource' => $this->resource,
            'resourceCollection' => $this->resourceCollection,
            'feedTypesConfig' => $this->feedTypesConfigMock,
            'feedType' => 'generic'
        ]);

        self::assertSame('generic', $feed->getType());
        self::assertSame('USD', $feed->getConfig('general_currency'));
    }

    /** @dataProvider stateSaveCases */
    #[DataProvider('stateSaveCases')]
    public function testStateSaveRestoresConfigurationFlag(string $method, $value, bool $fails): void
    {
        $resource = $this->getMockBuilder(\MageOS\ShoppingFeed\Model\ResourceModel\Feed::class)
            ->disableOriginalConstructor()->getMock();
        $suppression = [];
        $resource->method('unsUpdatedAt')->willReturnCallback(
            static function ($suppress = true) use ($resource, &$suppression) {
                $suppression[] = $suppress;
                return $resource;
            }
        );
        $resource->expects(self::once())->method('save')->willReturnCallback(
            static function ($feed) use ($resource, $fails) {
                self::assertTrue($feed->getData('no_after_save'));
                if ($fails) {
                    throw new \RuntimeException('Synthetic database failure');
                }
                return $resource;
            }
        );
        $feed = $this->objectManagerHelper->getObject(\MageOS\ShoppingFeed\Model\Feed::class, [
            'context' => $this->contextMock, 'registry' => $this->registryMock, 'resource' => $resource,
            'resourceCollection' => $this->resourceCollection, 'serializer' => $this->serializer
        ]);
        try {
            $feed->$method($value);
            self::assertFalse($fails);
        } catch (\RuntimeException $exception) {
            self::assertTrue($fails);
            self::assertSame('Synthetic database failure', $exception->getMessage());
        }
        self::assertFalse((bool)$feed->getData('no_after_save'));
        self::assertSame($method === 'saveStatus' ? [true, false] : [], $suppression);
    }

    public static function stateSaveCases(): array
    {
        return [
            ['saveStatus', 0, false], ['saveStatus', 0, true],
            ['saveMessages', ['exported' => 2], false], ['saveMessages', ['exported' => 2], true],
        ];
    }

    public function testResourceTimestampSuppressionCanBeReset(): void
    {
        $resource = (new \ReflectionClass(\MageOS\ShoppingFeed\Model\ResourceModel\Feed::class))
            ->newInstanceWithoutConstructor();
        $date = $this->createMock(\Magento\Framework\Stdlib\DateTime\DateTime::class);
        $date->expects(self::once())->method('gmtDate')->willReturn('2026-10-08 12:00:00');
        (new \ReflectionProperty($resource, 'date'))->setValue($resource, $date);
        $beforeSave = new \ReflectionMethod($resource, '_beforeSave');
        $this->feed->setIdFieldName('id');
        $this->feed->setUpdatedAt('2000-01-01 00:00:00');
        $resource->unsUpdatedAt();
        $beforeSave->invoke($resource, $this->feed);
        self::assertSame('2000-01-01 00:00:00', $this->feed->getUpdatedAt());

        $resource->unsUpdatedAt(false);
        $beforeSave->invoke($resource, $this->feed);
        self::assertSame('2026-10-08 12:00:00', $this->feed->getUpdatedAt());
    }

    /** @dataProvider invalidHeaderNames */
    #[DataProvider('invalidHeaderNames')]
    public function testRejectsHeaderControlCharactersAtTheModelSaveBoundary(string $name): void
    {
        $this->feed->setData('config', new \Magento\Framework\DataObject([
            'columns_product_columns' => [['column' => $name, 'attribute' => 'sku']]
        ]));
        $this->expectException(\Magento\Framework\Exception\LocalizedException::class);
        $this->expectExceptionMessage('Column names must not contain control characters.');
        (new \ReflectionMethod($this->feed, 'validate'))->invoke($this->feed);
    }

    public static function invalidHeaderNames(): array
    {
        return [["title\textra"], ["title\nextra"], ["title\rextra"], ["title\0extra"], ["title\x7fextra"]];
    }

    /**
     * Test columns map
     */
    public function testColumnsMap()
    {
        $returnValue = [
            'default_feed_config' => [
                'columns' => [
                    'product_columns' => [
                        [
                            'order' => 20,
                            'column' => 'test2'
                        ],
                        [
                            'order' => 10,
                            'column' => 'test1'
                        ]
                    ]
                ]
            ],
            'output_params' => '',
            'file' => ''
        ];

        $this->expectReturn($this->feedTypesConfigMock, 'getFeed', $returnValue);

        $this->feed->afterLoad();

        $expectedColumnsMap = [
            [
                'order' => 10,
                'column' => 'test1',
            ],
            [
                'order' => 20,
                'column' => 'test2',
            ]
        ];

        $this->assertEquals($expectedColumnsMap, $this->feed->getColumnsMap());
    }

    public function testSetColumnsMap()
    {
        $excpected = ['fake array'];
        $this->feed->setColumnsMap($excpected);

        $this->assertEquals($excpected, $this->feed->getColumnsMap());
    }

    public function testGoogleColumnsMapUpgradesExistingConfiguration(): void
    {
        $variantColumns = [
            'item_group_title' => ['column' => 'item_group_title', 'attribute' => 'directive_item_group_title', 'order' => 21],
            'variant_option' => ['column' => 'variant_option', 'attribute' => 'directive_variant_option', 'order' => 22],
            'color' => ['column' => 'color', 'attribute' => 'directive_variant_attributes', 'order' => 23, 'param' => ['color']],
            'size' => ['column' => 'size', 'attribute' => 'directive_variant_attributes', 'order' => 24, 'param' => ['size']],
            'material' => ['column' => 'material', 'attribute' => 'directive_variant_attributes', 'order' => 25, 'param' => ['material']],
            'pattern' => ['column' => 'pattern', 'attribute' => 'directive_variant_attributes', 'order' => 26, 'param' => ['pattern']],
            'gender' => ['column' => 'gender', 'attribute' => 'directive_variant_attributes', 'order' => 27, 'param' => ['gender']],
            'age_group' => ['column' => 'age_group', 'attribute' => 'directive_variant_attributes', 'order' => 28, 'param' => ['age_group']],
        ];
        $defaultColumns = array_merge(
            [
                'id' => ['column' => 'id', 'attribute' => 'directive_id', 'order' => 10],
                'item_group_id' => ['column' => 'item_group_id', 'attribute' => 'directive_item_group_id', 'order' => 20],
            ],
            $variantColumns
        );
        $this->feedTypesConfigMock->expects($this->once())
            ->method('getFeed')
            ->with('google_shopping')
            ->willReturn(['default_feed_config' => ['columns' => ['product_columns' => $defaultColumns]]]);

        $this->feed->setData('type', 'google_shopping');
        $this->feed->setData(
            'config',
            new \Magento\Framework\DataObject(
                [
                    'columns_product_columns' => [
                        ['column' => 'id', 'attribute' => 'directive_id', 'order' => 10],
                        ['column' => 'item_group_id', 'attribute' => 'directive_item_group_id', 'order' => 20],
                        ['column' => 'promotions_id', 'attribute' => 'directive_promotions_id', 'order' => 300],
                    ],
                ]
            )
        );

        $columns = array_column($this->feed->getColumnsMap(), null, 'column');

        $this->assertArrayNotHasKey('promotions_id', $columns);
        $this->assertArrayHasKey('promotion_id', $columns);
        foreach (array_keys($variantColumns) as $column) {
            $this->assertArrayHasKey($column, $columns);
        }
    }

    /**
     * Test afterLoad method in connection to messages
     */
    public function testAfterLoadConfig()
    {
        $returnValue = [
            'default_feed_config' => ['section' => ['path' => 'value']],
            'output_params' => '',
            'file' => ''
        ];

        $expectedConfig = new \Magento\Framework\DataObject();
        $expectedConfig->addData(
            [
            'section_path' => 'value'
            ]
        );

        $this->expectReturn($this->feedTypesConfigMock, 'getFeed', $returnValue);

        $this->feed->afterLoad();
        $this->assertEquals($expectedConfig, $this->feed->getData('config'));
    }

    /**
     * Test getConfigCollection method
     */
    public function testGetConfigCollection()
    {
        $this->assertInstanceOf(
            '\MageOS\ShoppingFeed\Model\ResourceModel\Feed\Config\Collection',
            $this->feed->getConfigCollection()
        );
    }

    /**
     * Test getMessages method
     *
     * @dataProvider getMessagesDataProvider
     */
    #[DataProvider('getMessagesDataProvider')]
    public function testGetMessages($messages, $expected)
    {
        $this->feed->setData('messages', $messages);

        $this->assertEquals($expected, $this->feed->getMessages());
    }

    /**
     * @return array
     */
    public static function getMessagesDataProvider()
    {
        return [
            [
                'messages' => json_encode(['foo' => 'bar']),
                'expected' => ['foo' => 'bar'],
            ],
            [
                'messages' => ['foo' => 'bar'],
                'expected' => ['foo' => 'bar'],
            ],
        ];
    }

    public function testBeforeSaveInitializesRequiredMessagesPayload(): void
    {
        $this->feed->setData(
            'config',
            new \Magento\Framework\DataObject(['columns_product_columns' => []])
        );
        $this->feed->unsetData('messages');

        $this->feed->beforeSave();

        $this->assertSame('[]', $this->feed->getData('messages'));
    }

    public function testBeforeSavePreservesNullAndLiteralColumnValuesWithoutDeprecations(): void
    {
        $this->feed->setData('config', new \Magento\Framework\DataObject([
            'columns_product_columns' => [[
                'column' => "custom\tcolumn",
                'default_value' => null,
                'param' => "first\nsecond\rthird",
                'order' => 10,
            ]],
        ]));
        $deprecations = [];
        set_error_handler(static function (int $severity, string $message) use (&$deprecations): bool {
            if ($severity === E_DEPRECATED) {
                $deprecations[] = $message;
                return true;
            }
            return false;
        });
        try {
            $this->feed->beforeSave();
        } finally {
            restore_error_handler();
        }

        $this->assertSame([], $deprecations);
        $this->assertSame([[
            'column' => "custom\tcolumn",
            'default_value' => null,
            'param' => "first\nsecond\rthird",
            'order' => 10,
        ]], $this->feed->getConfig('columns_product_columns'));
    }

    /**
     * Test getScheduleCollection method
     */
    public function testGetScheduleCollection()
    {
        $this->assertInstanceOf(
            '\MageOS\ShoppingFeed\Model\ResourceModel\Feed\Schedule\Collection',
            $this->feed->getScheduleCollection()
        );
    }

    public function testGetSchedulesFromCache()
    {
        $schedule = ['fake array'];
        $this->feed->setData('schedules', $schedule);

        $this->assertEquals($schedule, $this->feed->getSchedules());
    }

    public function testGetSchedules()
    {
        $this->expectReturn($this->scheduleMock, 'getData', ['fake array']);

        $expected = [['fake array']];
        $this->assertEquals($expected, $this->feed->getSchedules());
    }

    /**
     * Test formatted schedules empty value
     */
    public function testEmptyGetFormattedSchedules()
    {
        $this->expectReturn($this->scheduleCollectionMock, 'getSize', 0);
        $expectedSchedules = ['None'];

        $this->assertEquals($expectedSchedules, $this->feed->getFormattedSchedules());
    }

    /**
     * Test formatted schedules
     */
    public function testGetFormattedSchedules()
    {
        $this->expectReturn($this->scheduleCollectionMock, 'getSize', 1);
        $this->expectReturn($this->scheduleMock, 'getFormattedSchedule', 'Some Schedule');

        $expectedSchedules = ['Some Schedule'];

        $this->assertEquals($expectedSchedules, $this->feed->getFormattedSchedules());
    }

    public function testSetType()
    {
        $type = 'simple';
        $returnValue = [
            'default_feed_config' => ['section' => ['path' => 'value']],
            'output_params' => '',
            'file' => ''
        ];

        $expectedConfig = new \Magento\Framework\DataObject();
        $expectedConfig->addData(
            [
            'section_path' => 'value'
            ]
        );

        $this->expectReturn($this->feedTypesConfigMock, 'getFeed', $returnValue);
        $this->feed->setType($type);

        $this->assertEquals($expectedConfig, $this->feed->getConfig());
    }

    public function testIsProductTypeEnabled()
    {
        $type = 'simple';
        $returnValue = [
            'default_feed_config' => ['filters' => ['product_types' => ['simple']]],
            'output_params' => '',
            'file' => ''
        ];

        $this->expectReturn($this->feedTypesConfigMock, 'getFeed', $returnValue);
        $this->feed->setType($type);

        $this->assertEquals(true, $this->feed->isProductTypeEnabled($type));
    }

    public function testIsTaxonomyAutocompleteEnabled()
    {
        $type = 'simple';
        $returnValue = [
            'default_feed_config' => ['categories' => ['taxonomy_autocomplete_enabled' => 1]],
            'output_params' => '',
            'file' => ''
        ];

        $this->expectReturn($this->feedTypesConfigMock, 'getFeed', $returnValue);
        $this->feed->setType($type);

        $this->assertEquals(true, $this->feed->isTaxonomyAutocompleteEnabled());
    }

    public function testGetConfig()
    {
        $type = 'simple';
        $returnValue = [
            'default_feed_config' => ['categories' => ['taxonomy_autocomplete_enabled' => 1]],
            'output_params' => '',
            'file' => ''
        ];

        $this->expectReturn($this->feedTypesConfigMock, 'getFeed', $returnValue);
        $this->feed->setType($type);

        $this->assertEquals('fail', $this->feed->getConfig('fake path', 'fail'));
    }

    public function testGetStore()
    {
        $this->expectReturn($this->storeMock, 'getStoreId', 0);
        $this->expectReturn($this->priceCurrencyMock, 'getCurrency', 'return value');

        $this->assertEquals('return value', $this->feed->getStore()->getData('current_currency'));
    }

    public function testAfterSave()
    {
        $this->feed->setData('schedules', []);
        $this->assertInstanceOf('MageOS\ShoppingFeed\Model\Feed', $this->feed->afterSave());
    }

    public function testAfterSaveWithConfig()
    {
        $config = new \Magento\Framework\DataObject();
        $config->addData(
            [
            'section_path' => 'value',
            'shipping_cache_enabled' => true
            ]
        );
        $this->feed->setData('config', $config);

        $this->configMock->expects($this->any())
            ->method('getData')
            ->will($this->onConsecutiveCalls('section_path', 'new value'));

        $this->feed->setData('schedules', []);
        $this->assertInstanceOf('MageOS\ShoppingFeed\Model\Feed', $this->feed->afterSave());
    }

    public function testSaveSchedules()
    {
        $this->feed->setId($this->feedId);
        $this->expectSelf($this->scheduleMock, 'load');
        $schedules = [
            ['id' => 1, 'start_at' => 1, 'batch_mode' => true, 'batch_limit' => 5000],
            ['id' => '', 'start_at' => 12, 'batch_mode' => false, 'batch_limit' => '']
        ];
        $this->scheduleMock->expects($this->any())
            ->method('getId')
            ->willReturn(1);
        $this->scheduleMock->method('getData')->willReturnCallback(
            fn($key) => $key === 'feed_id' ? $this->feedId : 1
        );
        $this->feed->setData('schedules', $schedules);
        $this->feed->saveSchedules();

        $this->assertInstanceOf('MageOS\ShoppingFeed\Model\Feed', $this->feed);
    }

    public function testChangedScheduleCanRunOnTheSameDay(): void
    {
        $this->feed->setId($this->feedId);
        $this->scheduleMock->expects($this->any())
            ->method('getId')
            ->willReturn(1);
        $this->scheduleMock->expects($this->any())
            ->method('getData')
            ->willReturnCallback(fn($key) => $key === 'feed_id' ? $this->feedId : 1);
        $this->localeDateMock->expects($this->once())
            ->method('date')
            ->with(null);
        $this->scheduleMock->expects($this->once())->method('save');

        $this->feed->setData('schedules', [['id' => 1, 'start_at' => 12]]);
        $this->feed->saveSchedules();
    }

    public function testUnchangedScheduleKeepsItsProcessedDate(): void
    {
        $this->feed->setId($this->feedId);
        $this->scheduleMock->setData('processed_at', '2026-08-18 09:00:00');
        $this->scheduleMock->expects($this->any())
            ->method('getId')
            ->willReturn(1);
        $this->scheduleMock->expects($this->any())
            ->method('getData')
            ->willReturnCallback(fn($key) => $key === 'feed_id' ? $this->feedId : 12);
        $this->localeDateMock->expects($this->never())->method('date');

        $this->feed->setData('schedules', [['id' => 1, 'start_at' => 12]]);
        $this->feed->saveSchedules();
    }

    public function testDeleteSchedules()
    {
        $this->expectSelf($this->scheduleMock, 'load');

        $schedules = [
            ['id' => 1, 'delete' => true],
            ['id' => '', 'start_at' => 12]
        ];

        $this->scheduleMock->expects($this->any())
            ->method('getId')
            ->will($this->onConsecutiveCalls(null, null, 1, 1));

        $this->feed->setData($schedules);
        $this->feed->saveSchedules();

        $this->assertInstanceOf('MageOS\ShoppingFeed\Model\Feed', $this->feed);
    }
}
