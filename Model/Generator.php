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
 * @author    RocketWeb
 */

namespace MageOS\ShoppingFeed\Model;

use Magento\Framework\DataObject;
use MageOS\ShoppingFeed\Model\Exception as FeedException;
use MageOS\ShoppingFeed\Model\Product\Adapter\AdapterFactory;
use MageOS\ShoppingFeed\Model\Product\CollectionProvider;

class Generator extends DataObject
{
    const XML_LOG_LEVEL = 'mageos_shopping_feed/log/level';
    const XML_LOG_ROTATE = 'mageos_shopping_feed/log/rotate';
    const XML_LOG_PROGRESS = 'mageos_shopping_feed/log/progress_every';
    const UTF8_BOM = "\xEF\xBB\xBF";

    /**
     * @var AdapterFactory
     */
    protected $adapterFactory;

    /**
     * @var Generator\Batch
     */
    protected $batch;

    /**
     * @var \MageOS\ShoppingFeed\Model\Generator\Cache
     */
    protected $cache;

    protected $countProductsExported = 0;
    protected $countProductsSkipped = 0;
    protected $currentIteration = 0;
    protected $skippedData = [];

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    protected $collection;

    /**
     * @var CollectionProvider
     */
    protected $collectionProvider;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $dateTime;

    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     */
    protected $fileDriver;

    /**
     * @var Feed
     */
    protected $feed;

    /** @var \MageOS\ShoppingFeed\Model\Feed\Validation\MetaCatalog */
    private $metaCatalogValidator;

    /** @var \MageOS\ShoppingFeed\Model\Feed\Validation\MicrosoftMerchantCenter */
    private $microsoftMerchantCenterValidator;

    /** @var \MageOS\ShoppingFeed\Model\Feed\Validation\TikTokCatalog */
    private $tikTokCatalogValidator;

    /** @var \MageOS\ShoppingFeed\Model\Feed\Validation\PinterestCatalog */
    private $pinterestCatalogValidator;

    /** @var \MageOS\ShoppingFeed\Model\Feed\Validation\OpenAiGoogleCompatible */
    private $openAiGoogleCompatibleValidator;

    /**
     * @var \Magento\Framework\Model\ResourceModel\Iterator
     */
    protected $iterator;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var Generator\Memory
     */
    protected $memory;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $productFactory;

    /**
     * @var \MageOS\ShoppingFeed\Model\Uploader\UploaderFactory
     */
    protected $uploaderFactory;

    /**
     * @var \MageOS\ShoppingFeed\Model\Uploader\GzipCompressor
     */
    protected $gzipCompressor;

    /**
     * @var \MageOS\ShoppingFeed\Model\ResourceModel\Generator\Process\CollectionFactory
     */
    protected $processCollectionFactory;

    /**
     * @var null
     */
    protected $testSku = null;

    /**
     * @var array
     */
    protected $testOutput = [];

    /**
     * @var Generator\Queue
     */
    protected $queue;

    /**
     * @var Feed\Schedule
     */
    protected $scheduleFactory;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;

    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    protected $directoryList;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \MageOS\ShoppingFeed\Model\Feed\OutputPath
     */
    protected $outputPath;

