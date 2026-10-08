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


namespace MageOS\ShoppingFeed\Model\ResourceModel;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb as AbstractDb;

class Feed extends AbstractDb
{
    /**
     * Core date model
     *
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $date;

    /**
     * @var
     */
    protected $unsUpdatedAt = false;

    /**
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Magento\Framework\Stdlib\DateTime\DateTime       $date
     * @param string                                            $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        $connectionName = null
    ) {
        $this->date = $date;

        parent::__construct($context, $connectionName);
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('mageos_shopping_feed_feed', 'id');
    }

    /**
     * Feed has no edited changes, don't update this field.
     *
     * @param bool $suppress
     * @return $this
     */
    public function unsUpdatedAt(bool $suppress = true)
    {
        $this->unsUpdatedAt = $suppress;

        return $this;
    }

    /**
     * Perform actions before object save
     *
     * @param  AbstractModel $object
     * @return $this
     */
    protected function _beforeSave(AbstractModel $object)
    {
        if (!$object->getId()) {
            $object->setCreatedAt($this->date->gmtDate());
        }
        if (!$this->unsUpdatedAt) {
            $object->setUpdatedAt($this->date->gmtDate());
        }

        return $this;
    }

    /**
     * Some mysql servers have constraint disabled constraints
     *
     * @param  AbstractModel $object
     * @return $this
     */
    protected function _afterDelete(\Magento\Framework\Model\AbstractModel $object)
    {
        $cond = $this->getConnection()->quoteInto('feed_id=?', $object->getId());

        $this->getConnection()->delete($this->getTable('mageos_shopping_feed_feed_config'), $cond);
        $this->getConnection()->delete($this->getTable('mageos_shopping_feed_feed_queue'), $cond);
        $this->getConnection()->delete($this->getTable('mageos_shopping_feed_feed_schedule'), $cond);
        $this->getConnection()->delete($this->getTable('mageos_shopping_feed_feed_upload'), $cond);
        $this->getConnection()->delete($this->getTable('mageos_shopping_feed_process'), $cond);
        $this->getConnection()->delete($this->getTable('mageos_shopping_feed_shipping'), $cond);

        return parent::_afterDelete($object);
    }
}
