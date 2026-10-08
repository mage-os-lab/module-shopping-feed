<?php
declare(strict_types=1);
namespace MageOS\ShoppingFeed\Test\Unit\Model\Generator;

use MageOS\ShoppingFeed\Model\Generator;
use MageOS\ShoppingFeed\Model\Generator\Batch;
use MageOS\ShoppingFeed\Model\Generator\Queue;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
class RecoveryTest extends TestCase
{
    public function testAutomaticBatchSwitchWithoutSchedulesPersistsOnlyTheQueue(): void
    {
        $feed = $this->createMock(\MageOS\ShoppingFeed\Model\Feed::class);
        $feed->method('isProductTypeEnabled')->willReturn(true);
        $feed->method('getSchedules')->willReturn([]);
        $product = $this->createMock(\Magento\Catalog\Model\Product::class);
        $product->method('setStoreId')->willReturnSelf();
        $product->method('load')->willReturnSelf();
        $productFactory = $this->createMock(\Magento\Catalog\Model\ProductFactory::class);
        $productFactory->method('create')->willReturn($product);
        $adapters = $this->createMock(\MageOS\ShoppingFeed\Model\Product\Adapter\AdapterFactory::class);
        $adapters->method('create')->willReturn(false);
        $memory = $this->createMock(\MageOS\ShoppingFeed\Model\Generator\Memory::class);
        $memory->method('format')->willReturn('memory');
        $memory->method('isCloseToPhpLimit')->willReturn(true);
        $scheduleFactory = $this->createMock(\MageOS\ShoppingFeed\Model\Feed\ScheduleFactory::class);
        $scheduleFactory->expects(self::never())->method('create');
        $queue = $this->getMockBuilder(Queue::class)->disableOriginalConstructor()->onlyMethods(['save'])->getMock();
        $queue->setId(1);
        $queue->expects(self::once())->method('save')->willReturnSelf();
        $batch = new Batch();
        $generator = $this->getMockBuilder(Generator::class)->disableOriginalConstructor()
            ->onlyMethods(['logProgress', 'getLogger'])->getMock();
        $generator->method('getLogger')->willReturn($this->createMock(\MageOS\ShoppingFeed\Model\Logger::class));
        foreach ([
            'feed' => $feed, 'productFactory' => $productFactory, 'adapterFactory' => $adapters,
            'memory' => $memory, 'scheduleFactory' => $scheduleFactory, 'batch' => $batch, 'queue' => $queue,
            'logger' => $this->createMock(\MageOS\ShoppingFeed\Model\Logger::class),
        ] as $key => $value) {
            (new \ReflectionProperty($generator, $key))->setValue($generator, $value);
        }
        try {
            $generator->processProductCallback(['row' => ['type_id' => 'simple', 'sku' => 'SKU', 'entity_id' => 1]]);
            self::fail('Expected a resumable batch checkpoint.');
        } catch (\MageOS\ShoppingFeed\Model\Exception $exception) {
            self::assertStringContainsString('EARLY END', $exception->getMessage());
        }
        self::assertTrue($queue->getBatch()->isEnabled());
        self::assertSame(1, $queue->getBatch()->getOffset());
        self::assertSame(1, $queue->getBatch()->getLimit());
    }
    private function generator(Batch $batch, Queue $queue): Generator
    {
        $generator = (new \ReflectionClass(Generator::class))->newInstanceWithoutConstructor();
        $feed = $this->getMockBuilder(\MageOS\ShoppingFeed\Model\Feed::class)
            ->disableOriginalConstructor()->onlyMethods([])->getMock();
        $feed->setData('type', 'google_shopping');
        foreach (['batch' => $batch, 'queue' => $queue, 'feed' => $feed, 'fileDriver' => new \Magento\Framework\Filesystem\Driver\File()] as $key => $value) {
            (new \ReflectionProperty($generator, $key))->setValue($generator, $value);
        }
        return $generator;
    }

    public function testCheckpointClosesOutputBeforeSavingTheNextOffset(): void
    {
        $queue = $this->getMockBuilder(Queue::class)->disableOriginalConstructor()->onlyMethods(['save'])->getMock();
        $queue->setId(1);
        $batch = new Batch(['enabled' => true, 'offset' => 100]);
        $generator = $this->generator($batch, $queue);
        $handle = tmpfile();
        $generator->setData('temporary_handle', $handle);
        (new \ReflectionProperty($generator, 'currentIteration'))->setValue($generator, 200);
        $queue->expects($this->once())->method('save')->willReturnCallback(function () use ($queue, $handle) {
            $this->assertFalse(is_resource($handle));
            $this->assertSame(200, $queue->getBatch()->getOffset());
            $this->assertSame(0, $queue->getData('is_read'));
            return $queue;
        });
        try {
            $this->assertTrue($generator->updateBatchQueue());
        } finally {
            (new \ReflectionMethod($generator, 'closeTemporaryHandle'))->invoke($generator);
            (new \ReflectionProperty($generator, 'batch'))->setValue($generator, null);
        }
    }

    public function testInterruptedBatchReplacesPartialFileInsteadOfAppendingDuplicates(): void
    {
        $queue = $this->getMockBuilder(Queue::class)->disableOriginalConstructor()->onlyMethods(['save'])->getMock();
        $queue->setId(1)->setData('is_read', 1);
        $batch = new Batch(['enabled' => true, 'offset' => 500, 'limit' => 100]);
        $queue->setBatch($batch)->setRunning();
        $file = tempnam(sys_get_temp_dir(), 'shopping-feed-recovery-');
        file_put_contents($file . '.tmp', "old batch\npartial row\n");
        $generator = $this->generator($batch, $queue);
        $generator->setData('feed_file', $file);
        try {
            $handle = (new \ReflectionMethod($generator, 'getTemporaryHandle'))->invoke($generator);
            fwrite($handle, "new batch\n");
            (new \ReflectionMethod($generator, 'closeTemporaryHandle'))->invoke($generator);
            $this->assertSame(Generator::UTF8_BOM . "new batch\n", file_get_contents($file . '.tmp'));
        } finally {
            (new \ReflectionMethod($generator, 'closeTemporaryHandle'))->invoke($generator);
            (new \ReflectionProperty($generator, 'batch'))->setValue($generator, null);
            unlink($file . '.tmp');
            unlink($file);
        }
    }
}
