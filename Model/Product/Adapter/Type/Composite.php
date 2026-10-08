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

namespace MageOS\ShoppingFeed\Model\Product\Adapter\Type;

use \MageOS\ShoppingFeed\Model\Product\Adapter\AdapterAbstract;
use \MageOS\ShoppingFeed\Model\Feed\Source\Product\Inheritance;

/**
 * Composite Adapter, holds business logic between Product, Config and Mapper
 *
 * Class Composite
 *
 * @package MageOS\ShoppingFeed\Model\Product\Adapter\Type
 */
class Composite extends AdapterAbstract
{
    protected $allowed_parent = [
        \MageOS\ShoppingFeed\Model\Feed\Source\Product\AssociatedMode::ONLY_PARENT,
        \MageOS\ShoppingFeed\Model\Feed\Source\Product\AssociatedMode::BOTH_PARENT_ASSOCIATED
    ];

    protected $allowed_assoc = [
        \MageOS\ShoppingFeed\Model\Feed\Source\Product\AssociatedMode::ONLY_ASSOCIATED,
        \MageOS\ShoppingFeed\Model\Feed\Source\Product\AssociatedMode::BOTH_PARENT_ASSOCIATED
    ];

    /**
     * @var \MageOS\ShoppingFeed\Model\Product\Mapper\Generic\Simple\Concatenate
     */
    protected $concatenateMapper;

    /**
     * @var array
     */
    protected $skippedData = [];

    /** @var \Magento\Catalog\Model\ResourceModel\Product\Link\Product\CollectionFactory */
    protected $linkedProductCollectionFactory;


    public function __construct(
        \Magento\Framework\Filesystem $filesystem,
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
        \MageOS\ShoppingFeed\Model\Product\Mapper\Generic\Simple\Concatenate $concatenateMapper,
        \Magento\Catalog\Model\ResourceModel\Product\Link\Product\CollectionFactory $linkedProductCollectionFactory,
        array $data = []
    ) {
        $this->concatenateMapper = $concatenateMapper;
        $this->linkedProductCollectionFactory = $linkedProductCollectionFactory;

        parent::__construct(
            $filesystem, $feed, $product, $feedTypesConfig, $mapperFactory, $helper,
            $weeeData, $taxData, $catalogHelper, $catalogRuleCollectionFactory, $productTypePrice,
            $stockState, $filter, $localeResolver, $timezone, $date, $optionFactory, $processFactory,
            $processCollectionFactory, $logger, $cache, $adapterFactory, $formatterFactory, $categoryCollectionFactory,
            $sourceInventoryApi, $jsonSerializer, $data
        );
    }

    /**
     * @param  \Magento\Catalog\Model\ResourceModel\Product\Collection $associatedProductCollection
     * @return array
     */
    protected function prepareAssociatedProductAdapters($associatedProductCollection)
    {
        $associatedProductAdapters = [];

        foreach ($associatedProductCollection as $associatedProduct) {
            /**
 * @var \Magento\Catalog\Model\Product $associatedProduct
*/
            if ($associatedProduct->isDisabled()) {
                continue;
            }

            $associatedProductAdapter = $this->adapterFactory->create($associatedProduct, $this->getFeed(), false);
            if ($associatedProductAdapter !== false) {
                $associatedProductAdapter->setParentAdapter($this)
                    ->setData('generator', $this->getGenerator())
                    ->setData('associated_product_adapters', []);
                if ($this->isTestMode()) {
                    $associatedProductAdapter->setTestMode();
                }
                $associatedProductAdapters[] = $associatedProductAdapter;
            }
        }

        return $associatedProductAdapters;
    }

    /**
     * Internal method to pull feed config for specific product type
     * This needs to be overwritten in child class
     *
     * @return int
     */
    public function getAssociatedProductsMode()
    {
        return \MageOS\ShoppingFeed\Model\Feed\Source\Product\AssociatedMode::BOTH_PARENT_ASSOCIATED;
    }

    /**
     * Internal method to pull feed config for specific product type
     * This needs to be overwritten in child class
     *
     * @return array
     */
    public function getAssociatedMapInheritance()
    {
        return [];
    }