    /**
     * Generator constructor.
     *
     * @param \Magento\Catalog\Model\ProductFactory                   $productFactory
     * @param \MageOS\ShoppingFeed\Model\Uploader\UploaderFactory $uploaderFactory
     * @param \Magento\Framework\Filesystem\Driver\File               $fileDriver
     * @param \Magento\Framework\Model\ResourceModel\Iterator         $iterator
     * @param Generator\Cache                                         $cache
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface    $dateTime
     * @param CollectionProvider                                      $collectionProvider
     * @param Logger                                                  $logger
     * @param AdapterFactory                                          $adapterFactory
     * @param Generator\Batch                                         $batch
     * @param Generator\Memory                                        $memory
     * @param Feed                                                    $feed
     * @param Generator\Queue|null                                    $queue
     * @param null                                                    $testSku
     * @param array                                                   $data
     */
    public function __construct(
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \MageOS\ShoppingFeed\Model\Uploader\UploaderFactory $uploaderFactory,
        \Magento\Framework\Filesystem\Driver\File $fileDriver,
        \Magento\Framework\Model\ResourceModel\Iterator $iterator,
        \MageOS\ShoppingFeed\Model\Generator\Cache $cache,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $dateTime,
        \MageOS\ShoppingFeed\Model\Product\CollectionProvider $collectionProvider,
        \MageOS\ShoppingFeed\Model\Logger $logger,
        \MageOS\ShoppingFeed\Model\Product\Adapter\AdapterFactory $adapterFactory,
        \MageOS\ShoppingFeed\Model\Generator\Batch $batch,
        \MageOS\ShoppingFeed\Model\Generator\Memory $memory,
        \MageOS\ShoppingFeed\Model\Uploader\GzipCompressor $gzipCompressor,
        \MageOS\ShoppingFeed\Model\ResourceModel\Generator\Process\CollectionFactory $processCollectionFactory,
        \MageOS\ShoppingFeed\Model\Feed $feed,
        \MageOS\ShoppingFeed\Model\Feed\ScheduleFactory $scheduleFactory,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \MageOS\ShoppingFeed\Model\Feed\OutputPath $outputPath,
        \MageOS\ShoppingFeed\Model\Feed\Validation\MetaCatalog $metaCatalogValidator,
        \MageOS\ShoppingFeed\Model\Feed\Validation\MicrosoftMerchantCenter $microsoftMerchantCenterValidator,
        \MageOS\ShoppingFeed\Model\Feed\Validation\TikTokCatalog $tikTokCatalogValidator,
        \MageOS\ShoppingFeed\Model\Feed\Validation\PinterestCatalog $pinterestCatalogValidator,
        \MageOS\ShoppingFeed\Model\Feed\Validation\OpenAiGoogleCompatible $openAiGoogleCompatibleValidator,
        ?\MageOS\ShoppingFeed\Model\Generator\Queue $queue = null,
        $testSku = null,
        $data = []
    ) {

        $this->eventManager = $eventManager;
        $this->adapterFactory = $adapterFactory;
        $this->batch = $batch;
        $this->cache = $cache;
        $this->collectionProvider = $collectionProvider;
        $this->dateTime = $dateTime;
        $this->feed = $feed;
        $this->fileDriver = $fileDriver;
        $this->iterator = $iterator;
        $this->logger = $logger;
        $this->memory = $memory;
        $this->gzipCompressor = $gzipCompressor;
        $this->processCollectionFactory = $processCollectionFactory;
        $this->productFactory = $productFactory;
        $this->uploaderFactory = $uploaderFactory;
        $this->setTestSku($testSku);
        $this->queue = $queue;
        $this->directoryList = $directoryList;
        if (!is_null($queue) && !is_null($queue->getBatch())) {
            $this->batch = $queue->getBatch();
            $this->currentIteration = $this->batch->getOffset();
            if ($this->batch->isEnabled() && $this->batch->getOffset() > 0) {
                $feedData = $this->feed->getMessages();
                $feedData = is_array($feedData) ? $feedData : [];
                $this->countProductsExported = (int)($feedData['exported'] ?? 0);
                $this->countProductsSkipped = (int)($feedData['skipped'] ?? 0);
            }
        }
        $this->scheduleFactory = $scheduleFactory;
        $this->scopeConfig = $scopeConfig;
        $this->outputPath = $outputPath;
        $this->metaCatalogValidator = $metaCatalogValidator;
        $this->microsoftMerchantCenterValidator = $microsoftMerchantCenterValidator;
        $this->tikTokCatalogValidator = $tikTokCatalogValidator;
        $this->pinterestCatalogValidator = $pinterestCatalogValidator;
        $this->openAiGoogleCompatibleValidator = $openAiGoogleCompatibleValidator;

        parent::__construct($data);
    }

