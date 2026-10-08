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

namespace MageOS\ShoppingFeed\Model\Product\Adapter;

use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use MageOS\ShoppingFeed\Model\Exception as FeedException;

/**
 * Class AdapterAbstract
 *
 * @package MageOS\ShoppingFeed\Model\Product\Adapter
 *
 * @method boolean hasParentAdapter()
 * @method $this   setParentAdapter($adapter)
 * @method $this   getParentAdapter()
 */
class AdapterAbstract extends \Magento\Framework\DataObject
{
    const DEFAULT_CHILDREN_COUNT = 1;

    /**
     * Store object
     *
     * @var null|\Magento\Store\Model\Store
     */
    protected $storeObject = null;

    /**
     * Feed object
     *
     * @var null|\MageOS\ShoppingFeed\Model\Feed
     */
    protected $feed = null;

    /**
     * Product object
     *
     * @var null|\Magento\Catalog\Model\Product
     */
    protected $product;

    /**
     * @var \MageOS\ShoppingFeed\Model\FeedTypes\Config
     */
    protected $feedTypesConfig;

    /**
     * @var \MageOS\ShoppingFeed\Model\Product\Mapper\MapperFactory
     */
    protected $mapperFactory;

    /**
     * @var \MageOS\ShoppingFeed\Model\Product\Formatter\FormatterFactory
     */
    protected $formatterFactory;

    /**
     * @var \MageOS\ShoppingFeed\Model\Product\Helper
     */
    protected $helper;

    /**
     * @var \Magento\Weee\Helper\Data
     */
    protected $weeeHelper;

    /**
     * @var \Magento\Tax\Helper\Data
     */
    protected $taxHelper;

    /**
     * @var \MageOS\ShoppingFeed\Model\Product\Helper\Catalog
     */
    protected $catalogHelper;

    /**
     * @var \Magento\CatalogRule\Model\ResourceModel\Rule\Product\Price\CollectionFactory
     */
    protected $catalogRuleCollectionFactory;

    /**
     * @var \Magento\Catalog\Model\Product\Type\Price
     */
    protected $productTypePrice;


    protected $logger;

    /**
     * @var \MageOS\ShoppingFeed\Model\Product\Filter
     */
    protected $filter;

    /**
     * @var \Magento\Framework\Locale\Resolver
     */
    protected $localeResolver;

    /**
     * DateTime
     *
     * @var \Magento\Framework\Stdlib\DateTime\Timezone
     */
    protected $timezone;

    /**
     * @var boolean
     */
    protected $isTestMode = false;

    /**
     * DateTime
     *
     * @var \Magento\Framework\Stdlib\DateTime
     */
    protected $date;

    /**
     * @var \MageOS\ShoppingFeed\Model\Product\Processors\OptionFactory
     */
    protected $optionFactory;

    /**
     * @var \MageOS\ShoppingFeed\Model\Product\Option
     */
    protected $option;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var \MageOS\ShoppingFeed\Model\Generator\Cache
     */
    protected $cache;

    /**
     * @var \MageOS\ShoppingFeed\Model\Generator\ProcessFactory
     */
    protected $processFactory;

    /**
     * @var \MageOS\ShoppingFeed\Model\ResourceModel\Generator\Process\CollectionFactory
     */
    protected $processCollectionFactory;

    /**
     * @var \Magento\CatalogInventory\Model\StockState
     */
    protected $stockState;

    /**
     * @var AdapterFactory
     */
    protected $adapterFactory;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory
     */
    protected $categoryCollectionFactory;

    /**
     * @var MageOS\ShoppingFeed\Model\Inventory/Api
     */
    protected $sourceInventoryApi;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    protected $jsonSerializer;

    /**
     * @var array
     */
    protected $skippedData;

    /**
     * @var string
     */
    protected $eavLinkField;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $dbConnection;

