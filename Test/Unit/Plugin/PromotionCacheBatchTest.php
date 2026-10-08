<?php
declare(strict_types=1);

namespace MageOS\ShoppingFeed\Test\Unit\Plugin;

use MageOS\ShoppingFeed\Model\Generator;
use MageOS\ShoppingFeed\Model\Promotions\Provider;
use MageOS\ShoppingFeed\Plugin\PromotionCacheBatch;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
class PromotionCacheBatchTest extends TestCase
{
    /** @dataProvider exitPaths */
    #[DataProvider('exitPaths')]
    public function testCacheBatchEndsOnEveryGeneratorExit(?\Throwable $exception): void
    {
        $provider = $this->createMock(Provider::class);
        $provider->expects(self::once())->method('beginCacheBatch');
        $provider->expects(self::once())->method('endCacheBatch');
        $generator = $this->getMockBuilder(Generator::class)->disableOriginalConstructor()
            ->onlyMethods([])->getMock();
        $proceed = static function () use ($generator, $exception) {
            if ($exception !== null) {
                throw $exception;
            }
            return $generator;
        };
        if ($exception !== null) {
            $this->expectExceptionObject($exception);
        }
        self::assertSame($generator, (new PromotionCacheBatch($provider))->aroundRun($generator, $proceed));
    }

    public static function exitPaths(): array
    {
        return [[null], [new \RuntimeException('failure')],
            [new \MageOS\ShoppingFeed\Model\Exception(new \Magento\Framework\Phrase('EARLY END'))]];
    }
}