    /**
     * @inheritdoc
     * @param bool $checkDuplicates
     * @param bool $mapAssociatedProducts
     */
    public function internalMap(bool $checkDuplicates = true, bool $mapAssociatedProducts = true): array
    {
        $associatedMode = $this->getAssociatedProductsMode();
        $rows = [];

        if (in_array($associatedMode, $this->allowed_parent)) {
            $this->setData('map_parent', true);
            // Map current product
            $fields = [];
            foreach ($this->feed->getColumnsMap() as $arr) {
                $row = false;
                $column = $arr['column'];
                if (!$row) {
                    $row = $this->getMapValue($arr);
                }

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
        }

        if ($mapAssociatedProducts && in_array($associatedMode, $this->allowed_assoc)) {
            $rows = array_merge($rows, $this->mapAssociatedProducts($checkDuplicates));
        }

        return $rows;
    }

    public function setSkipProduct($reason, $productId = null)
    {
        if (is_null($productId)) {
            $productId = $this->getProduct()->getId();
        }

        // Set skipped data locally instead of Generator
        if (isset($this->skippedData[$reason])) {
            array_push($this->skippedData[$reason], $productId);
        } else {
            $this->skippedData[$reason] = [$productId];
        }

        $this->setData('is_skipped', true);

        return $this;
    }

    /**
     * Get rows for associated products
     *
     * @param bool $checkDuplicates
     * @return array
     */
    public function mapAssociatedProducts(bool $checkDuplicates = true): array
    {
        $rows = [];
        $associatedProductAdapters = $this->getData('associated_product_adapters');
        $feedType = $this->feed->getType();

        /**
 * @var \MageOS\ShoppingFeed\Model\Product\Adapter\AdapterAbstract $associatedProductAdapter
*/
        foreach ($associatedProductAdapters as $associatedProductAdapter) {
            if ($associatedProductAdapter->isSkipped()
                || ($checkDuplicates && $associatedProductAdapter->isDuplicate())
            ) {
                continue;
            }

            $fields = [];
            foreach ($this->feed->getColumnsMap() as $arr) {
                $cell = $this->mapByInheritance($associatedProductAdapter, $arr);
                // Grab from associated by default if no inheritance rule defined
                if ($cell === false) {
                    $cell = $associatedProductAdapter->getMapValue($arr);
                }

                $column = $arr['column'];
                $directive = $this->feedTypesConfig->getDirective($feedType, $arr['attribute']);
                if (!empty($directive)) {
                    $mapperData = $this->mapperFactory->getMapperData($directive, $feedType);
                    $mapper = $this->mapperFactory->create($directive, $associatedProductAdapter);
                    if (isset($mapperData['filter']) && $mapperData['filter']) {
                        $skip = $mapper->filter($cell);
                        if ($skip) {
                            $this->setSkipProduct(sprintf('filtered by column "%s"', $column));
                            continue 2;
                        }
                    }
                }

                if (isset($fields[$column])) {
                    if (is_array($fields[$column])) {
                        $fields[$column][] = $cell;
                    } else {
                        $fields[$column] = [$fields[$column], $cell];
                    }
                } else {
                    $fields[$column] = $cell;
                }
            }

            array_push($rows, $fields);
        }

        return $rows;
    }

    protected function afterMap(array $rows)
    {
        $rows = parent::afterMap($rows);

        // Log skipped
        foreach($this->skippedData as $message => $ids) {
            $this->logger->info(
                sprintf(
                    "Skipped associated product id(s) [%s], parent %s - %s",
                    implode(', ', $ids),
                    $this->getProduct()->getId(),
                    $message
                )
            );
        }
        $this->skippedData = [];

        return $rows;
    }

    /**
     * @inheritdoc
     */
    public function getChildrenCount()
    {
        $associatedAdapters = $this->getData('associated_product_adapters');
        return is_array($associatedAdapters) ? self::DEFAULT_CHILDREN_COUNT + count($associatedAdapters) : self::DEFAULT_CHILDREN_COUNT;
    }


    /**
     * @inheritdoc
     */
    public function hasSpecialPrice($processRules = true, $product = null)
    {
        $associatedProductAdapters = $this->getData('associated_product_adapters');

        $has = false;
        /**
 * @var \MageOS\ShoppingFeed\Model\Product\Adapter\AdapterAbstract $associatedProductAdapter
*/
        foreach ($associatedProductAdapters as $associatedProductAdapter) {
            if ($associatedProductAdapter->hasSpecialPrice($processRules, $product)) {
                $has = true;
                break;
            }
        }

        return $has;
    }

    protected function mapByInheritance(\MageOS\ShoppingFeed\Model\Product\Adapter\AdapterAbstract $adapter, $column = [])
    {
        if (array_key_exists('column', $column)) {
            $column_name = $column['column'];
            $map = $this->getAssociatedMapInheritance();

            foreach ($map as $row) {
                if ($row['column'] == $column_name) {
                    if (in_array($column['attribute'], $this->concatenateMapper->getDirectiveNames()) && !empty($row['extra'])) {
                        $attributes = explode(',', $row['extra']);
                        // for concatenation, map each attribute individually as defined in inheritance rule
                        foreach ($attributes as $code) {
                            $code = trim($code);
                            if (strpos($column['param'], $code) !== false) {
                                $val = $this->mapByInheritanceFrom(
                                    $adapter,
                                    ['column' => 'concat_'.$code, 'attribute' => $code],
                                    $row['from']
                                );
                                $column['param'] = str_replace("{{{$code}}}", $val, $column['param']);
                            }
                        }
                        // map column definition on associated product only,
                        return $adapter->getMapValue($column);
                    } else {
                        // map column definition as defined in inheritance rule
                        return $this->mapByInheritanceFrom($adapter, $column, $row['from']);
                    }
                }
            }
        }

        return false;
    }

    protected function mapByInheritanceFrom(\MageOS\ShoppingFeed\Model\Product\Adapter\AdapterAbstract $adapter, $column = [], $from = Inheritance::ASSOCIATED_FIRST)
    {
        switch ($from) {
        case Inheritance::PARENT_ONLY:
            $value = $this->getMapValue($column);
            break;
        case Inheritance::ASSOCIATED_ONLY:
            $value = $adapter->getMapValue($column);
            break;
        case Inheritance::PARENT_FIRST:
            $value = $this->getMapValue($column);
            if (empty($value)) {
                $value = $adapter->getMapValue($column);
            }
            break;
        case Inheritance::ASSOCIATED_FIRST:
            $value = $adapter->getMapValue($column);
            if (empty($value)) {
                $value = $this->getMapValue($column);
            }
            break;
        default:
            throw new \Magento\Framework\Exception\LocalizedException(__('Unknown product inheritance mode.'));
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
                    $this->getLogger()->info(
                        sprintf(
                            "product id %d skipped - by product skip rule, has %s empty.",
                            array_key_exists('id', $row) ? $row['id'] : $this->getProduct()->getId(),
                            $column
                        )
                    );
                    $this->getGenerator()->updateCountSkip(1);
                    return false;
                }
            }
        }

        return true;
    }
}