    /**
     * @return $this
     */
    public function run()
    {
        //ini_set('memory_limit','83M');

        if (!$this->feed->getId()) {
            throw new FeedException(
                new \Magento\Framework\Phrase('Generator must be created using existing feed - no Feed Id found!')
            );
        }

        $this->eventManager->dispatch(
            sprintf('mageos_shopping_feed_before_generate_%s', $this->feed->getData('type')), [
            'generator' => $this,
            'feed' => $this->feed
            ]
        );

        $time = $this->dateTime->date()->getTimestamp();
        $this->setData('started_at', $time);
        $this->memory->setStartUsage();
        $this->updateStatus(\MageOS\ShoppingFeed\Model\Feed\Source\Status::STATUS_PROCESSING);


        // Log rotate
        $logger = $this->getLogger();
        $logFile = $this->directoryList->getRoot(). $this->getData('feed_log_file');
        $rotateKb = $this->scopeConfig->getValue(self::XML_LOG_ROTATE);
        $rotateKb = is_numeric($rotateKb) && (float)$rotateKb > 0 && is_finite((float)$rotateKb)
            ? (float)$rotateKb : 512;
        if (is_file($logFile) && filesize($logFile) > 1024 * $rotateKb) {
            $archiveFile = $logFile . '.' . date('Y-m-d-H-i-s') . '.gz';
            file_put_contents('compress.zlib://' . $archiveFile, file_get_contents($logFile));
            file_put_contents($logFile, '');
        }

        $logger->notice(sprintf('START %s FEED #%s', strtoupper($this->feed->getType()), $this->feed->getId()));

        // Run any custom pre-generation processes
        //$this->runPreHook();


        /**
 * @var \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
*/
        $collection = $this->getCollection();
        if (!$this->isTestMode() && (!$this->batch->isEnabled() || $this->batch->isNew())) {
            /**
 * @var \MageOS\ShoppingFeed\Model\ResourceModel\Generator\Process\Collection $processCollection
*/
            $processCollection = $this->processCollectionFactory->create();
            $processCollection->truncate($this->feed);
            $this->writeFeed($this->getHeader(), false);
        }

        $this->iterator->walk(
            $collection->getSelect(),
            [[$this, 'processProductCallback']]
        );

        $addedItems = $this->currentIteration;
        if ($this->batch->isEnabled()) {
            $addedItems = $addedItems - (int)$this->batch->getOffset();
        }

        $this->closeTemporaryHandle()
            ->copyDataFromTemporaryFeedFile();

        if (!$this->isTestMode()) {
            $this->getLogger()->debug("---------------------------------------------------------------------");
            $this->getLogger()->notice(
                sprintf(
                    'Processed %d products (%s/%s) | Added %d rows, %d skipped | in file %s(.tmp)',
                    $addedItems,
                    $this->currentIteration,
                    $this->getTotalItems(),
                    $this->getCountProductsExported(),
                    $this->getCountProductsSkipped(),
                    $this->getFeedFile(false)
                )
            );
        }

        $t = round($this->dateTime->date()->getTimestamp() - $time);
        $c = $t/60;
        $h = $t/3600;
        $m = (int)$c%60;
        $s = (int)$t%60;
        $this->getLogger()->notice(
            sprintf(
                'FINISHED | MEMORY USAGE: %s | TIME SPENT: %s',
                $this->memory->format(true),
                sprintf('%02d:%02d:%02d', $h, $m, $s)
            )
        );
        return $this;
    }

