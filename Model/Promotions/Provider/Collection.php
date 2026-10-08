<?php

namespace MageOS\ShoppingFeed\Model\Promotions\Provider;

class Collection
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \MageOS\ShoppingFeed\Model\Feed\ConfigFactory
     */
    protected $configFactory;

    /**
     * @var \MageOS\ShoppingFeed\Model\ResourceModel\Feed\Config\CollectionFactory
     */
    protected $configCollectionFactory;

    /**
     * @var \Magento\SalesRule\Model\ResourceModel\Rule\Quote\Collection
     */
    protected $ruleQuoteCollectionFactory;

    /**
     * @var \MageOS\ShoppingFeed\Model\Feed
     */
    protected $feed;

    protected $resourceConnection;


    public function __construct(
        \Magento\SalesRule\Model\ResourceModel\Rule\Quote\CollectionFactory $ruleQuoteCollectionFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \MageOS\ShoppingFeed\Model\Feed\ConfigFactory $configFactory,
        \MageOS\ShoppingFeed\Model\ResourceModel\Feed\Config\CollectionFactory $configCollectionFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ) {

        $this->ruleQuoteCollectionFactory = $ruleQuoteCollectionFactory;
        $this->storeManager = $storeManager;
        $this->configFactory = $configFactory;
        $this->configCollectionFactory = $configCollectionFactory;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Get magento's promotion rules collection
     *
     * @param \MageOS\ShoppingFeed\Model\Feed $feed
     * @return \Magento\SalesRule\Model\ResourceModel\Rule\Quote\Collection
     */
    public function getPromotionRules(\MageOS\ShoppingFeed\Model\Feed $feed)
    {
        $storeId = $feed->getStoreId();
        $websiteId =  $this->storeManager->getStore($storeId)->getWebsiteId();
        $groupIds = \Magento\Customer\Model\Group::NOT_LOGGED_IN_ID;

        /** @var \Magento\SalesRule\Model\ResourceModel\Rule\Quote\Collection $collection */
        $collection = $this->ruleQuoteCollectionFactory->create()
            ->addWebsiteFilter($websiteId)->addIsActiveFilter();

        $customer_group_rule_column =
            $collection->getConnection()->tableColumnExists(
                $this->resourceConnection->getTableName('salesrule_customer_group'),
                'row_id'
            )
            ? 'row_id' : 'rule_id';

        $collection->getSelect()->joinInner(
            ['customer_group_ids' => $this->resourceConnection->getTableName('salesrule_customer_group')],
            $collection->getConnection()->quoteInto('main_table.rule_id = customer_group_ids.'. $customer_group_rule_column. '
                AND customer_group_ids.customer_group_id = ?', (int)$groupIds),
            []
        );

        return $collection;
    }

    /**
     * Updates google promotions configuration
     *
     * @param \MageOS\ShoppingFeed\Model\Feed $feed
     * @param array $data
     * @return $this
     */
    public function updatePromotionsData(\MageOS\ShoppingFeed\Model\Feed $feed, $data = [])
    {
        $lookup = $this->configCollectionFactory->create()
            ->addFieldToSelect('*')
            ->setFeedFilter($feed)
            ->addFieldToFilter('path', 'promotions_provider_widget')
            ->load();

        $model = count($lookup) == 0 ? $this->configFactory->create() : $lookup->getFirstItem();

        $model->addData([
            'feed_id' => $feed->getId(),
            'path' => 'promotions_provider_widget',
            'value' => $data
        ]);
        $model->save();

        // Force a reload since the config has changed.
        return $feed->load($feed->getId());
    }
}
