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


namespace MageOS\ShoppingFeed\Model;

use Magento\Framework\Model\AbstractModel;
use MageOS\ShoppingFeed\Model\ResourceModel\Feed\Schedule\CollectionFactory as ScheduleFactory;
use MageOS\ShoppingFeed\Model\ResourceModel\Feed\Upload\CollectionFactory as UploadCollectionFactory;
use MageOS\ShoppingFeed\Model\ResourceModel\Feed\Config\CollectionFactory as ConfigCollectionFactory;
use MageOS\ShoppingFeed\Model\ResourceModel\Feed\ConfigFactory;

/**
 * Class Feed
 *
 * @package MageOS\ShoppingFeed\Model
 *
 * @method int getStoreId()
 */
class Feed extends AbstractModel
{
    private const GOOGLE_VARIANT_COLUMNS = [
        'item_group_title',
        'variant_option',
        'color',
        'size',
        'material',
        'pattern',
        'gender',
        'age_group',
    ];

    /**
     * Event prefix for observer
     *
     * @var string
     */
    protected $_eventPrefix = 'mageos_shopping_feed_feed';

    /**
     * @var ScheduleFactory
     */
    protected $scheduleCollectionFactory;

    /**
     * @var UploadCollectionFactory
     */
    protected $uploadCollectionFactory;

    /**
     * @var ConfigCollectionFactory
     */
    protected $configCollectionFactory;

    /**
     * @var \MageOS\ShoppingFeed\Model\Feed\ConfigFactory
     */
    protected $configFactory;

    /**
     * @var \MageOS\ShoppingFeed\Model\Feed\ScheduleFactory
     */
    protected $scheduleFactory;

    /**
     * @var \MageOS\ShoppingFeed\Model\Feed\UploadFactory
     */
    protected $uploadFactory;

    /**
     * @var \MageOS\ShoppingFeed\Model\FeedTypes\Config
     */
    protected $feedTypesConfig;

    /**
     * Columns map cache
     *
     * @var array
     */
    protected $columnsMap;

    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * Store object
     *
     * @var null|\Magento\Store\Model\Store
     */
    protected $storeObject = null;

    /**
     * @var \MageOS\ShoppingFeed\Model\Generator\Cache\ShippingProvider
     */
    protected $shippingProvider;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $localeDate;

    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     */
    protected $serializer;