    /**
     * AdapterAbstract constructor.
     *
     * @param  Filesystem                                                                       $filesystem
     * @param  \MageOS\ShoppingFeed\Model\Feed                                              $feed
     * @param  \Magento\Catalog\Model\Product                                                   $product
     * @param  \MageOS\ShoppingFeed\Model\FeedTypes\Config                                  $feedTypesConfig
     * @param  \MageOS\ShoppingFeed\Model\Product\Mapper\MapperFactory                      $mapperFactory
     * @param  \MageOS\ShoppingFeed\Model\Product\Helper                                    $helper
     * @param  \Magento\Weee\Helper\Data                                                        $weeeData
     * @param  \Magento\Tax\Helper\Data                                                         $taxData
     * @param  \MageOS\ShoppingFeed\Model\Product\Helper\Catalog                            $catalogHelper
     * @param  \Magento\CatalogRule\Model\ResourceModel\Rule\Product\Price\CollectionFactory    $catalogRuleCollectionFactory
     * @param  \Magento\Catalog\Model\Product\Type\Price                                        $productTypePrice
     * @param  \Magento\CatalogInventory\Model\StockState                                       $stockState
     * @param  \MageOS\ShoppingFeed\Model\Product\Filter                                    $filter
     * @param  \Magento\Framework\Locale\Resolver                                               $localeResolver
     * @param  \Magento\Framework\Stdlib\DateTime\Timezone                                      $timezone
     * @param  \Magento\Framework\Stdlib\DateTime                                               $date
     * @param  \MageOS\ShoppingFeed\Model\Product\Processors\OptionFactory                  $optionFactory
     * @param  \MageOS\ShoppingFeed\Model\Generator\ProcessFactory                          $processFactory
     * @param  \MageOS\ShoppingFeed\Model\ResourceModel\Generator\Process\CollectionFactory $processCollectionFactory
     * @param  \MageOS\ShoppingFeed\Model\Logger                                            $logger
     * @param  \MageOS\ShoppingFeed\Model\Generator\Cache                                   $cache
     * @param  AdapterFactory                                                                   $adapterFactory
     * @param  \MageOS\ShoppingFeed\Model\Product\Formatter\FormatterFactory                $formatterFactory
     * @param  \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory                  $categoryCollectionFactory
     * @param  \MageOS\ShoppingFeed\Model\Inventory\Api                                     $sourceInventoryApi
     * @param  \Magento\Framework\Serialize\SerializerInterface                                 $jsonSerializer
     * @param  array                                                                            $data
     * @throws FeedException
     */
    public function __construct(
        Filesystem $filesystem,
        \MageOS\ShoppingFeed\Model\Feed $feed,
        \Magento\Catalog\Model\Product $product,
        \MageOS\ShoppingFeed\Model\FeedTypes\Config $feedTypesConfig,
        \MageOS\ShoppingFeed\Model\Product\Mapper\MapperFactory $mapperFactory,
        \MageOS\ShoppingFeed\Model\Product\Helper $helper,
        \Magento\Weee\Helper\Data $weeeData,
        \Magento\Tax\Helper\Data $taxData,
        \MageOS\ShoppingFeed\Model\Product\Helper\Catalog $catalogHelper,
        \Magento\CatalogRule\Model\ResourceModel\Rule\Product\Price\CollectionFactory $catalogRuleCollectionFactory,
        \Magento\Catalog\Model\Product\Type\Price $productTypePrice,
        \Magento\CatalogInventory\Model\StockState $stockState,
        \MageOS\ShoppingFeed\Model\Product\Filter $filter,
        \Magento\Framework\Locale\Resolver $localeResolver,
        \Magento\Framework\Stdlib\DateTime\Timezone $timezone,
        \Magento\Framework\Stdlib\DateTime $date,
        \MageOS\ShoppingFeed\Model\Product\Processors\OptionFactory $optionFactory,
        \MageOS\ShoppingFeed\Model\Generator\ProcessFactory $processFactory,
        \MageOS\ShoppingFeed\Model\ResourceModel\Generator\Process\CollectionFactory $processCollectionFactory,
        \MageOS\ShoppingFeed\Model\Logger $logger,
        \MageOS\ShoppingFeed\Model\Generator\Cache $cache,
        \MageOS\ShoppingFeed\Model\Product\Adapter\AdapterFactory $adapterFactory,
        \MageOS\ShoppingFeed\Model\Product\Formatter\FormatterFactory $formatterFactory,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
        \MageOS\ShoppingFeed\Model\Inventory\Api $sourceInventoryApi,
        \Magento\Framework\Serialize\SerializerInterface $jsonSerializer,
        array $data = []
    ) {
        $this->setFeed($feed);
        $this->setProduct($product);
        $this->logger = $logger;
        $this->stockState = $stockState;
        $this->adapterFactory = $adapterFactory;
        $this->filesystem = $filesystem;
        $this->helper = $helper;
        $this->weeeHelper = $weeeData;
        $this->taxHelper = $taxData;
        $this->catalogHelper = $catalogHelper;
        $this->catalogRuleCollectionFactory = $catalogRuleCollectionFactory;
        $this->productTypePrice = $productTypePrice;
        $this->mapperFactory = $mapperFactory;
        $this->feedTypesConfig = $feedTypesConfig;
        $this->filter = $filter;
        $this->filter->setFeed($feed);
        $this->localeResolver = $localeResolver;
        $this->timezone = $timezone;
        $this->date = $date;
        $this->optionFactory = $optionFactory;
        $this->processFactory = $processFactory;
        $this->processCollectionFactory = $processCollectionFactory;
        $this->cache = $cache;
        $this->formatterFactory = $formatterFactory;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->sourceInventoryApi = $sourceInventoryApi;
        $this->jsonSerializer = $jsonSerializer;
        $this->skippedData = [];

        parent::__construct($data);

        $this->setAdapterData();
    }

