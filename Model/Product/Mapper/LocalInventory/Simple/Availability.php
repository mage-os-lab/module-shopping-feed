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
 * @copyright Copyright (c) 2023 RocketWeb (http://rocketweb.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author    Rocket Web Inc.
 */

namespace MageOS\ShoppingFeed\Model\Product\Mapper\LocalInventory\Simple;


class Availability extends \MageOS\ShoppingFeed\Model\Product\Mapper\Generic\Simple\Availability
{
    /**
     * @param $adapter
     * @return mixed|string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStockStatus($adapter)
    {
        $cell = self::OUT_OF_STOCK;
        $sourceItem = $this->getAdapter()->getData('inventory_source_item');

        if (!$sourceItem) {
            $status = parent::getStockStatus($adapter);
            return $status === self::IN_STOCK ? self::IN_STOCK : self::OUT_OF_STOCK;
        }

        if (!$sourceItem->getStatus()) {
            return self::OUT_OF_STOCK;
        }
        $qty = $sourceItem->getQuantity();
        if ($qty > 0) {
            $cell = self::IN_STOCK;
        }

        return $cell;
    }

}
