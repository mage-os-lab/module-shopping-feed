<?php

namespace MageOS\ShoppingFeed\Model\Promotions;

class Provider
{
    const MAX_PROMOTION_DAYS = 183;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    protected $directoryList;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var \Magento\Quote\Model\Quote\ItemFactory
     */
    protected $quoteItem;

    /**
     * @var Provider\Map
     */
    protected $map;

    /**
     * @var Provider\Collection
     */
    protected $promotionsCollection;

    /**
     * @var \MageOS\ShoppingFeed\Model\Feed\OutputPath
     */
    protected $outputPath;

    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     */
    protected $fileDriver;

    /**
     * @var \MageOS\ShoppingFeed\Model\Feed
     */
    private $feed = null;

    /**
     * @var string
     */
    protected $hashCache = null;

    /**
     * @var string|null
     */
    protected $hashCacheKey = null;

    private bool $promotionCacheDirty = false;
    private int $cacheBatchDepth = 0;

    /**
     * @var bool
     */
    protected $fileCreated = false;


    public function __construct(
        \Magento\Framework\Json\Helper\Data $helper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Quote\Model\Quote\ItemFactory $quoteItem,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \MageOS\ShoppingFeed\Model\Promotions\Provider\Map $map,
        \MageOS\ShoppingFeed\Model\Promotions\Provider\Collection $promotionsCollection,
        \MageOS\ShoppingFeed\Model\Feed\OutputPath $outputPath,
        \Magento\Framework\Filesystem\Driver\File $fileDriver
    ) {

        $this->helper = $helper;
        $this->directoryList = $directoryList;
        $this->storeManager = $storeManager;
        $this->quoteItem = $quoteItem;
        $this->objectManager = $objectManager;
        $this->map = $map;
        $this->promotionsCollection = $promotionsCollection;
        $this->outputPath = $outputPath;
        $this->fileDriver = $fileDriver;
    }