    public function processProductCallback($args)
    {
        $row = $args['row'];

        // Skip if product type is not enabled
        if (!$this->feed->isProductTypeEnabled($row['type_id'])) {
            return false;
        }

        $this->getLogger()->debug(
            sprintf(
                '%s | adding product - SKU #%s, ID %s',
                $this->memory->format(),
                $row['sku'],
                $row['entity_id']
            )
        );

        // Prepare product and map object
        try {
            $product = $this->productFactory->create()
                ->setStoreId($this->feed->getStoreId())
                ->load($row['entity_id']);
        } catch (\Exception $e) {
            $this->logger->error(sprintf('Product ID %s - %s', $row['entity_id'], $e->getMessage()));
            return;
        }

        $adapter = $this->adapterFactory->create($product, $this->feed, false);

        if ($adapter === false) {
            $this->updateCountSkip();

            $this->getLogger()->warning(
                sprintf(
                    'Adapter creation failed for type "%s", SKU #%s.',
                    $row['type_id'],
                    $row['sku']
                )
            );
        } else {
            $adapter->setData('generator', $this);
            $this->addProductToFeed($adapter);
        }
        $this->currentIteration++;
        $this->logProgress();



        if ($this->memory->isCloseToPhpLimit($this->getData('started_at'))) {
            // Automatically swicth to batch mode
            $newLimit = $this->currentIteration - $this->batch->getOffset();

            if (!$this->batch->isEnabled()) {
                $this->getLogger()->warning('Automatic switch to batch mode.');

                // Manual feeds may have no schedules; their queue still stores the checkpoint.
                $schedules = $this->feed->getSchedules();
                if (!empty($schedules[0]['id'])) {
                    $schedules[0]['batch_mode'] = 1;
                    $schedules[0]['batch_limit'] = $newLimit;
                    $schedule = $this->scheduleFactory->create()->load($schedules[0]['id']);
                    $schedule->setData($schedules[0]);
                    $schedule->save();
                }

                // Persist batch state, will be saved in the queue messages
                $this->batch->setData(
                    ['offset' => $this->currentIteration,
                    'limit' => $newLimit,
                    'enabled' => true]
                );
            }

            $this->updateBatchQueue();

            // Exit the iterator but don't set the feed as failed
            throw new FeedException(
                new \Magento\Framework\Phrase('EARLY END / PHP Limits reached')
            );
        }

        unset($product, $adapter, $row);
    }

    /**
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    public function getCollection()
    {
        if (is_null($this->collection)) {
            if ($this->isTestMode()) {
                $this->collectionProvider->setTestSku($this->testSku);
            }
            $this->collection = $this->collectionProvider
                ->getCollection($this->feed);

            if ($this->batch->isEnabled()) {
                $limit = $this->batch->getLimit();
                $offset = $this->batch->getOffset();
                $this->collection->getSelect()->limit($limit, $offset);
            }
        }
        return $this->collection;
    }

    /**
     * @return int
     */
    public function getTotalItems()
    {
        if (!$this->hasData('total_items')) {
            if ($this->isTestMode()) {
                $this->collectionProvider->setTestSku($this->testSku);
            }
            $countCollection = $this->collectionProvider->getCollection($this->feed);
            $countCollection->getSelect()->reset(\Magento\Framework\DB\Select::GROUP);
            $this->setData('total_items', $countCollection->getSize());
        }
        return $this->getData('total_items');
    }

    /**
     * @param  \MageOS\ShoppingFeed\Model\Product\Adapter\AdapterInterface|\MageOS\ShoppingFeed\Model\Product\Adapter\AdapterAbstract $productAdapter
     * @return $this
     */
    protected function addProductToFeed($productAdapter)
    {
        $rows = [];
        if ($this->isTestMode()) {
            $productAdapter->setTestMode();
        }

        if (!$productAdapter->isSkipped()) {
            $rows = $productAdapter->map();
        }

        foreach ($rows as $row) {
            $requiresItemGroupId = in_array($this->feed->getData('type'), ['pinterest_catalog', 'openai_google_compatible'], true)
                && ($productAdapter instanceof \MageOS\ShoppingFeed\Model\Product\Adapter\Type\Configurable
                    || $productAdapter->getParentAdapter() instanceof \MageOS\ShoppingFeed\Model\Product\Adapter\Type\Configurable);
            $this->writeFeed($row, true, $requiresItemGroupId);
        }
        return $this;
    }