    protected function setAdapterData()
    {
        $this->setData('store_currency_code', $this->getStore()->getCurrentCurrency()->getCode());
        $this->setData('images_url_prefix', $this->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA, false) . 'catalog/product');
        $this->setData('images_path_prefix', $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath());
    }

    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Gets value either from directive method or attribute method.
     *
     * @param  array $column
     * @return mixed
     */
    public function getMapValue(array $column = [])
    {
        $cacheKey = ['row', 'map', 'product', $this->getProduct()->getId(), 'column', $column['column'], 'attribute', $column['attribute']];
        if (array_key_exists('cache_key', $column)) {
            array_push($cacheKey, $column['cache_key']);
        }
        if ($this->cache->getCache($cacheKey, false) === false) {
            $feedType = $this->feed->getData('type');

            if ($this->feedTypesConfig->isAllowedDirective($feedType, $column['attribute'])
                && !isset($column['skip_directive'])
            ) {
                $directive = $this->feedTypesConfig->getDirective($feedType, $column['attribute']);
                $mapper = $this->mapperFactory->create($directive, $this);
                $value = $mapper->map($column);
                $mapper->popAdapter();
            } else {
                $attribute = $this->getMapAttribute($column);
                $value = $this->getAttributeValue($this->product, $attribute);
                $value = $this->getFilter()->cleanField($value, $column);
            }

            if ($value == '') {
                $value = $this->mapEmptyValues($column);
            }

            $this->cache->setCache($cacheKey, $value);
        } else {
            $value = $this->cache->getCache($cacheKey);
        }

        return $value;
    }

    /**
     * Pull price & special price with & without tax
     *
     * @return array
     */
    public function getPrices()
    {
        if ($this->hasData('price_array')) {
            return $this->getData('price_array');
        }

        /**
 * @var \Magento\Weee\Helper\Data $weeeHelper
*/
        $weeeHelper = $this->weeeHelper;
        /**
 * @var \Magento\Tax\Helper\Data $taxHelper
*/
        $taxHelper = $this->taxHelper;
        /**
 * @var \MageOS\ShoppingFeed\Model\Product\Helper\Catalog $catalogHelper
*/
        $catalogHelper = $this->catalogHelper;
        $helper = $this->helper;

        $store = $this->getStore();
        $algorithm = $taxHelper->getConfig()->getAlgorithm($store);

        /**
 * @var \Magento\Catalog\Model\Product $product
*/
        $product = $this->product;

        $qtyIncrements = $helper->getQuantityIcrements(
            ($this->hasParentAdapter() ? $this->getParentAdapter()->getProduct() : $product),
            $this->getFeed()
        );

        $weeeExcludingTax = $weeeHelper->getAmountExclTax($product);
        $weeeIncludingTax = $weeeExcludingTax;
        if ($weeeHelper->isTaxable()) {
            $amount = 0;
            $attributes = $weeeHelper->getProductWeeeAttributesForRenderer($product, null, null, null, true);
            foreach ($attributes as $attribute) {
                $amount += $attribute->getAmount();
            }
            $weeeIncludingTax = $amount;
        }

        $prices = $this->getProductPrices($product);

        if ($algorithm !== \Magento\Tax\Model\Calculation::CALC_UNIT_BASE && $qtyIncrements > 1.0) {
            // We need to multiply base before calculating tax for whole ((itemPrice * qty) + vat = total)
            $prices['p_excl_tax'] *= $qtyIncrements;
            $prices['p_incl_tax'] = $catalogHelper->getTaxPrice($product, $prices['p_excl_tax'], true);

            $prices['sp_excl_tax'] *= $qtyIncrements;
            $prices['sp_incl_tax'] = $catalogHelper->getTaxPrice($product, $prices['sp_excl_tax'], true);
        } elseif ($qtyIncrements > 1.0) {
            // We just need to multiply incl_tax/excl_tax prices
            foreach ($prices as $code => $price) {
                $prices[$code] = $price * $qtyIncrements;
            }
        }

        foreach ($prices as $code => $price) {
            if (strpos($code, '_incl_') !== false) {
                $price = $price + $weeeIncludingTax;
            } else {
                $price = $price + $weeeExcludingTax;
            }
            $prices[$code] = $price;
        }

        $this->setData('price_array', $prices);
        return $this->getData('price_array');
    }

    /**
     * @param  bool|true $processRules
     * @param  null      $product
     * @return bool
     */
    public function hasSpecialPrice($processRules = true, $product = null)
    {
        $has = false;
        if (is_null($product)) {
            $product = $this->product;
        }

        if ($processRules && $this->hasPriceByCatalogRules()) {
            $has = true;
        } elseif ($this->helper->hasMsrp($product)) {
            $has = true;
        } else {
            $specialPrice = $product->getSpecialPrice();
            $locale = $this->localeResolver->getLocale();
            if ($specialPrice > 0) {
                $dates = $this->getSpecialPriceEffectiveDates($product, false);
                $cDate = $this->timezone->date(null, $locale);
                /**
                 * @var \DateTime $start
                 * @var \DateTime $end
                 */
                extract($dates);

                if ($start <= $cDate && $end >= $cDate && $specialPrice < $product->getPrice()) {
                    $has = true;
                }
            }
        }

        return $has || $this->getSingleUnitTierPrice($product) !== null;
    }

    /** Return a discounted tier available to a guest buying one unit, in the store's base currency. */
    protected function getSingleUnitTierPrice(\Magento\Catalog\Model\Product $product): ?float
    {
        $guestProduct = clone $product;
        $guestProduct->setCustomerGroupId(\Magento\Customer\Model\Group::NOT_LOGGED_IN_ID);
        $price = $guestProduct->getTierPrice(1);
        return is_numeric($price) && $price > 0 && $price < $product->getPrice() ? (float)$price : null;
    }

    /**
     * @return float|int
     */
    public function getInventoryCount($sourceCode = null)
    {
        $stockQty = 0;
        if ($this->sourceInventoryApi->getAllItems($this->product)) {
            $websiteCode = $this->feed->getStore()->getWebsite()->getCode();
            $sourceItems = $this->sourceInventoryApi->getItems($this->product, $websiteCode);
            foreach ($sourceItems as $item) {
                if (!$item->getStatus() || ($sourceCode !== null && $item->getSourceCode() != $sourceCode)) {
                    continue;
                }
                $stockQty += (float)$item->getQuantity();
            }
            // Reservations belong to the website stock, never to an individual physical source.
            if ($sourceCode === null && $this->getFeed()->getConfig('general_use_stock_reservations')) {
                $stockQty += $this->sourceInventoryApi->getReservations(
                    $this->getProduct()->getSku(), null, $websiteCode
                );
            }
        } else {
            $stockState = $this->stockState;
            $stockQty = $stockState->getStockQty($this->product->getId(), $this->feed->getStore()->getWebsiteId());
            if ($this->getFeed()->getConfig('general_use_stock_reservations')) {
                $reservationCount = $this->sourceInventoryApi->getReservations(
                    $this->getProduct()->getSku(), null, $this->feed->getStore()->getWebsite()->getCode()
                );
                $stockQty += $reservationCount;
            }
        }

        return $stockQty > 0 ? $stockQty : 0;
    }

    /**
     * Get an array of sale price effective dates from catalog rules or product's special price
     *
     * @return false|array(\DateTime, \DateTime)
     */
    public function getSalePriceEffectiveDates()
    {
        $product = $this->product;

        $tierPrice = $this->getSingleUnitTierPrice($product);
        if ($tierPrice !== null && $tierPrice <= $product->getFinalPrice()) {
            // A tier price has no date range, even when an expired special-price attribute is still present.
            return false;
        }

        if ($this->hasPriceByCatalogRules($product)) {
            return $this->getCatalogRuleEffectiveDates($product);
        } elseif ($this->hasSpecialPrice(false)) {
            $specialPrice = $product->getSpecialPrice();
            return $this->getSpecialPriceEffectiveDates($product, !$specialPrice);
        }

        return false;
    }

    /**
     * Get the price array (regular/special + incuding/excluding tax)
     *
     * @param  \Magento\Catalog\Model\Product $product
     * @return array
     */
    protected function getProductPrices(\Magento\Catalog\Model\Product $product)
    {
        /**
 * @var \MageOS\ShoppingFeed\Model\Product\Helper\Catalog
*/
        $catalogHelper = $this->catalogHelper;

        $prices = [];
        // Compute equivalent to default/template/catalog/product/price.phtml
        $price = $product->getPrice();
        $convertedPrice = $this->convertPrice($price);
        $prices['p_excl_tax'] = $catalogHelper->getTaxPrice($product, $convertedPrice, null, null, null, null, $product->getStore());
        $prices['p_incl_tax'] = $catalogHelper->getTaxPrice($product, $convertedPrice, true, null, null, null, $product->getStore());

        $catalogRulesPrice = $this->getPriceByCatalogRules();
        $finalPrice = $catalogRulesPrice ? min($catalogRulesPrice, $product->getFinalPrice()) : $product->getFinalPrice();
        $tierPrice = $this->getSingleUnitTierPrice($product);
        if ($tierPrice !== null) {
            $finalPrice = min($finalPrice, $tierPrice);
        }
        $convertedFinalPrice = $this->convertPrice($finalPrice);

        $prices['sp_excl_tax'] = $catalogHelper->getTaxPrice($product, $convertedFinalPrice, null, null, null, null, $product->getStore());
        $prices['sp_incl_tax'] = $catalogHelper->getTaxPrice($product, $convertedFinalPrice, true, null, null, null, $product->getStore());

        return $prices;
    }


    /**
     * @param  null| $product
     * @return bool
     */
    public function hasPriceByCatalogRules($product = null)
    {
        $has = false;
        if (is_null($product)) {
            $product = $this->product;
        }

        if ($this->getFeed()->getConfig('general_apply_catalog_price_rules')) {
            $rulesPrice = $this->getPriceByCatalogRules();

            if ($product->getPrice() != null && round($product->getPrice(), 2) != round($rulesPrice, 2)) {
                $specialPrice = $product->getSpecialPrice();
                $hasSpecialPrice = $this->hasSpecialPrice(false);

                if ($hasSpecialPrice && $specialPrice > 0 && floatval($specialPrice) <= floatval($rulesPrice)) {
                    $has = false;
                } else {
                    $has = true;
                }
            }
        }

        return $has;
    }

    /**
     * Retrieves the start and end date for the product's special price, if they exist.
     *
     * @see self::hasSpecialPrice() - you should check to see if the product is using a special price
     *
     * @param  \Magento\Catalog\Model\Product $product
     * @return false|array
     */
    protected function getSpecialPriceEffectiveDates($product, $returnEmpty = true)
    {
        $specialFromDate = $product->getSpecialFromDate();
        $specialToDate = $product->getSpecialToDate();

        if (empty($specialFromDate) && empty($specialToDate) && $returnEmpty) {
            return false;
        }

        $locale = $this->localeResolver->getLocale();

        $fromDate = null;
        if (!$this->date->isEmptyDate($specialFromDate)) {
            $fromDate = \DateTime::createFromFormat('Y-m-d H:i:s', $specialFromDate);
        } else {
            $fromDate = \DateTime::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:s', time()-(30*86400)));
        }

        /**
 * @var \DateTime $fromDate
*/
        $fromDate = $this->timezone->date($fromDate, $locale);

        /**
 * @var \DateTime $toDate
*/
        if (!$this->date->isEmptyDate($specialToDate)) {
            $toDate = \DateTime::createFromFormat('Y-m-d H:i:s', $specialToDate);
        } else {
            $toDate = clone $fromDate;
            $toDate->add(new \DateInterval('P5Y'));
        }

        return [
            'start' => $fromDate,
            'end' => $toDate
        ];
    }

    /**
     * Retrieves the start and end date for the catalog rule that applies to the product.
     * If there's no rule, or if the rule doesn't have dates, it defaults 365 days
     *
     * @see self::hasPriceByCatalogRules() - you should first check if the product has catalog rules
     *
     * @param  \Magento\Catalog\Model\Product $product
     * @return false|\DateTime[]
     */
    protected function getCatalogRuleEffectiveDates($product)
    {
        /**
 * @var \DateTime $date
*/
        $date = $this->timezone->date();

        /**
 * @var \Magento\CatalogRule\Model\ResourceModel\Rule\Product\Price\Collection $catalogRuleCollection
*/
        $catalogRuleCollection = $this->catalogRuleCollectionFactory->create();
        $catalogRuleCollection->addFieldToFilter('rule_date', ['eq' => $this->date->formatDate($date, false)])
            ->addFieldToSelect(['latest_start_date', 'earliest_end_date'])
            ->addFieldToFilter('website_id', ['eq' => $this->getStore()->getWebsiteId()])
            ->addFieldToFilter('product_id', ['eq' => $product->getId()])
            ->addFieldToFilter('rule_price', ['eq' => $this->getPriceByCatalogRules()])
            ->addFieldToFilter('customer_group_id', ['eq' => \Magento\Customer\Model\Group::NOT_LOGGED_IN_ID]);

        /**
 * @var \Magento\CatalogRule\Model\Rule $rule
*/
        $rule = $catalogRuleCollection->getFirstItem();
        $timezoneCode = $this->timezone->getConfigTimezone() ?: 'UTC';
        $storeTimezone = new \DateTimeZone($timezoneCode);

        if ($rule->getData('latest_start_date')) {
            $fromDate = \DateTime::createFromFormat(
                '!Y-m-d',
                $rule->getData('latest_start_date'),
                $storeTimezone
            );
            $fromDate->setTime(1, 0);
        } else {
            $fromDate = null;
        }

        if ($rule->getData('earliest_end_date')) {
            $toDate = \DateTime::createFromFormat(
                '!Y-m-d',
                $rule->getData('earliest_end_date'),
                $storeTimezone
            );
            $toDate->setTime(1, 0);
        } else {
            $toDate = null;
        }

        if (is_null($fromDate)) {
            $fromDate = $date;
        }

        if (is_null($toDate)) {
            $toDate = clone $date;
            $toDate->add(new \DateInterval('P1Y'));
        }

        return [
            'start' => $fromDate,
            'end' => $toDate
        ];
    }

    /**
     * When computing the special price, we send the $price parameter from associated items
     *
     * @return mixed
     */
    protected function getPriceByCatalogRules($price = null)
    {
        if (is_null($price)) {
            $price = $this->product->getPrice();
        }

        return $this->productTypePrice->calculatePrice(
            $price,
            0.0,
            false,
            false,
            false,
            $this->getStore()->getWebsiteId(),
            \Magento\Customer\Model\Group::NOT_LOGGED_IN_ID,
            $this->product->getId()
        );
    }

    /**
     * @param  array $column
     * @return false|\Magento\Eav\Model\Entity\Attribute\AbstractAttribute
     * @throws FeedException
     */
    public function getMapAttribute($column = [])
    {
        if (!is_array($column)) {
            $column = ['attribute' => $column];
        }

        $attributeCode = $column['attribute'];
        $resource = $this->product->getResource();
        $attribute = $resource->getAttribute($attributeCode);

        if ($attribute === false) {
            throw new FeedException(
                new \Magento\Framework\Phrase(sprintf('Couldn\'t find attribute \'%s\'.', $column['attribute']))
            );
        }
        return $attribute;
    }

    /**
     * @param  \Magento\Catalog\Model\Product      $product
     * @param  \Magento\Eav\Model\Entity\Attribute $attribute
     * @throws FeedException
     * @return string
     */
    public function getAttributeValue($product, $attribute)
    {
        if ($attribute->getSourceModel() == 'Magento\Eav\Model\Entity\Attribute\Source\Boolean') {
            $attributeCode = $attribute->getAttributeCode();
            $value = $product->getData($attributeCode) ? 'Yes' : 'No';
        } elseif ($attribute->getFrontendInput() == "select" || $attribute->getFrontendInput() == "multiselect") {
            $value = $this->getAttributeSelectValue($product, $attribute, $this->getStore()->getId());
        } else {
            $value = $product->getData($attribute->getAttributeCode());
        }

        if (is_array($value)) {
            $value = implode(',', $value);
        } else {
            $value = (string) $value;
        }

        if (!is_string($value)) {
            throw new FeedException(
                new \Magento\Framework\Phrase(
                    sprintf(
                        'Attribute %s returned non-string value for product SKU #%s',
                        $attribute->getAttributeCode(),
                        $product->getSku()
                    )
                )
            );
        }

        return $value;
    }

    /**
     * Gets option text value from product for attributes with frontend_type select.
     * Multiselect values are by default imploded with comma.
     * By default gets option text from admin store (recommended - english values in feed).
     *
     * @param \Magento\Catalog\Model\Product      $product
     * @param \Magento\Eav\Model\Entity\Attribute $attribute
     * @param int|null                            $store_id
     *
     * @return string
     */
    protected function getAttributeSelectValue($product, $attribute, $storeId = null)
    {
        $attrValue = $product->getData($attribute->getAttributeCode());
        if (!$attrValue) { return '';
        }

        if (is_array($attrValue)) {
            $attrValue = implode(',', $attrValue);
        }

        // get the attribute options
        $cacheKey = ['select', 'attribute', $attribute->getAttributeCode(), 'options'];
        if ($this->cache->getCache($cacheKey, false) === false) {
            $data = $attribute->getOptions();
            $options = [];
            foreach ($data as $item) {
                if ($item->getValue()) {
                    $options[$item->getValue()] = ['label' => $item->getLabel(), 'store_labels' => $item->getStoreLabels()];
                }
            }
            $this->cache->setCache($cacheKey, $this->jsonSerializer->serialize($options));
        } else {
            $options = $this->jsonSerializer->unserialize($this->cache->getCache($cacheKey, []));
        }

        // find the label(s)
        $attributeValues = explode(",", $attrValue);
        $returnArray = [];
        foreach ($attributeValues as $val) {
            if (array_key_exists($val, $options)) {
                $returnArray[] = isset($options[$val]['store_labels'][$storeId])
                    ? $options[$val]['store_labels'][$storeId]
                    : $options[$val]['label'];
            }
        }

        return implode(', ', $returnArray);
    }

    /**
     * @param  $args
     * @return mixed|string
     */
    public function mapEmptyValues($args)
    {
        $value = '';
        $column = $args['column'];
        $columnsMapReplaced = $this->hasData('columns_map_replaced') ? $this->getData('columns_map_replaced') : [];

        // Avoid infinite loop, and not process if already replaced
        if (isset($columnsMapReplaced[$column]) && array_key_exists('empty_replaced', $columnsMapReplaced[$column])) {
            return $value;
        }

        $emptyColumnsReplaceMaps = $this->feed->getConfig('filters_map_replace_empty_columns', []);
        if (is_array($emptyColumnsReplaceMaps) && count($emptyColumnsReplaceMaps) > 0) {
            usort(
                $emptyColumnsReplaceMaps,
                static fn (array $left, array $right): int => ($left['order'] ?? 0) <=> ($right['order'] ?? 0)
            );

            // Go through replacement rules and pick the one matching current column.
            foreach ($emptyColumnsReplaceMaps as $arr) {
                if ($column == $arr['column']) {
                    $columnsMapReplaced[$column]['empty_replaced'] = true;
                    $this->setData('columns_map_replaced', $columnsMapReplaced);

                    $attribute = $arr['attribute'] ?? '';
                    if (!empty($arr['static']) && (!$attribute || $attribute == 'directive_static_value')) {
                        $value = $arr['static'];
                    } else {
                        $arr['attribute'] = $attribute;
                        $value = $this->getMapValue($arr);
                    }
                    if (!empty($value)) {
                        break;
                    }
                }
            }
        }


        return $value;
    }

    /**
     * @param  $rows
     * @return $this
     */
    protected function checkEmptyColumns($row)
    {
        $skipEmptyColumn = $this->feed->getConfig('filters_skip_column_empty');

        if (is_array($skipEmptyColumn)) {
            foreach ($skipEmptyColumn as $column) {
                if (isset($row[$column]) && $row[$column] == "") {
                    $this->setSkipProduct(sprintf("by product skip rule, has %s empty.", $column));
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Generates feed row(s) based on the given product
     * This is called for each Enabled & Visible (Catalog & Search) product
     *
     * @return array
     */
    public function map()
    {
        $this->beforeMap();
        $rows = $this->internalMap();
        $rows = $this->afterMap($rows);
        return $rows;
    }

    /**
     * Implement product options on top of complex product variants.
     *
     * @return $this
     */
    public function beforeMap()
    {
        $this->setData('is_skipped', false);
        return $this;
    }

    /**
     * Forms product's data row. [column] => [value]
     *
     * @param bool $checkDuplicates
     * @param bool $mapAssociatedProducts Reserved for composite adapters
     * @return array
     */
    public function internalMap(bool $checkDuplicates = true, bool $mapAssociatedProducts = true): array
    {
        $rows = [];
        if ($checkDuplicates && $this->isDuplicate()) {
            return $rows;
        }

        // Map current product
        $fields = [];
        foreach ($this->feed->getColumnsMap() as $arr) {
            $column = $arr['column'];
            $row = $this->getMapValue($arr);
            if (isset($fields[$column])) {
                if (is_array($fields[$column])) {
                    $fields[$column][] = $row;
                } else {
                    $fields[$column] = [$fields[$column], $row];
                }
            } else {
                $fields[$column] = $row;
            }
        }
        $rows[] = $fields;

        return $rows;
    }

    /**
     * Checks if option is enabled and if categories match with the product
     *
     * @return bool
     */
    protected function canAddProductOptions()
    {
        $multipleRowsEnabled =
            $this->getFeed()->getConfig('options_mode') == \MageOS\ShoppingFeed\Model\Feed\Source\Product\OptionHandling::MULTIPLE_ROWS;
        if ($multipleRowsEnabled) {
            $categories = $this->getFeed()->getConfig('options_vary_categories', []);
            $matchingCategories = array_intersect($categories, $this->getProduct()->getCategoryIds());
            if (empty($categories) || (count($categories) > 0 && count($matchingCategories) > 0)) {
                return true;
            }
        }
        return false;
    }

    protected function filterAndFormatRows(array $rows)
    {
        $feedType = $this->feed->getData('type');
        foreach ($this->feed->getColumnsMap() as $arr) {
            $isAllowed = $this->feedTypesConfig->isAllowedDirective($feedType, $arr['attribute']);
            if (!$isAllowed) {
                continue;
            }

            $column = $arr['column'];
            $directive = $this->feedTypesConfig->getDirective($feedType, $arr['attribute']);
            $mapperData = $this->mapperFactory->getMapperData($directive, $feedType);
            $hasFilter = isset($mapperData['filter']) && $mapperData['filter'];

            if ($hasFilter) {
                $mapper = $this->mapperFactory->create($directive, $this);

                foreach ($rows as $key => &$row) {
                    $skip = $mapper->filter($row[$column]);
                    if ($skip) {
                        unset($rows[$key]);
                        $this->setSkipProduct(sprintf('filtered by column "%s"', $column));
                    }
                }
            }

            $hasFormat = !is_null($this->formatterFactory->getFormatterData($directive, $feedType));
            if ($hasFormat) {
                $formatter = $this->formatterFactory->create($directive, $this);
                foreach ($rows as &$row) {
                    $row[$column] = $formatter->setColumn($column)->run($row[$column]);
                }
            }
        }

        return $rows;
    }

    /**
     * @param  $rows
     * @return array
     */
    protected function afterMap(array $rows)
    {
        foreach ($this->getAdditionalProcessors() as $processor) {
            $processor->setAdapter($this);
            $rows = $processor->execute($rows);
        }

        // If we don't have any rows, they might be skipped, so don't process filters/formats/empty cells
        if (count($rows)) {
            // Format rows (add currency to prices, ...)
            $rows = $this->filterAndFormatRows($rows);

            foreach ($rows as $k => $row) {
                if (!$this->checkEmptyColumns($row)) {
                    unset($rows[$k]);
                }
            }
        }

        // Reset the row cache, leave the feed cache
        $this->cache->setCache('row', []);
        $this->unsetData('mode_parent');
        return $rows;
    }

    /**
     * Catch any undefined currency rate
     *
     * @param  $price
     * @return float
     */
    public function convertPrice($price)
    {
        try {
            return $this->getStore()
                ->getBaseCurrency()
                ->convert($price, $this->getStore()->getCurrentCurrency());
        } catch (\Exception $e) {
            return $price;
        }
    }

    public function setSkipProduct($reason, $productId = null)
    {
        if (is_null($productId)) {
            $productId = $this->getProduct()->getId();
        }

        $this->getGenerator()->addSkippedProduct($reason, $productId);
        $this->setData('is_skipped', true);

        return $this;
    }

    /**
     * Check if product is set to be skipped in the Product Edit page
     *
     * @return bool
     */
    public function isSkipped()
    {
        $product = $this->getProduct();
        if ($product->getData('mageos_shopping_feed_skip_submit') == 1) {
            $this->setSkipProduct("'Skip from Being Submitted' = 'Yes'.");
            return true;
        }

        $includeAllProducts = (bool)$this->getFeed()->getConfig('categories_include_all_products');

        if (!empty($product->getCategoryIds()) && !$includeAllProducts) {
            $collection = $this->categoryCollectionFactory->create()
                ->addIsActiveFilter()
                ->addFieldToFilter('entity_id', ['in' => $product->getCategoryIds()]);
            $count = $collection->getSize();

            if (!$count) {
                $this->setSkipProduct("categories are disabled.");
                return true;
            }
        }

        return (bool)$this->getData('is_skipped');
    }

    /**
     * Check if product was already processed (either as a standalone product or associated product of some other)
     *
     * @return bool
     */
    public function isDuplicate()
    {
        // We don't check for duplicates if this is a test mode
        if ($this->isTestMode()) {
            return false;
        }

        // Check if this is a simple product that's part of an enabled complex product
        if ($this->getFeed()->getConfig('general_complex_duplicates_check')) {
            $parent_ids = $this->getProductInComplexProduct();

            if (!empty($parent_ids)) {
                $first_parent_id = min(array_keys($parent_ids));
                $this->setSkipProduct(
                    sprintf('simple product scheduled to be processed as part of the complex product (ID: %s %s)',
                        $first_parent_id, $parent_ids[$first_parent_id]),
                    $this->getProduct()->getId()
                );
                return true;
            }
        }

        /** @var \MageOS\ShoppingFeed\Model\ResourceModel\Generator\Process\Collection $processLookup */
        $processLookup = $this->processCollectionFactory->create()
            ->setFeedFilter($this->getFeed())
            ->setProductFilter($this->getProduct());
        /** @var \MageOS\ShoppingFeed\Model\Generator\Process $process */
        $process = ($processLookup->count()) ? $processLookup->getFirstItem() : $this->processFactory->create();

        if ($this->hasParentAdapter()) {
            $process->setParentItemId($this->getParentAdapter()->getProduct()->getId());
        }

        if ($process->getId()) {
            if ($process->getStatus() == \MageOS\ShoppingFeed\Model\Generator\Process::STATUS_PROCESSED) {
                $this->setSkipProduct(
                    sprintf(
                        'omitted because it has been already processed%s',
                        ($process->getParentItemId() > 0
                            ? sprintf(' as part of product ID %s', $process->getParentItemId())
                            : ' as a standalone product'
                        )
                    ),
                    $this->getProduct()->getId()
                );
                return true;
            }
        } else {
            $process->setFeedId($this->getFeed()->getId())
                ->setItemId($this->getProduct()->getId());
        }
        $process->setStatus(\MageOS\ShoppingFeed\Model\Generator\Process::STATUS_PROCESSED)
            ->save();

        return false;
    }

    /**
     * Get number of total rows for this adapter
     *
     * @return int
     */
    public function getChildrenCount()
    {
        return self::DEFAULT_CHILDREN_COUNT;
    }

    /**
     * @return \Magento\Store\Model\Store
     * @throws FeedException
     */
    public function getStore()
    {
        if (!$this->storeObject instanceof \Magento\Store\Model\Store) {
            throw new FeedException(
                new \Magento\Framework\Phrase('Adapter failed, feed was not set!')
            );
        }
        return $this->storeObject;
    }

    /**
     * Returns filter instance
     *
     * @return \MageOS\ShoppingFeed\Model\Product\Filter
     */
    public function getFilter()
    {
        return $this->filter;
    }

    /**
     * @return \MageOS\ShoppingFeed\Model\Feed
     */
    public function getFeed()
    {
        return $this->feed;
    }

    /**
     * @return \Magento\Framework\Stdlib\DateTime\Timezone
     */
    public function getTimezone()
    {
        return $this->timezone;
    }


    /**
     * @param  \MageOS\ShoppingFeed\Model\Feed $feed
     * @return $this
     */
    public function setFeed(\MageOS\ShoppingFeed\Model\Feed $feed)
    {
        $this->storeObject = $feed->getStore();
        $this->feed = $feed;
        return $this;
    }

    /**
     * @return $this
     */
    public function setTestMode()
    {
        $this->isTestMode = true;
        return $this;
    }

    /**
     * @return mixed
     */
    public function isTestMode()
    {
        return (bool) $this->isTestMode;
    }

    /**
     * Sets the product for this adapter
     *
     * @param  \Magento\Catalog\Model\Product $product
     * @return $this
     * @throws FeedException
     */
    public function setProduct(\Magento\Catalog\Model\Product $product)
    {
        if (!$product->getId()) {
            throw new FeedException(
                new \Magento\Framework\Phrase('Adapter can\'t be created, product is not loaded')
            );
        }
        $this->product = $product;
        return $this;
    }

    /**
     * @return \Magento\Catalog\Model\Product|null
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @param  array $processors
     * @return array
     */
    public function getAdditionalProcessors($processors = [])
    {
        if ($this->canAddProductOptions()) {
            if (is_null($this->option)) {
                $this->option = $this->optionFactory->create();
            }
            array_push($processors, $this->option);
        }
        return $processors;
    }

    public function getOptionProcessor()
    {
        if (is_null($this->option)) {
            $this->option = $this->optionFactory->create()->setAdapter($this);
        }
        return $this->option;
    }

    public function getDbConnection()
    {
        if (is_null($this->dbConnection)) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $resource = $objectManager->get(\Magento\Framework\App\ResourceConnection::class);
            $this->dbConnection = $resource->getConnection();
        }
        return $this->dbConnection;
    }

    private function getEavLinkField()
    {
        if (is_null($this->eavLinkField)) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $metadataPool = $objectManager->get(\Magento\Framework\EntityManager\MetadataPool::class);
            $metadata = $metadataPool->getMetadata(\Magento\Catalog\Api\Data\ProductInterface::class);
            $this->eavLinkField = $metadata->getLinkField();
        }
        return $this->eavLinkField;
    }

    /**
     * Check if the current product is a simple product that's part of an enabled complex product
     * (configurable, bundle, or grouped) and is individually visible
     * Optimized version using raw SQL queries for better performance
     *
     * @return array
     */
    private function getProductInComplexProduct()
    {
        $complexParents = [];
        $product = $this->getProduct();

        // Complex products and simple associated products are not considered here
        if ($product->getTypeId() !== \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE || $this->hasParentAdapter()) {
            return [];
        }

        // Search-only products can be processed before their parent too.
        $visibility = $product->getVisibility();
        if ($visibility == \Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE) {
            return [];
        }

        $productId = $product->getId();
        try {
            $websiteId = $this->getFeed()->getStore()->getWebsite()->getId();
            $storeId = $this->getFeed()->getStoreId();
        } catch (\Exception $e) {
            return [];
        }

        // Work around column migration from 'row_id' to 'entity_id'
        $linkField = $this->getEavLinkField();
        $connection = $this->getDbConnection();


        // Check configurable parents
        if ($this->getFeed()->isProductTypeEnabled('configurable')) {
            $configurableSql = "
                SELECT parent.entity_id
                FROM catalog_product_entity parent
                INNER JOIN catalog_product_super_link csl ON parent.entity_id = csl.parent_id
                INNER JOIN catalog_product_entity_int status_attr ON parent.{$linkField} = status_attr.{$linkField}
                INNER JOIN eav_attribute ea_status ON status_attr.attribute_id = ea_status.attribute_id
                INNER JOIN catalog_product_website cpw ON parent.entity_id = cpw.product_id
                WHERE csl.product_id = ?
                    AND ea_status.attribute_code = 'status'
                    AND status_attr.value = 1
                    AND (status_attr.store_id = 0 OR status_attr.store_id = ?)
                    AND cpw.website_id = ?
                ORDER BY status_attr.store_id DESC
                LIMIT 1
            ";
            $parentId = $connection->fetchOne($configurableSql, [$productId, $storeId, $websiteId]);
            if ($parentId) {
                $complexParents[$parentId] = 'configurable';
            }
        }

        // Check bundle parents
        if ($this->getFeed()->isProductTypeEnabled('bundle')) {
            $bundleSql = "
                SELECT parent.entity_id
                FROM catalog_product_entity parent
                INNER JOIN catalog_product_bundle_selection cbs ON parent.entity_id = cbs.parent_product_id
                INNER JOIN catalog_product_entity_int status_attr ON parent.{$linkField} = status_attr.{$linkField}
                INNER JOIN eav_attribute ea_status ON status_attr.attribute_id = ea_status.attribute_id
                INNER JOIN catalog_product_website cpw ON parent.entity_id = cpw.product_id
                WHERE cbs.product_id = ?
                    AND ea_status.attribute_code = 'status'
                    AND status_attr.value = 1
                    AND (status_attr.store_id = 0 OR status_attr.store_id = ?)
                    AND cpw.website_id = ?
                ORDER BY status_attr.store_id DESC
                LIMIT 1
            ";
            $parentId = $connection->fetchOne($bundleSql, [$productId, $storeId, $websiteId]);
            if ($parentId) {
                $complexParents[$parentId] = 'bundle';
            }
        }

        // Check grouped parents
        if ($this->getFeed()->isProductTypeEnabled('grouped')) {
            $groupedSql = "
                SELECT parent.entity_id
                FROM catalog_product_entity parent
                INNER JOIN catalog_product_link cpl ON parent.entity_id = cpl.product_id
                INNER JOIN catalog_product_link_type cplt ON cpl.link_type_id = cplt.link_type_id
                INNER JOIN catalog_product_entity_int status_attr ON parent.{$linkField} = status_attr.{$linkField}
                INNER JOIN eav_attribute ea_status ON status_attr.attribute_id = ea_status.attribute_id
                INNER JOIN catalog_product_website cpw ON parent.entity_id = cpw.product_id
                WHERE cpl.linked_product_id = ?
                    AND cplt.code = 'super'
                    AND ea_status.attribute_code = 'status'
                    AND status_attr.value = 1
                    AND (status_attr.store_id = 0 OR status_attr.store_id = ?)
                    AND cpw.website_id = ?
                ORDER BY status_attr.store_id DESC
                LIMIT 1
            ";
            $parentId = $connection->fetchOne($groupedSql, [$productId, $storeId, $websiteId]);
            if ($parentId) {
                $complexParents[$parentId] = 'grouped';
            }
        }

        return $complexParents;
    }
}