    /**
     * @param \MageOS\ShoppingFeed\Model\Feed $feed
     * @return $this
     */
    public function setFeed(\MageOS\ShoppingFeed\Model\Feed $feed)
    {
        if ($this->feed === $feed) {
            return $this;
        }
        $this->flushPromotionCache();
        $this->feed = $feed;
        $this->hashCache = null;
        $this->hashCacheKey = null;
        $this->fileCreated = false;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getFeed()
    {
        return $this->feed;
    }

    /**
     * @return string
     */
    public function getDateFormat()
    {
        return 'Y/m/d';
    }

    /**
     * @return string
     */
    public function getPromotionDateFormat()
    {
        return 'Y-m-d';
    }

    /**
     * @return bool
     */
    public function isFileCreated()
    {
        return $this->fileCreated;
    }

    /**
     * @return $this
     */
    public function setFileCreated()
    {
        $this->fileCreated = true;
        return $this;
    }

    /**
     * @return string
     */
    public function getPromotionFile()
    {
        return $this->outputPath->getPromotionFile($this->getFeed());
    }

    /**
     * Compare magento collection & saved data collection - if saved row doesn't exists
     * in magento collection, it should be removed!
     *
     * @param \MageOS\ShoppingFeed\Model\Feed $feed
     * @return $this
     */
    public function validateGooglePromotions()
    {
        $config = $this->getFeed()->getConfig('promotions_provider_widget');
        $cartRulesIds = $this->promotionsCollection->getPromotionRules($this->getFeed())->getAllIds();

        if (is_array($config) && is_array($config['promotion'] ?? null)) {
            $promotionIds = array_keys($config['promotion']);
            $removeIds = array_diff($promotionIds, $cartRulesIds);
            if (count($removeIds) > 0) {
                foreach ($removeIds as $removeId) {
                    unset($config['promotion'][$removeId]);
                }
                $this->promotionsCollection->updatePromotionsData($this->getFeed(), $config);
            }
        }
        return $this;
    }

    /**
     * Getter method to handle cache
     *
     * @param int $productId
     * @param string $hash
     * @return bool
     */
    protected function getPromotionCache($productId = 0, $hash = '')
    {
        $cache = $this->getHashCache($hash);
        if (isset($cache[$productId]) && is_array($cache[$productId])) {
            return $cache[$productId];
        }
        return false;
    }

    /**
     * Retain results in memory; full generations write the file once per batch.
     *
     * @param int $productId
     * @param string $hash
     * @param array $promotionIds
     * @return $this
     */
    protected function setPromotionCache($productId = 0, $hash = '', $promotionIds = [])
    {
        $cache = $this->getHashCache($hash);
        $cache[$productId] = $promotionIds;

        // Update current cache
        $this->hashCache = $cache;
        $this->hashCacheKey = $hash;
        $this->promotionCacheDirty = true;
        if ($this->cacheBatchDepth === 0) {
            $this->flushPromotionCache();
        }
        return $this;
    }

    public function beginCacheBatch(): void
    {
        $this->cacheBatchDepth++;
    }

    public function endCacheBatch(): void
    {
        if ($this->cacheBatchDepth > 0) {
            $this->cacheBatchDepth--;
        }
        if ($this->cacheBatchDepth === 0) {
            $this->flushPromotionCache();
        }
    }

    public function flushPromotionCache(): void
    {
        if (!$this->promotionCacheDirty) {
            return;
        }

        // Update file cache without exposing readers to a partially-written JSON document.
        $cacheFile = $this->getHashFile();
        $temporaryFile = $cacheFile . '.' . getmypid() . '.tmp';
        $fileContent = [
            'cache' => $this->hashCache,
            'hash' => $this->hashCacheKey
        ];
        $this->fileDriver->createDirectory(dirname($cacheFile));
        if ($this->fileDriver->isExists($temporaryFile)) {
            $this->fileDriver->deleteFile($temporaryFile);
        }

        try {
            $this->fileDriver->filePutContents($temporaryFile, $this->helper->jsonEncode($fileContent));
            $this->fileDriver->rename($temporaryFile, $cacheFile);
        } catch (\Throwable $exception) {
            if ($this->fileDriver->isExists($temporaryFile)) {
                $this->fileDriver->deleteFile($temporaryFile);
            }
            throw $exception;
        }

        $this->promotionCacheDirty = false;
    }

    /**
     * Removes cache file
     * Not used currently (it should be in the observer)
     *
     * @return $this
     */
    public function clearPromotionCache()
    {
        $cacheFile = $this->getHashFile();
        if ($this->fileDriver->isExists($cacheFile)) {
            $this->fileDriver->deleteFile($cacheFile);
        }
        $this->hashCache = null;
        $this->hashCacheKey = null;
        $this->promotionCacheDirty = false;
        return $this;
    }

    /**
     * Gets cache from file if its not available inside the Varien_Object
     *
     * @param string $hash
     * @return array
     */
    protected function getHashCache($hash = '')
    {
        if ($this->hashCache === null || $this->hashCacheKey !== $hash) {
            $cacheFile = $this->getHashFile();
            $cache = [];
            if ($this->fileDriver->isExists($cacheFile) && $this->fileDriver->isReadable($cacheFile)) {
                $fileContent = $this->fileDriver->fileGetContents($cacheFile);
                $tmpCache = $this->helper->jsonDecode($fileContent);
                if (is_array($tmpCache) && isset($tmpCache['hash']) && $tmpCache['hash'] == $hash && isset($tmpCache['cache'])) {
                    $cache = $tmpCache['cache'];
                }
            }
            $this->hashCache = $cache;
            $this->hashCacheKey = $hash;
        }
        return $this->hashCache;
    }

    /**
     * Returns promotion cache file location
     *
     * @return string
     */
    /**
     * @return string
     */
    protected function getHashFile()
    {
        return rtrim($this->directoryList->getPath('var'), '/') .'/cache/mageos_shopping_feed_promotions.cache';
    }

    /**
     * Prepare date for output
     *
     * @param \MageOS\ShoppingFeed\Model\Feed $feed
     * @param $promotionDate
     * @param $rowDate
     * @param bool $addDays
     * @param bool $format
     * @return string
     */
    public function prepareDate($promotionDate, $rowDate, $addDaysFrom = false, $format = false)
    {
        /** @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezoneObject */
        $timezoneObject = $this->objectManager->create('\Magento\Framework\Stdlib\DateTime\Timezone');
        $store = $this->storeManager->getStore($this->getFeed()->getStoreId());

        $timezone = $timezoneObject->getConfigTimezone(\Magento\Store\Model\ScopeInterface::SCOPE_STORE, $store);
        $timezone = $timezone ? new \DateTimeZone($timezone) : new \DateTimeZone(\DateTimeZone::UTC);

        $add = true;
        $date = $timezoneObject->date();

        if (!empty($rowDate)) {
            //$date = $timezoneObject->date($rowDate);
            $date =  $date->createFromFormat($this->getDateFormat(), $rowDate, $timezone);
            $add = false;
        } elseif (!empty($promotionDate)) {
            // $date = $timezoneObject->date($promotionDate);
            $date = $date->createFromFormat($this->getPromotionDateFormat(), $promotionDate, $timezone);
            $add = false;
        }
        if (!$date) {
            return '';
        }
        $date->setTime(12, 0, 0);

        if ($addDaysFrom !== false && $add === true) {
            $date = $date->createFromFormat($this->getDateFormat(), $addDaysFrom, $timezone);
            $date->add(new \DateInterval('P'.self::MAX_PROMOTION_DAYS. 'D'));
        }

        //$date->setTimezone($timezone);

        if ($format === false) {
            $format = $this->getDateFormat();
        }

        return $date->format($format);
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return array
     */
    public function getPromotionIds(\Magento\Catalog\Model\Product $product)
    {
        $ids = [];
        $activeIds = [];
        $productId = $product->getId();

        if (is_null($this->getFeed())) {
            return $ids;
        }

        $config = $this->getFeed()->getConfig('promotions_provider_widget');
        /**
         * If promotion is not correctly structured, we don't go any further
         * If hash is not set, the promotions weren't generated
         */
        if ($this->getFeed()->getConfig('promotions_enabled')
            && is_array($config)
            && isset($config['hash'])
            && isset($config['promotion'])
            && is_array($config['promotion'])
            && count($config['promotion']) > 0
        ) {
            foreach ($config['promotion'] as $k => $row) {
                if (!empty($row['include'])) {
                    $activeIds[] = $k;
                }
            }
            if (!$activeIds) {
                return [];
            }

            $ids = $this->getPromotionCache($productId, $config['hash']);
            if ($ids === false) {
                $ids = [];
                $this->map->setProvider($this);

                /** @var \Magento\SalesRule\Model\ResourceModel\Rule\Quote\Collection $cartRulesCollection */
                $cartRulesCollection = $this->promotionsCollection->getPromotionRules($this->getFeed());

                if (count($activeIds)) {
                    $cartRulesCollection->addFieldToFilter('rule_id', ['in' => $activeIds]);
                }

                /** @var \Magento\SalesRule\Model\Rule $rule */
                foreach ($cartRulesCollection as $rule) {
                    /**
                     * Because the rule->validate works on different types (quote/quote_item/...)
                     * the following manipulation is needed
                     */
                    $quoteItem = $this->quoteItem->create()->setProduct($product);
                    if ($rule->getActions()->validate($quoteItem)) {
                        $ids[$rule->getId()] = $this->map->mapPromotionId($config['counter'], $rule);
                    }
                }

                // Save to cache
                $this->setPromotionCache($productId, $config['hash'], $ids);
            }
        }

        return array_filter(
            $ids,
            function ($key) use ($activeIds) {
                return in_array($key, $activeIds);
            },
            ARRAY_FILTER_USE_KEY
        );
    }
}