    /**
     * @param  $fields
     * @param  bool|true $addNewLine
     * @param  bool $requiresItemGroupId
     * @return Generator
     */
    protected function writeFeed($fields, $addNewLine = true, bool $requiresItemGroupId = false)
    {
        if ($addNewLine && $this->feed->getData('type') === 'google_shopping') {
            $requiresDate = in_array($fields['availability'] ?? '', ['backorder', 'preorder'], true);
            $date = $this->normalizeAvailabilityDate($fields['availability_date'] ?? '');
            if ($requiresDate && $date === null) {
                $this->updateCountSkip();
                $this->getLogger()->warning(sprintf(
                    'Skipped Google Shopping row %s: %s requires a valid future availability_date within one year. Map the expected shipping date in Columns Map.',
                    $fields['id'] ?? '(no id)',
                    $fields['availability']
                ));
                return $this;
            }
            $fields['availability_date'] = $date ?? '';
        }
        $isGoogleFeed = in_array(
            $this->feed->getData('type'),
            ['google_shopping', 'google_local_inventory'],
            true
        );
        $isMetaFeed = $this->feed->getData('type') === 'meta_catalog';
        $isMicrosoftFeed = $this->feed->getData('type') === 'microsoft_merchant_center';
        $isTikTokFeed = $this->feed->getData('type') === 'tiktok_catalog';
        $isPinterestFeed = $this->feed->getData('type') === 'pinterest_catalog';
        $isOpenAiFeed = $this->feed->getData('type') === 'openai_google_compatible';
        if ($addNewLine && $isOpenAiFeed && is_bool($fields['identifier_exists'] ?? null)) {
            $fields['identifier_exists'] = $fields['identifier_exists'] ? 'true' : 'false';
        }
        if ($addNewLine && ($isMetaFeed || $isMicrosoftFeed || $isTikTokFeed || $isPinterestFeed || $isOpenAiFeed)) {
            [$validator, $destination] = match (true) {
                $isOpenAiFeed => [$this->openAiGoogleCompatibleValidator, 'OpenAI Google-compatible'],
                $isPinterestFeed => [$this->pinterestCatalogValidator, 'Pinterest Catalog'],
                $isTikTokFeed => [$this->tikTokCatalogValidator, 'TikTok Catalog'],
                $isMicrosoftFeed => [$this->microsoftMerchantCenterValidator, 'Microsoft Merchant Center'],
                default => [$this->metaCatalogValidator, 'Meta Catalog'],
            };
            $validation = ($isPinterestFeed || $isOpenAiFeed)
                ? $validator->validate($fields, $requiresItemGroupId)
                : $validator->validate($fields);
            $identifier = $fields[$isTikTokFeed ? 'sku_id' : 'id'] ?? null;
            $id = is_scalar($identifier) ? (string)$identifier : '(no id)';
            if ($validation['errors']) {
                $this->updateCountSkip();
                $this->getLogger()->warning(sprintf(
                    'Skipped %s row %s: %s. Review Columns Map.',
                    $destination,
                    $id,
                    implode('; ', $validation['errors'])
                ));
                return $this;
            }
            foreach ($validation['warnings'] as $warning) {
                $this->getLogger()->warning(sprintf('%s row %s: %s.', $destination, $id, $warning));
            }
        }

        if ($addNewLine
            && ($isGoogleFeed || $isMetaFeed || $isMicrosoftFeed || $isTikTokFeed || $isPinterestFeed)
            && !empty($fields['sale_price'])
            && isset($fields['price'])
            && is_scalar($fields['sale_price'])
            && is_scalar($fields['price'])
            && (float)$fields['sale_price'] >= (float)$fields['price']
        ) {
            $fields['sale_price'] = '';
            if (isset($fields['sale_price_effective_date'])) {
                $fields['sale_price_effective_date'] = '';
            }
        }

        $params = $this->getWriteFeedParams();
        /**
         * @var $defaultValue
         * @var $delimiter
         * @var $encloseCell
         * @var $encloseEscape
         */
        extract($params);
        $row = [];

        foreach ($this->getOutputColumns($isGoogleFeed || $isMicrosoftFeed) as $arr) {
            $column = $arr['column'];
            $values = isset($fields[$column]) ? $fields[$column] : '';
            if (!is_array($values)) {
                $values = [$values];
            }

            foreach ($values as $value) {
                if (is_null($value) || $value == "") {
                    $value = $defaultValue;
                }
                if (!$this->isTestMode()) {
                    if ($encloseCell !== '') {
                        $value = str_replace($encloseCell, $encloseEscape . $encloseCell, $value);
                        $value = sprintf('%s%s%s', $encloseCell, $value, $encloseCell);
                    }
                    $row[] = $value;
                } else {
                    $row[] = ['label' => $column, 'value' => $value];
                }
            }
        }

        if (!$this->isTestMode()) {
            $this->fileDriver->fileWrite($this->getTemporaryHandle(), ($addNewLine ? PHP_EOL : '') . implode($delimiter, $row));
        } else {
            $this->testOutput[] = $row;
        }
        if ($addNewLine) {
            $this->countProductsExported++;
        }

        return $this;
    }

