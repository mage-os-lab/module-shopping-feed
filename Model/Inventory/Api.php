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
 * @copyright Copyright (c) 2021 RocketWeb (http://rocketweb.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author    Rocket Web Inc.
 */

namespace MageOS\ShoppingFeed\Model\Inventory;


class Api
{
    protected $moduleManager;
    protected $objectManager;
    protected $searchCriteriaBuilder;
    protected $resourceConnection;

    public function __construct(
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ) {
        $this->moduleManager = $moduleManager;
        $this->objectManager = $objectManager;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @return bool
     */
    public function isMsiEnabled()
    {
        return $this->moduleManager->isEnabled('Magento_InventoryApi');
    }

    /**
     * @param  $product
     * @param  $websiteCode
     * @return array
     */
    public function getItems($product, $websiteCode)
    {

        if (!$this->isMsiEnabled()) {
            return [];
        }

        $stockResolver = $this->objectManager->create('Magento\InventorySalesApi\Api\StockResolverInterface');
        $stock = $stockResolver->execute("website", $websiteCode);
        $stockId = (int)$stock->getStockId();

        $sourceCodes = [];
        $searchCriteria = $this->searchCriteriaBuilder->addFilter('stock_id', $stockId)->create();
        $stockLinks = $this->objectManager->create('Magento\InventoryApi\Api\GetStockSourceLinksInterface');
        $sourceRepository = $this->objectManager->create('Magento\InventoryApi\Api\SourceRepositoryInterface');
        foreach ($stockLinks->execute($searchCriteria)->getItems() as $link) {
            if ($sourceRepository->get($link->getSourceCode())->isEnabled()) {
                $sourceCodes[] = $link->getSourceCode();
            }
        }

        $items = [];
        $sourceItems = $this->getAllItems($product);
        foreach ($sourceItems as $item) {
            if (in_array($item['source_code'], $sourceCodes)) {
                $items[] = $item;
            }
        }

        return $items;
    }

    /**
     * @param  $product
     * @return array
     */
    public function getAllItems($product)
    {

        if (!$this->isMsiEnabled()) {
            return [];
        }

        $sourceItems = $this->objectManager->create('Magento\InventoryApi\Api\GetSourceItemsBySkuInterface');
        return $sourceItems->execute($product->getSku());
    }

    /**
     * @param  string $sku
     * @param  string $sourceCode
     * @param  string|null $websiteCode Resolve reservations for the feed's website stock.
     * @return float|int
     */
    public function getReservations(string $sku, ?string $sourceCode = null, ?string $websiteCode = null)
    {
        if (!$this->isMsiEnabled()) {
            return 0;
        }
        $connection = $this->resourceConnection->getConnection();
        $reservationTable = $this->resourceConnection->getTableName('inventory_reservation');
        $select = $connection->select()
            ->from($reservationTable, ['quantity' => 'SUM(quantity)'])
            ->where('sku = ?', $sku);

        if ($websiteCode !== null) {
            $resolver = $this->objectManager->create('Magento\InventorySalesApi\Api\StockResolverInterface');
            $stockId = (int)$resolver->execute('website', $websiteCode)->getStockId();
            $select->where('stock_id = ?', $stockId);
        } elseif ($sourceCode !== null) {
            $inventoryTableName = $this->resourceConnection->getTableName('inventory_source_stock_link');
            $sourceSelect = $connection->select()->from($inventoryTableName, ['stock_id'])
                ->where('source_code = ?', $sourceCode);
            $stockId = $connection->fetchOne($sourceSelect);
            if ($stockId) {
                $select->where("stock_id = ?", $stockId);
            } else {
                return 0;
            }
        }

        $reservationQty = $connection->fetchOne($select);
        return $reservationQty ? (float)$reservationQty : 0;
    }
}