    /**
     * @var \MageOS\ShoppingFeed\Model\Feed\OutputPath
     */
    protected $outputPath;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Feed constructor.
     *
     * @param \Magento\Framework\Model\Context                             $context
     * @param \Magento\Framework\Registry                                  $registry
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface            $priceCurrency
     * @param \Magento\Store\Model\Store                                   $storeObject
     * @param ScheduleFactory                                              $scheduleCollectionFactory
     * @param UploadCollectionFactory                                      $uploadCollectionFactory
     * @param ConfigCollectionFactory                                      $configCollectionFactory
     * @param Feed\ConfigFactory                                           $configFactory
     * @param Feed\ScheduleFactory                                         $scheduleFactory
     * @param Feed\UploadFactory                                           $uploadFactory
     * @param FeedTypes\Config                                             $feedTypesConfig
     * @param Generator\Cache\ShippingProvider                             $shippingProvider
     * @param \Magento\Framework\App\Config\ScopeConfigInterface           $scopeConfig
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface         $localeDate
     * @param \Magento\Framework\Serialize\SerializerInterface           $serializer
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null           $resourceCollection
     * @param \Magento\Store\Model\StoreManagerInterface                   $storeManager
     * @param string                                                       $feedType
     * @param array                                                        $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Store\Model\Store $storeObject,
        ScheduleFactory $scheduleCollectionFactory,
        UploadCollectionFactory $uploadCollectionFactory,
        ConfigCollectionFactory $configCollectionFactory,
        \MageOS\ShoppingFeed\Model\Feed\ConfigFactory $configFactory,
        \MageOS\ShoppingFeed\Model\Feed\ScheduleFactory $scheduleFactory,
        \MageOS\ShoppingFeed\Model\Feed\UploadFactory $uploadFactory,
        \MageOS\ShoppingFeed\Model\FeedTypes\Config $feedTypesConfig,
        \MageOS\ShoppingFeed\Model\Generator\Cache\ShippingProvider $shippingProvider,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \MageOS\ShoppingFeed\Model\Feed\OutputPath $outputPath,
        ?\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        ?\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        $feedType = '',
        array $data = []
    ) {
        $this->scheduleCollectionFactory = $scheduleCollectionFactory;
        $this->uploadCollectionFactory = $uploadCollectionFactory;
        $this->configCollectionFactory = $configCollectionFactory;
        $this->configFactory = $configFactory;
        $this->scheduleFactory = $scheduleFactory;
        $this->uploadFactory = $uploadFactory;
        $this->feedTypesConfig = $feedTypesConfig;
        $this->storeObject = $storeObject;
        $this->priceCurrency = $priceCurrency;
        $this->shippingProvider = $shippingProvider;
        $this->scopeConfig = $scopeConfig;
        $this->localeDate = $localeDate;
        $this->serializer = $serializer;
        $this->storeManager = $storeManager;
        $this->outputPath = $outputPath;

        parent::__construct($context, $registry, $resource, $resourceCollection, $data);

        if (!empty($feedType)) {
            $this->setType($feedType);
        }
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('MageOS\ShoppingFeed\Model\ResourceModel\Feed');
    }

    /**
     * Returns columns map in asc order.
     * Skips columns with attributes that doesn't exist.
     * Caches eav attributes model used.
     *
     *  [column] =>
     *            [column]
     *            [attribute code or directive code]
     *            [default_value]
     *            [order]
     *
     * @return array
     */
    public function getColumnsMap()
    {
        if (!is_null($this->columnsMap) && is_array($this->columnsMap)) {
            return $this->columnsMap;
        }

        $columns = $this->getConfig()->getData('columns_product_columns');
        if ($this->getData('type') === 'google_shopping') {
            $columns = $this->upgradeGoogleColumns($columns);
        }

        $this->columnsMap = [];
        $order = [];

        $counter = 0;
        foreach ($columns as $arr) {
            $this->columnsMap[] = $arr;
            $order[sprintf('%s_%s', $arr['column'], $counter)] = $arr['order'];
            $counter++;
        }
        array_multisort($order, $this->columnsMap);

        return $this->columnsMap;
    }

    private function upgradeGoogleColumns(array $columns): array
    {
        $columnNames = [];
        foreach ($columns as &$column) {
            if ($column['column'] === 'promotions_id') {
                $column['column'] = 'promotion_id';
            }
            $columnNames[$column['column']] = true;
        }
        unset($column);

        if (!isset($columnNames['item_group_id'])) {
            return $columns;
        }

        $feedConfig = $this->feedTypesConfig->getFeed('google_shopping');
        $defaultColumns = $feedConfig['default_feed_config']['columns']['product_columns'];
        foreach (self::GOOGLE_VARIANT_COLUMNS as $columnName) {
            if (!isset($columnNames[$columnName], $defaultColumns[$columnName])) {
                $columns[] = $defaultColumns[$columnName];
            }
        }

        return $columns;
    }

    public function setColumnsMap($columnsMap = [])
    {
        $this->columnsMap = $columnsMap;
        return $this;
    }

    public function setType($type)
    {
        $this->setData('type', $type);
        if (!$this->hasData('config')) {
            $this->setData('config', new \Magento\Framework\DataObject());
            $this->setDefaultConfig($type);
        }
        return $this;
    }

    /**
     * Serialize messages before save if needed
     *
     * @return $this
     */
    public function beforeSave()
    {
        $messages = $this->getData('messages');
        if ($messages === null || $messages === false || $messages === '') {
            $messages = [];
        }
        if (is_array($messages)) {
            $this->setData('messages', $this->serializer->serialize($messages));
        }

        return parent::beforeSave();
    }

    /**
     * Use resource to save the feed so that config is not saved.
     *
     * @param  $value
     * @return $this
     */
    public function saveMessages($value)
    {
        $skipConfigSave = $this->getData('no_after_save');
        $this->setData('no_after_save', true);
        try {
            $messages = $this->getMessages();
            if (!$messages) {
                $messages = [];
            }
            $data = array_merge($messages, $value);
            $this->setData('messages', $data);

            $this->beforeSave();
            $this->getResource()->save($this);
        } finally {
            $this->setData('no_after_save', $skipConfigSave);
        }
        return $this;
    }

    /**
     * Use resource to save the feed so that config is not saved.
     *
     * @param  $value
     * @return $this
     */
    public function saveStatus($status)
    {
        $skipConfigSave = $this->getData('no_after_save');
        $this->setData('no_after_save', true);
        try {
            $this->setStatus($status);
            $this->beforeSave();
            $this->getResource()->unsUpdatedAt()->save($this);
        } finally {
            $this->getResource()->unsUpdatedAt(false);
            $this->setData('no_after_save', $skipConfigSave);
        }
        return $this;
    }

    /**
     * Save object data
     *
     * @return $this
     * @throws \Exception
     */
    public function save()
    {
        $this->validate();

        return parent::save();
    }

    /**
     * Save all config data
     *
     * @return $this
     */
    public function afterSave()
    {
        if ($this->getData('no_after_save')) {
            return parent::afterSave();
        }

        $config = $this->getConfig();
        if ($config) {
            $config = $config->toArray();
        } else {
            $config = [];
        }
        if (count($config)) {
            /**
 * @var |MageOS\ShoppingFeed\Model\ResourceModel\Feed\Config\Collection $configCollection
*/
            $configCollection = $this->getConfigCollection();
            foreach ($configCollection as $configObject) {
                /**
 * @var \MageOS\ShoppingFeed\Model\Feed\Config $configObject
*/
                $path = $configObject->getData('path');
                $value = $configObject->getData('value');
                if (array_key_exists($path, $config)) {
                    if ($config[$path] != $value) {
                        $configObject->setData('value', $config[$path]);
                        $configObject->save();
                    }
                    unset($config[$path]);
                }
            }

            if (count($config) > 0) {
                foreach ($config as $path => $value) {
                    $configObject = $this->configFactory->create();
                    $configObject->setData('path', $path);
                    $configObject->setData('value', $value);
                    $configObject->setData('feed_id', $this->getId());
                    $configObject->save();
                }
            }
            // Clear shipping cache on feed save
            if ($this->getConfig('shipping_cache_enabled')
                && !$this->scopeConfig->getValue(\Magento\Directory\Model\Observer::IMPORT_ENABLE)
            ) {
                $this->shippingProvider->clearCache($this);
            }
        }

        $this->saveSchedules();

        $this->saveUploads();

        return parent::afterSave();
    }

    /**
     * Save schedules after feed save
     *
     * @return void
     */
    public function saveSchedules(): void
    {
        $schedules = $this->getData('schedules');

        if (is_array($schedules) && count($schedules)) {
            // Force elements with ID set to save first. Prevent order changed in
            // MageOS\ShoppingFeed\Block\Adminhtml\Feed\Edit\Tab\Schedule::sortValuesCallback
            usort($schedules, [$this, 'sortByIdCallback']);

            foreach ($schedules as $schedule) {
                $scheduleObject = $this->scheduleFactory->create();

                // test if this should be a new schedule entry (for example when cloning a feed)
                if (!empty($schedule['id'])) {
                    $scheduleObject->load($schedule['id']);
                    if (!$scheduleObject->getId()
                        || (int)$scheduleObject->getData('feed_id') !== (int)$this->getId()
                    ) {
                        throw new \Magento\Framework\Exception\LocalizedException(
                            __('The schedule does not belong to this feed.')
                        );
                    }
                }

                if (isset($schedule['delete']) && $schedule['delete']) {
                    if ($scheduleObject->getId()) {
                        $scheduleObject->delete();
                    }
                    continue;
                }

                $startAt = $schedule['start_at'] ?? 1;
                if ($scheduleObject->getId()
                    && (int)$scheduleObject->getData('start_at') !== (int)$startAt
                ) {
                    $scheduleObject->setData(
                        'processed_at',
                        $this->localeDate->date()->modify('-1 day')
                            ->format(\Magento\Framework\DB\Adapter\Pdo\Mysql::DATETIME_FORMAT)
                    );
                }
                $scheduleObject->setData('start_at', $startAt);

                if (array_key_exists('batch_mode', $schedule)) {
                    $scheduleObject->setData('batch_mode', $schedule['batch_mode']);
                }
                if (array_key_exists('batch_limit', $schedule)) {
                    $scheduleObject->setData('batch_limit', max(0, (int)$schedule['batch_limit']));
                }

                if (!$scheduleObject->hasData('processed_at')) {
                    $scheduleObject->setData('processed_at', $this->localeDate->date()->format(\Magento\Framework\DB\Adapter\Pdo\Mysql::DATETIME_FORMAT));
                }

                if (!$scheduleObject->getId()) {
                    $scheduleObject->setData('feed_id', $this->getId());
                }

                try {
                    $scheduleObject->save();
                } catch (\Exception $e) {
                    if ($e->getCode() === \Magento\UrlRewrite\Model\Storage\DbStorage::ERROR_CODE_DUPLICATE_ENTRY
                        && preg_match('#SQLSTATE\[23000\]: [^:]+: 1062[^\d]#', $e->getMessage())
                    ) {
                        throw new \Magento\Framework\Exception\LocalizedException(
                            __('Only one schedule can be added for each Start At hour.')
                        );
                    }
                    throw $e;
                }
            }
        }
    }

    /**
     * Sort array by id key descendent values
     *
     * @param                                   array $a
     * @param                                   array $b
     * @return                                  int
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function sortByIdCallback($a, $b)
    {
        return (int)$b['id'] - (int)$a['id'];
    }

    /**
     * Save uploads after feed save
     *
     * @return void
     */
    public function saveUploads()
    {
        $uploads = $this->getData('uploads');

        if (!empty($uploads)) {
            foreach ($uploads as $upload) {
                $uploadObject = $this->uploadFactory->create();

                if (!empty($upload['id'])) {
                    $uploadObject->load($upload['id']);
                    if (!$uploadObject->getId()
                        || (int)$uploadObject->getData('feed_id') !== (int)$this->getId()
                    ) {
                        throw new \Magento\Framework\Exception\LocalizedException(
                            __('The upload destination does not belong to this feed.')
                        );
                    }
                }

                if ($uploadObject->getId() && !empty($upload['delete'])) {
                    $uploadObject->delete();
                    continue;
                }

                $uploadObject->setData('mode', $upload['mode']);
                $uploadObject->setData('host', $upload['host']);
                $uploadObject->setData('port', $upload['port']);
                $uploadObject->setData('username', $upload['username']);
                $uploadObject->setData('password', $upload['password']);
                $uploadObject->setData('gzip', $upload['gzip']);
                $uploadObject->setData('path', $upload['path']);

                if (!$uploadObject->getId()) {
                    $uploadObject->setData('feed_id', $this->getId());
                }

                $uploadObject->save();
            }
        }
    }

    /**
     * Method which can be used to add custom validation.
     *
     * In case of any error \Magento\Framework\Exception\LocalizedException
     * should be thrown.
     *
     * @return $this
     */
    protected function validate()
    {
        $this->outputPath->validateFeed($this);
        foreach ((array)$this->getConfig('columns_product_columns', []) as $row) {
            if (!is_array($row) || !isset($row['column']) || !is_scalar($row['column'])) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Invalid column name in Columns Map.'));
            }
            if (preg_match('/[\x00-\x1F\x7F]/', (string)$row['column'])) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Column names must not contain control characters.')
                );
            }
        }
        return $this;
    }

    /**
     * Unserialize messages after load if needed
     * Loads all saved config into getConfig && adds default ones if any missing
     *
     * @return $this
     */
    protected function _afterLoad()
    {
        if (!is_array($this->getData('messages'))) {
            $messages = $this->getData('messages');
            $this->setData('messages', empty($messages) ? false : $this->serializer->unserialize($messages));
        }

        if (!$this->hasData('config') || !($this->getData('config') instanceof \Magento\Framework\DataObject)) {
            $this->setData('config', new \Magento\Framework\DataObject());
        }

        //Set all the configs into $config dataObject
        $configCollection = $this->getConfigCollection();
        foreach ($configCollection as $config) {
            /**
 * @var \MageOS\ShoppingFeed\Model\Feed\Config $config
*/
            $path = $config->getData('path');
            if (!empty($path)) {
                $this->getConfig()->setData($path, $config->getData('value'));
            }
        }

        $this->setDefaultConfig($this->getType());
        return parent::_afterLoad();
    }

    /**
     * Set default feed config into getConfig object
     * Its aware of already existing data and it will fill in only missing ones!
     * It also adds the feed output parameters (delimiter, default cell value, ...)
     *
     * @param  $type
     * @return $this
     */
    protected function setDefaultConfig($type)
    {
        $defaultFeedConfig = $this->feedTypesConfig->getFeed($type);
        if (is_array($defaultFeedConfig) && count($defaultFeedConfig)) {
            foreach ($defaultFeedConfig['default_feed_config'] as $group => $items) {
                foreach ($items as $key => $value) {
                    $path = sprintf('%s_%s', $group, $key);
                    if ($this->getConfig()->hasData($path)) {
                        continue;
                    }
                    $this->getConfig()->setData($path, $value);
                }
            }
        }
        return $this;
    }

    /**
     * Unserialize messages if needed
     *
     * @return $this
     */
    public function getMessages()
    {
        if (!is_array($this->getData('messages'))) {
            $messages = $this->getData('messages');
            $this->setData('messages', empty($messages) ? false : $this->serializer->unserialize($messages));
        }

        return $this->getData('messages');
    }

    /**
     * @return |MageOS\ShoppingFeed\Model\ResourceModel\Feed\Config\Collection
     */
    public function getConfigCollection()
    {
        return $this->configCollectionFactory->create()->setFeedFilter($this);
    }

    /**
     * Return schedule collection for a current feed.
     *
     * @return \MageOS\ShoppingFeed\Model\ResourceModel\Feed\Schedule\Collection
     */
    public function getScheduleCollection()
    {
        return $this->scheduleCollectionFactory->create()->setFeedFilter($this);
    }

    /**
     * Return upload collection for a current feed.
     *
     * @return \MageOS\ShoppingFeed\Model\ResourceModel\Feed\Upload\Collection
     */
    public function getUploadCollection()
    {
        return $this->uploadCollectionFactory->create()->setFeedFilter($this);
    }

    /**
     * Return feed schedules formatted in human readable way.
     *
     * @return array
     */
    public function getFormattedSchedules()
    {
        $schedules = [];
        $scheduleCollection = $this->getScheduleCollection();

        if (!$scheduleCollection->getSize()) {
            return [__('None')];
        }

        foreach ($scheduleCollection as $schedule) {
            $schedules[] = $schedule->getFormattedSchedule();
        }

        return $schedules;
    }

    /**
     * @param  $type
     * @return bool
     */
    public function isProductTypeEnabled($type)
    {
        return in_array($type, $this->getConfig('filters_product_types', []));
    }

    /**
     * @return bool
     */
    public function isTaxonomyAutocompleteEnabled()
    {
        return $this->getConfig('categories_taxonomy_autocomplete_enabled') == 1 ? true : false;
    }

    /**
     * @param null|string        $param
     * @param null|boolean|mixed $default
     *
     * @return \Magento\Framework\DataObject|mixed|null
     */
    public function getConfig($param = null, $default = null, $notFoundUseDefault = null)
    {
        $data = $this->getData('config', $param);
        if (is_null($data) || $data == $notFoundUseDefault) {
            $data = $default;
        }
        return $data;
    }

    /**
     * Get array containing schedules and cache them in feed object.
     *
     * @return array
     */
    public function getSchedules()
    {
        if ($this->hasData('schedules') && is_array($this->getData('schedules'))) {
            return $this->getData('schedules');
        }

        $schedules = [];

        $scheduleCollection = $this->getScheduleCollection();
        foreach ($scheduleCollection as $schedule) {
            $schedules[] = $schedule->getData();
        }

        $this->setData('schedules', $schedules);

        return $schedules;
    }

    /**
     * Get array containing Uploads and cache them in feed object.
     *
     * @return array
     */
    public function getUploads()
    {
        if ($this->hasData('uploads') && is_array($this->getData('uploads'))) {
            return $this->getData('uploads');
        }

        $uploads = [];

        $uploadCollection = $this->getUploadCollection();
        foreach ($uploadCollection as $upload) {
            $uploads[] = $upload->getData();
        }

        $this->setData('uploads', $uploads);

        return $uploads;
    }

    /**
     * Returns store instance
     *
     * @return \Magento\Store\Model\Store
     */
    public function getStore()
    {
        if ($this->storeObject->getStoreId() != $this->getFeedStoreId()) {
            $this->storeObject->load($this->getFeedStoreId());
            $currency = $this->priceCurrency->getCurrency($this->storeObject, $this->getConfig('general_currency'));
            $this->storeObject->setData('current_currency', $currency);
        }
        return $this->storeObject;
    }

    /**
     * @return int|mixed
     */
    public function getFeedStoreId()
    {
        $id = $this->getData('store_id');
        if (!$id) {
            $id = $this->storeManager->getDefaultStoreView()->getId();
        }
        return $id;
    }
}
