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


class Quantity extends \MageOS\ShoppingFeed\Model\Product\Mapper\Generic\Simple\Quantity
{

    public function map(array $params = [])
    {
        $sourceItem = $this->getAdapter()->getData('inventory_source_item');
        if (!$sourceItem) {
            return parent::map($params);
        }

        $cell = $sourceItem->getQuantity();

        return $this->getAdapter()->getFilter()->cleanField(
            sprintf('%d', $cell >= 0 ? $cell : 0),
            $params
        );
    }

}
