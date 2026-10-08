<?php
declare(strict_types=1);

namespace MageOS\ShoppingFeed\Test\Unit\Model\Product\Adapter\Type;

use MageOS\ShoppingFeed\Model\Product\Adapter\AdapterAbstract;
use MageOS\ShoppingFeed\Model\Product\Adapter\Type\Composite;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
class CompositeDiagnosticsTest extends TestCase
{
    public function testEverySkippedChildIsRetainedByReason(): void
    {
        $adapter = (new \ReflectionClass(Composite::class))->newInstanceWithoutConstructor();
        $adapter->setSkipProduct('out_of_stock', 10)->setSkipProduct('out_of_stock', 20)
            ->setSkipProduct('disabled', 30)->setSkipProduct('out_of_stock', 40);
        self::assertSame(
            ['out_of_stock' => [10, 20, 40], 'disabled' => [30]],
            (new \ReflectionProperty($adapter, 'skippedData'))->getValue($adapter)
        );
        self::assertTrue($adapter->getData('is_skipped'));
    }

    public function testUnknownInheritanceModeFailsWithAClearMessage(): void
    {
        $adapter = (new \ReflectionClass(Composite::class))->newInstanceWithoutConstructor();
        $this->expectException(\Magento\Framework\Exception\LocalizedException::class);
        $this->expectExceptionMessage('Unknown product inheritance mode');
        (new \ReflectionMethod($adapter, 'mapByInheritanceFrom'))->invoke(
            $adapter, $this->createMock(AdapterAbstract::class), ['column' => 'title'], 'invalid'
        );
    }
}