    /** Normalize mapped ISO dates or Magento datetime attributes (UTC), without guessing dates. */
    private function normalizeAvailabilityDate($value): ?string
    {
        if (!is_string($value)
            || !preg_match('/^\d{4}-\d{2}-\d{2}(?:[T ]\d{2}:\d{2}(?::\d{2})?(?:Z|[+-]\d{2}:?\d{2})?)?$/D', $value)
        ) {
            return null;
        }
        try {
            $date = new \DateTimeImmutable($value, new \DateTimeZone('UTC'));
            $errors = \DateTimeImmutable::getLastErrors();
            $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
            if (($errors !== false && ($errors['warning_count'] || $errors['error_count']))
                || $date <= $now || $date > $now->modify('+1 year')
            ) {
                return null;
            }
            return $date->format('c');
        } catch (\Exception $exception) {
            return null;
        }
    }

    private function getOutputColumns(bool $putIdLast): array
    {
        $columns = $this->feed->getColumnsMap();
        if (!$putIdLast) {
            return $columns;
        }

        foreach ($columns as $key => $column) {
            if ($column['column'] === 'id') {
                unset($columns[$key]);
                $columns[] = $column;
                break;
            }
        }

        return $columns;
    }

    /**
     * @return Generator
     */
    protected function closeTemporaryHandle()
    {
        if ($this->hasData('temporary_handle')) {
            $this->fileDriver->fileClose($this->getData('temporary_handle'));
            $this->unsetData('temporary_handle');
        }

        return $this;
    }

