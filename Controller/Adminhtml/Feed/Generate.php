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

namespace MageOS\ShoppingFeed\Controller\Adminhtml\Feed;

use MageOS\ShoppingFeed\Model\ResourceModel\Generator\Queue\Collection as QueueCollection;
use MageOS\ShoppingFeed\Model\Generator\QueueFactory;
use Magento\Framework\App\Action\HttpPostActionInterface;

class Generate extends \Magento\Backend\App\Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'MageOS_ShoppingFeed::generate';

    /**
     * @var \MageOS\ShoppingFeed\Controller\Adminhtml\Feed\Builder
     */
    protected $feedBuilder;

    /**
     * @var QueueCollection
     */
    protected $queueCollection;

    /**
     * @var QueueFactory
     */
    protected $queueFactory;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * Generate constructor.
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param Builder                             $feedBuilder
     * @param QueueCollection                     $queueCollection
     * @param QueueFactory                        $queueFactory
     * @param \Magento\Framework\Registry         $registry
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \MageOS\ShoppingFeed\Controller\Adminhtml\Feed\Builder $feedBuilder,
        QueueCollection $queueCollection,
        QueueFactory $queueFactory,
        \Magento\Framework\Registry $registry
    ) {
        $this->feedBuilder = $feedBuilder;
        $this->queueCollection = $queueCollection;
        $this->queueFactory = $queueFactory;
        $this->registry = $registry;

        parent::__construct($context);
    }

    /**
     * Add feed to queue
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        try {
            $feed = $this->feedBuilder->build($this->getRequest()->getParams());
        } catch (\Magento\Framework\Exception\LocalizedException $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
            return $this->resultRedirectFactory->create()->setPath('*/*/index');
        }

        if (!$feed->getId()) {
            $this->messageManager->addError(__('Feed wasn\'t found'));
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath('*/*/index');
        }

        // Clear old queues;
        $this->queueCollection->getSelect()->where('feed_id = ?', $feed->getId());
        $this->queueCollection->walk('delete');

        $queue = $this->queueFactory->create();
        // Initialize the batch with first schedule config.
        $schedules = $feed->getSchedules();
        if (count($schedules)) {
            $queue->getBatch()
                ->setOffset(0)
                ->setLimit(intval($schedules[0]['batch_limit']))
                ->setEnabled(boolval($schedules[0]['batch_mode']));
        }
        $queue->add($feed);

        $feed->saveStatus(\MageOS\ShoppingFeed\Model\Feed\Source\Status::STATUS_PENDING);
        $this->messageManager->addSuccess(__('Feed added in processing queue.'));

        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setPath('*/*/index');
    }
}
