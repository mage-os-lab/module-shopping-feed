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
namespace MageOS\ShoppingFeed\Block\Adminhtml;

use Composer\InstalledVersions;

class Info extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * @var \Magento\Framework\Module\ModuleListInterface
     */
    protected $_moduleList;

    /*
     * @var \Magento\Framework\Module\ResourceInterface
     */
    protected $_moduleResource;

    /**
     * Load Module Interfaces
     *
     * @param \Magento\Backend\Block\Template\Context     $context
     * @param array                                       $data
     * @param \Magento\Framework\Module\ResourceInterface $moduleResource
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\Module\ResourceInterface $moduleResource,
        array $data = []
    ) {

        parent::__construct($context, $data);
        $this->_moduleList = $moduleList;
        $this->_moduleResource = $moduleResource;
    }

    /**
     * Remove the scope label
     *
     * @param  \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _renderScopeLabel(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return '';
    }

    /**
     * List modules and their versions
     *
     * @param  \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $html = '<ul style="list-style-type: none; margin-top: 7px;">';
        foreach ($this->_moduleList->getNames() as $moduleName) {
            if (strpos($moduleName, 'MageOS_ShoppingFeed') !== false) {
                $version = $this->getModuleVersion($moduleName);
                $label = $moduleName;
                if ($version !== '') {
                    $label .= ' ' . $version;
                }
                $html .= '<li>' . $this->escapeHtml($label) . '</li>';
            }
        }
        return $html . '</ul>';
    }

    /**
     * Read the installed package version, falling back to the module's schema version.
     *
     * @param string $moduleName
     * @return string
     */
    private function getModuleVersion(string $moduleName): string
    {
        $version = null;
        if ($moduleName === 'MageOS_ShoppingFeed'
            && class_exists(InstalledVersions::class)
            && InstalledVersions::isInstalled('mage-os/module-shopping-feed')
        ) {
            $version = InstalledVersions::getPrettyVersion('mage-os/module-shopping-feed');
        }
        if ($version === null || $version === '') {
            $version = (string)$this->_moduleResource->getDbVersion($moduleName);
        }
        if (preg_match('/^\d/', $version)) {
            $version = 'v' . $version;
        }
        return $version;
    }
}
