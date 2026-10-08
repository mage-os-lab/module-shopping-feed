<?php
declare(strict_types=1);

namespace MageOS\ShoppingFeed\Test\Unit\Controller\Adminhtml\Feed;

use MageOS\ShoppingFeed\Controller\Adminhtml\Feed\Builder;
use MageOS\ShoppingFeed\Controller\Adminhtml\Feed\Generate;
use MageOS\ShoppingFeed\Controller\Adminhtml\Feed\Viewlog;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
class InvalidIdentityTest extends TestCase
{
    public function testMalformedGenerationIdReturnsToTheGridWithoutChangingTheQueue(): void
    {
        [$context, $builder, $messages] = $this->dependencies();
        $messages->expects(self::once())->method('addErrorMessage')->with('Invalid id value.');
        $redirect = $this->createMock(\Magento\Backend\Model\View\Result\Redirect::class);
        $redirect->expects(self::once())->method('setPath')->with('*/*/index')->willReturnSelf();
        $factory = $this->createMock(\Magento\Backend\Model\View\Result\RedirectFactory::class);
        $factory->method('create')->willReturn($redirect);
        $context->method('getResultRedirectFactory')->willReturn($factory);
        $queues = $this->createMock(\MageOS\ShoppingFeed\Model\ResourceModel\Generator\Queue\Collection::class);
        $queues->expects(self::never())->method('walk');
        $queueFactory = $this->createMock(\MageOS\ShoppingFeed\Model\Generator\QueueFactory::class);
        $queueFactory->expects(self::never())->method('create');
        $controller = new Generate($context, $builder, $queues, $queueFactory,
            $this->createMock(\Magento\Framework\Registry::class));
        self::assertSame($redirect, $controller->execute());
    }

    public function testMalformedLogIdReturnsToTheGridWithoutRenderingALog(): void
    {
        [$context, $builder, $messages] = $this->dependencies();
        $messages->expects(self::once())->method('addErrorMessage')->with('Invalid id value.');
        $forward = $this->createMock(\Magento\Backend\Model\View\Result\Forward::class);
        $forward->expects(self::once())->method('forward')->with('index')->willReturnSelf();
        $forwardFactory = $this->createMock(\Magento\Backend\Model\View\Result\ForwardFactory::class);
        $forwardFactory->method('create')->willReturn($forward);
        $pages = $this->createMock(\Magento\Framework\View\Result\PageFactory::class);
        $pages->expects(self::never())->method('create');
        self::assertSame($forward, (new Viewlog($context, $builder, $pages, $forwardFactory))->execute());
    }

    private function dependencies(): array
    {
        $request = $this->createMock(\Magento\Framework\App\RequestInterface::class);
        $request->method('getParams')->willReturn(['id' => 'abc']);
        $messages = $this->createMock(\Magento\Framework\Message\ManagerInterface::class);
        $context = $this->createMock(Context::class);
        $context->method('getRequest')->willReturn($request);
        $context->method('getMessageManager')->willReturn($messages);
        $builder = $this->createMock(Builder::class);
        $builder->method('build')->willThrowException(new LocalizedException(__('Invalid id value.')));
        return [$context, $builder, $messages];
    }
}