    /**
     * Only transfer data from temporary feed file if in
     * batch mode and this is the last batch, or if not in batch mode.
     *
     * @return $this
     */
    protected function copyDataFromTemporaryFeedFile()
    {
        if (!$this->isTestMode()) {
            if ($this->batch->isEnabled()) {
                // if this was the last batch
                if ($this->getTotalItems() <= $this->currentIteration) {
                    $this->moveFeedFile();
                    $this->processUploads();
                } else {
                    $this->updateBatchQueue();
                }
            } else {
                $this->moveFeedFile();
                $this->processUploads();
            }
        }

        return $this;
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    protected function getTemporaryHandle()
    {
        if (!$this->hasData('temporary_handle') || $this->getData('temporary_handle') === null) {
            $mode = "a";
            if (!$this->batch->isEnabled() || ($this->batch->isEnabled() && $this->batch->isNew())) {
                $mode = "w";
            }
            $handle = $this->fileDriver->fileOpen($this->getFeedFile() . '.tmp', $mode);

            // Microsoft TSV uses plain UTF-8 so the first header has no BOM prefix.
            if ($mode === "w" && $this->feed->getData('type') !== 'microsoft_merchant_center') {
                $this->fileDriver->fileWrite($handle, self::UTF8_BOM);
            }

            $this->setData('temporary_handle', $handle);
        }



        return $this->getData('temporary_handle');
    }

    /**
     * Moves the feed file to it's final location after being generated in a temporary location.
     * Removes queue since we are done with generation
     *
     * @return Generator
     */
    protected function moveFeedFile()
    {
        $this->fileDriver->rename($this->getFeedFile() . '.tmp', $this->getFeedFile());
        $this->removeQueue();
        $this->getLogger()->debug(sprintf('Moved %s to %s, queue removed', $this->getFeedFile() . '.tmp', $this->getFeedFile()));
        $this->updateStatus(\MageOS\ShoppingFeed\Model\Feed\Source\Status::STATUS_COMPLETED);

        return $this;
    }

    /**
     * Uploads feed to all configured locations.
     */
    protected function processUploads()
    {
        if ($this->isTestMode()) {
            return $this;
        }

        $uploadCollection = $this->feed->getUploadCollection();

        if (!$this->fileDriver->isExists($this->getFeedFile())) {
            return $this;
        }

        $gzipFile = null;
        try {
            foreach ($uploadCollection as $upload) {
                try {
                    $uploader = $this->uploaderFactory->create($upload);
                    $file = $this->getFeedFile();
                    if ((bool) $upload->getGzip()) {
                        $gzipFile = $gzipFile ?: $this->gzipCompressor->compress($file);
                        $file = $gzipFile;
                    }
                    $result = $uploader->upload($file);

                    $this->eventManager->dispatch(
                        'mageos_shopping_feed_upload_after', [
                        'generator' => $this,
                        'uploader' => $uploader,
                        'upload' => $upload,
                        'result' => $result
                        ]
                    );
                    if ($result) {
                        $this->getLogger()->info(sprintf("File %s uploaded to %s:%s", $file, $upload->getHost(), $upload->getPath()));
                    } else {
                        $this->getLogger()->warning(sprintf('Failed to upload %s', $file));
                    }
                } catch (\Throwable $e) {
                    $this->getLogger()->warning(sprintf('Problem with the upload: %s', $e->getMessage()));
                }
            }
        } finally {
            if ($gzipFile !== null) {
                $this->gzipCompressor->remove($gzipFile);
            }
        }

        return $this;
    }

    /**
     * @return array
     */
    protected function getHeader()
    {
        $header = [];
        foreach ($this->feed->getColumnsMap() as $map) {
            $column = $map['column'];
            if (isset($header[$column])) {
                if (is_array($header[$column])) {
                    $header[$column][] = $column;
                } else {
                    $header[$column] = [$header[$column], $column];
                }
            } else {
                $header[$column] = $column;
            }
        }
        return $header;
    }

    /**
     * @return array
     */
    protected function getWriteFeedParams()
    {
        $encloseCell = (string)$this->feed->getConfig('output_params_enclose_cell', '');
        $cellEncloseEscape = (string)$this->feed->getConfig('output_params_enclose_escape', '');
        $delimiter = $this->feed->getConfig('output_params_delimiter', "\t");
        $delimiter_other = $this->feed->getConfig('output_params_delimiter_other', "\t");
        $delimiter = $delimiter == 'other' ? "$delimiter_other" : ($delimiter == '\t' ? "\t" : "$delimiter");
        if ($this->feed->getData('type') === 'generic' && $delimiter === ',' && $encloseCell === '') {
            $encloseCell = '"';
        }
        $params = [
            'defaultValue' => $this->feed->getConfig('output_params_default_value', ''),
            'delimiter' => $delimiter,
            'encloseCell' => $encloseCell,
            'encloseEscape' => $encloseCell !== '' ? ($cellEncloseEscape !== '' ? $cellEncloseEscape : $encloseCell) : ''
        ];
        return $params;
    }

    /**
     * @return $this
     */
    protected function logProgress()
    {
        $time = $this->dateTime->date()->getTimestamp();

        if ($time - $this->getProgressTiming() > intval($this->scopeConfig->getValue(self::XML_LOG_PROGRESS))
            || $this->currentIteration <= 1
            || $this->currentIteration == $this->getTotalItems()
            || (!is_null($this->batch) && ($this->batch->isEnabled()
            && ($this->currentIteration % $this->batch->getLimit() == 0
            || $this->memory->isCloseToPhpLimit($this->getData('started_at'), false)            )))
        ) {
            // Log skipped products
            foreach($this->skippedData as $message => $ids) {
                $this->logger->warning(
                    sprintf("Skipped product(s) [%s] - %s", implode(', ', $ids), $message)
                );
            }
            $this->skippedData = [];

            // Log no. of products
            $percent = sprintf('%d', round($this->currentIteration / $this->getTotalItems() * 100));
            if (!$this->isTestMode()) {
                $origData = $this->feed->getMessages();
                $this->feed->saveMessages(
                    [
                    'date' => $this->dateTime->formatDateTime($this->dateTime->date()),
                    'progress' => $percent,
                    'added' => $this->currentIteration,
                    'exported' => $this->getCountProductsExported(),
                    'skipped' => $this->getCountProductsSkipped(),
                    'file' => $this->getFeedFile(false),
                    'store_id' => $this->feed->getStoreId()
                    ]
                );
            }

            $this->getLogger()->info(sprintf("Processed %s of %s (%s%%)", $this->currentIteration, $this->getTotalItems(), $percent));
            $this->setProgressTiming($time);
        }
        return $this;
    }

    /**
     * Could take negative value to decrease count
     *
     * @param  $val
     * @return Generator
     */
    public function updateCountSkip($val = 1)
    {
        $this->countProductsSkipped = $this->countProductsSkipped + $val;
        return $this;
    }

    public function addSkippedProduct($reason, $productId)
    {
        if (array_key_exists($reason, $this->skippedData)) {
            array_push($this->skippedData[$reason], $productId);
        } else {
            $this->skippedData[$reason] = [$productId];
        }
    }

    /**
     * @return int
     */
    public function getCountProductsExported()
    {
        return $this->countProductsExported;
    }

    /**
     * @return int
     */
    public function getCountProductsSkipped()
    {
        return $this->countProductsSkipped;
    }

    public function isTestMode()
    {
        return !is_null($this->testSku);
    }

    public function setTestSku($testSku)
    {
        $this->testSku = $testSku;
        return $this;
    }

    public function getTestOutput()
    {
        return $this->testOutput;
    }

    /**
     * Sets the logger handler if it doesn't exists yet
     *
     * @return Logger
     */
    public function getLogger()
    {
        if (!$this->hasData('feed_log_file') && !$this->isTestMode()) {
            $feedLogFile = '/' . $this->outputPath->getLogFile($this->feed, false);
            $this->logger->addHandler($feedLogFile, $this->scopeConfig->getValue(self::XML_LOG_LEVEL));
            $this->setData('feed_log_file', $feedLogFile);
        }
        return $this->logger;
    }

    /**
     * Sets the feed file path and returns the data
     *
     * @return string
     */
    public function getFeedFile($absolute = true)
    {
        if (!$this->hasData('feed_file')) {
            $this->setData('feed_file', $this->outputPath->getFeedFile($this->feed));
        }
        return $absolute
            ? $this->getData('feed_file')
            : $this->outputPath->getFeedFile($this->feed, false);
    }

    /**
     * Update queue batch data for next run
     *
     * @return bool
     */
    public function updateBatchQueue()
    {
        // Flush output before making the next offset available to another worker.
        $this->closeTemporaryHandle();
        $this->batch->setOffset($this->currentIteration);

        if (!is_null($this->queue) && $this->queue->getId()) {
            $this->queue->setBatch($this->batch);
            $this->queue->setData('is_read', 0);
            $this->queue->save();
            $this->batch = null;
            return true;
        }
        return false;
    }

    public function updateStatus($status)
    {
        if (!$this->isTestMode()) {
            $this->feed->saveStatus($status);
        }
    }

    protected function removeQueue()
    {
        if (!is_null($this->queue)) {
            $this->queue->delete();
            // We unset this so queue is not recreated
            $this->queue = null;
            $this->batch = null;
            return true;
        }
        return false;
    }

    /**
     * Close output without committing progress from an interrupted batch.
     */
    public function __destruct()
    {
        $this->closeTemporaryHandle();
    }
}
