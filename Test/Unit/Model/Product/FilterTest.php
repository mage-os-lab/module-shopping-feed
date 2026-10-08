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


namespace MageOS\ShoppingFeed\Test\Unit\Model\Product;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use MageOS\ShoppingFeed\Test\Unit\CompatibilityTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
class FilterTest extends CompatibilityTestCase
{
    /**
     * @var \MageOS\ShoppingFeed\Model\Product\Filter
     */
    protected $model;

    /**
     * @var \MageOS\ShoppingFeed\Model\Generator\Cache|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $cacheMock;

    /**
     * @var \MageOS\ShoppingFeed\Model\Feed|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $feedMock;

    /**
     * @var \Magento\Catalog\Model\Product|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $productMock;

    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);

        $this->cacheMock = $this->createCompatibleMock(
            'MageOS\ShoppingFeed\Model\Generator\Cache',
            ['getCache']
        );

        $this->feedMock = $this->createCompatibleMock(
            'MageOS\ShoppingFeed\Model\Feed',
            ['getId', 'getConfig']
        );

        $this->model = $this->objectManagerHelper->getObject(
            'MageOS\ShoppingFeed\Model\Product\Filter',
            [
                'cache' => $this->cacheMock
            ]
        );
    }

    public function testCustomCsvPreservesLiteralAndEncodedCommas(): void
    {
        $this->feedMock->setData('type', 'generic');
        $this->feedMock->method('getConfig')->willReturnCallback(
            fn($key, $default = null) => $key === 'output_params_delimiter' ? ',' : $default
        );
        $this->model->setFeed($this->feedMock);
        $this->assertSame('"Quoted", café, 東京', $this->model->cleanField('&quot;Quoted&quot;, café&#44; 東京'));
    }

    /** @dataProvider unclosedMarkup */
    #[DataProvider('unclosedMarkup')]
    public function testUnclosedMarkupFragmentsAreRemoved(string $input, string $expected): void
    {
        $this->model->setFeed($this->feedMock);
        self::assertSame($expected, $this->model->cleanField($input));
    }

    public static function unclosedMarkup(): array
    {
        return [
            ['Description <img src=x onerror=alert(1)//', 'Description'],
            ['Description &lt;img src=x', 'Description'],
            ['Description <script src=x', 'Description'],
            ['Description <img', 'Description'],
            ['Description <img src="broken <b>Following text</b>', 'Description Following text'],
            ['Size 5 < 10 and 20 > 15', 'Size 5 < 10 and 20 > 15'],
        ];
    }

    public function testEnclosedCustomDelimiterSurvivesCleaning(): void
    {
        $settings = ['output_params_delimiter' => '|', 'output_params_enclose_cell' => '"'];
        $this->feedMock->method('getConfig')->willReturnCallback(
            static fn($key, $default = null) => $settings[$key] ?? $default
        );
        $this->model->setFeed($this->feedMock);
        $this->assertSame('A|B|C', $this->model->cleanField('A|B&#124;C'));
    }

    public function testFindAndReplace()
    {
        $params = 'columnName';
        $string = 'String: FIND STRING, FIND ALL';

        $this->cacheMock->expects($this->any())
            ->method('getCache')
            ->will(
                $this->onConsecutiveCalls(
                    true, [
                    'columnName' => ['find' => 'FIND STRING', 'replace' => 'REPLACE STRING'],
                    '-all-' => ['find' => 'FIND ALL', 'replace' => 'REPLACE ALL']
                    ]
                )
            );

        $this->feedMock->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(1));
        $this->feedMock->expects($this->any())
            ->method('getConfig')
            ->will(
                $this->returnValue(
                    [
                    ['columns' => '', 'find' => 'find string', 'replace' => 'replace string'],
                    ['columns' => 'column1', 'find' => 'find string', 'replace' => 'replace string'],
                    ]
                )
            );
        $this->model->setFeed($this->feedMock);

        $expected = 'String: REPLACE STRING, REPLACE ALL';
        $this->model->findAndReplace($string, $params);
        $this->assertEquals($expected, $string);
    }


    public function testCleanField()
    {
        $params = ['column' => 'columnName'];
        $field = ' A"\'B' . "\nC <span>content</span><br /> > path&nbsp;SPACE\t";

        $this->feedMock->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(1));

        $this->model->setFeed($this->feedMock);

        $this->cacheMock->expects($this->any())
            ->method('getCache')
            ->will($this->returnValue([]));

        $this->assertEquals('A"\'B C content > path SPACE', $this->model->cleanField($field, $params));
    }
    public function testDecodedEntitiesCannotIntroduceFeedDelimiters(): void
    {
        $this->feedMock->method('getConfig')->willReturnCallback(
            fn($key, $default = null) => $key === 'output_params_delimiter' ? '\\t' : $default
        );
        $this->model->setFeed($this->feedMock);
        foreach (['&#09;', '&#x9;', '&#13;', '&#x0D;'] as $entity) {
            $cell = $this->model->cleanField('before' . $entity . 'after');
            $this->assertSame('before after', $cell, $entity);
            $this->assertCount(2, explode("\t", $cell . "\t" . 'next column'));
        }
    }

    public function testDecodedCustomDelimiterIsRemoved(): void
    {
        $this->feedMock->method('getConfig')->willReturnCallback(
            fn($key, $default = null) => ['output_params_delimiter' => 'other', 'output_params_delimiter_other' => '|'][$key] ?? $default
        );
        $this->model->setFeed($this->feedMock);
        $this->assertSame('before after', $this->model->cleanField('before&#124;after'));
    }

    public function testColumnLimitsCountUtf8CharactersInsteadOfBytes(): void
    {
        $this->feedMock->method('getConfig')->willReturn([['column' => 'title', 'limit' => 3]]);
        $this->model->setFeed($this->feedMock);
        foreach (['é漢😀end' => 'é漢😀', '漢字' => '漢字', 'abcdef' => 'abc'] as $input => $expected) {
            $this->model->limitOutput($input, 'title');
            $this->assertSame($expected, $input);
            $this->assertTrue(mb_check_encoding($input, 'UTF-8'));
        }
    }

    /** @dataProvider plainDescriptionCases */
    #[DataProvider('plainDescriptionCases')]
    public function testPageBuilderDescriptionCleaning(string $input, string $expected, ?int $limit): void
    {
        $settings = ['output_params_delimiter' => '\\t', 'filters_output_limit' => $limit === null
            ? [] : [['column' => 'description', 'limit' => $limit]]];
        $this->feedMock->method('getConfig')->willReturnCallback(
            static fn($key, $default = null) => $settings[$key] ?? $default
        );
        $this->cacheMock->method('getCache')->willReturn([]);
        $this->model->setFeed($this->feedMock);
        $this->assertSame($expected, $this->model->cleanField($input, ['column' => 'description']));
    }

    public static function plainDescriptionCases(): array
    {
        return [
            'escaped HTML code' => [
                '<div>&lt;h2&gt;Oil Drain Flange&lt;/h2&gt;&lt;p&gt;Includes gasket&lt;/p&gt;</div>',
                'Oil Drain Flange Includes gasket',
                null,
            ],
            'raw style and script' => [
                '<style>#html-body{color:red}</style><p>Useful text</p><script>alert(1)</script>',
                'Useful text',
                null,
            ],
            'escaped style and script' => [
                '&lt;style&gt;.rule{color:red}&lt;/style&gt;&lt;p&gt;Useful text&lt;/p&gt;&lt;script&gt;alert(1)&lt;/script&gt;',
                'Useful text',
                null,
            ],
            'double entities' => [
                '&lt;p&gt;Oil&amp;nbsp;Drain &amp;amp; Gasket&lt;/p&gt;',
                "Oil\u{00a0}Drain & Gasket",
                null,
            ],
            'limit after cleaning' => [
                '<div data-content-type="html">&lt;p&gt;Oil Drain Flange&lt;/p&gt;</div>',
                'Oil Drain',
                9,
            ],
            'literal comparison' => ['1 &lt; 2 and 3 &gt; 2', '1 < 2 and 3 > 2', null],
            'double comparison' => ['1 &amp;lt; 2', '1 < 2', null],
            'double encoded separators' => ['before&amp;#9;after', 'before after', null],
            'comments and doctype' => ['<!DOCTYPE html><!-- hidden --><p>Visible</p>', 'Visible', null],
            'quoted greater-than attribute' => ['<p title="1 > 0">Useful text</p>', 'Useful text', null],
            'inch mark in media image attributes' => [
                '<p>Shroud <img src="{{media url="wysiwyg/a.png"}}" alt="Radiator 12" " width="800" /> Fan</p>',
                'Shroud Fan',
                null,
            ],
            'doubled quote after media directive' => [
                '<img src="{{media url=&quot;x.jpg&quot;}}"" alt="" />Text',
                'Text',
                null,
            ],
            'stray double quote before heading' => [
                '<img alt="3.5" core" /></p><h3>3.5" Thick - "Hot Flow" Options</h3><ul><li>A</li>',
                '3.5" Thick - "Hot Flow" Options A',
                null,
            ],
            'stray single quote before heading' => [
                "<img alt='3.5' core' /></p><h3>3.5' Thick - 'Hot Flow' Options</h3><ul><li>A</li>",
                "3.5' Thick - 'Hot Flow' Options A",
                null,
            ],
            'unclosed double quote in container tag' => [
                '<p>Before <span title="broken>inside</span> After</p>',
                'Before inside After',
                null,
            ],
            'unclosed single quote in container tag' => [
                "<p>Before <span title='broken>inside</span> After</p>",
                'Before inside After',
                null,
            ],
            'escaped malformed image tag' => [
                '&lt;img src=&quot;{{media url=&quot;x.jpg&quot;}}&quot;&quot; alt=&quot;&quot; /&gt;Text',
                'Text',
                null,
            ],
            'double escaped malformed container tag' => [
                '&amp;lt;span title=&amp;quot;broken&amp;gt;Visible&amp;lt;/span&amp;gt;',
                'Visible',
                null,
            ],
            'limit after malformed tag cleaning' => [
                '<img alt="3.5" core" /></p><h3>3.5" Thick - "Hot Flow" Options</h3><ul><li>A</li>',
                '3.5" Thick',
                10,
            ],
            'literal comparisons beside inch marks' => [
                '3 < 5 > 2; 3.5" Thick - "Hot Flow"',
                '3 < 5 > 2; 3.5" Thick - "Hot Flow"',
                null,
            ],
            'double quoted greater-than image attribute' => ['<img alt="a > b">Text', 'Text', null],
            'single quoted greater-than image attribute' => ["<img alt='a > b'>Text", 'Text', null],
            'double quoted less-than image attribute' => ['<img alt="a < b">Text', 'Text', null],
            'single quoted less-than image attribute' => ["<img alt='a < b'>Text", 'Text', null],
            'quoted comparisons in image attribute' => ['<img alt="1 < 2 > 0">Text', 'Text', null],
            'double quoted unspaced comparison attribute' => ['<img alt="a<b">Text', 'Text', null],
            'single quoted unspaced comparison attribute' => ["<img alt='a<b'>Text", 'Text', null],
        ];
    }
}
