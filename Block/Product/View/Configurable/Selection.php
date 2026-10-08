<?php

namespace MageOS\ShoppingFeed\Block\Product\View\Configurable;

use Magento\ConfigurableProduct\Model\ConfigurableAttributeData;
use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Framework\Pricing\PriceCurrencyInterface;

class Selection extends \Magento\ConfigurableProduct\Block\Product\View\Type\Configurable
{
    const GOOGLE_ADS_DESTINATION_ID_PATH = 'mageos_shopping_feed/google/google_ads_destination_id';
    const DYNAMIC_REMARKETING_ENABLED_PATH = 'mageos_shopping_feed/google/dynamic_remarketing_enabled';

    /**
     * @var \Magento\ConfigurableProduct\Model\Product\Type\Configurable
     */
    protected $configurableProductType;

    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\Stdlib\ArrayUtils $arrayUtils,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\ConfigurableProduct\Helper\Data $helper,
        \Magento\Catalog\Helper\Product $catalogProduct,
        CurrentCustomer $currentCustomer,
        PriceCurrencyInterface $priceCurrency,
        ConfigurableAttributeData $configurableAttributeData,
        \Magento\ConfigurableProduct\Model\Product\Type\Configurable $configurableProductType,
        array $data = [],
        ?\Magento\Framework\Locale\Format $localeFormat = null,
        ?\Magento\Customer\Model\Session $customerSession = null,
        ?\Magento\ConfigurableProduct\Model\Product\Type\Configurable\Variations\Prices $variationPrices = null
    ) {
        parent::__construct(
            $context,
            $arrayUtils,
            $jsonEncoder,
            $helper,
            $catalogProduct,
            $currentCustomer,
            $priceCurrency,
            $configurableAttributeData,
            $data,
            $localeFormat,
            $customerSession,
            $variationPrices
        );
        $this->configurableProductType = $configurableProductType;
    }

    /**
     * @return string
     */
    public function getGoogleAdsDestinationId()
    {
        return trim((string) $this->_scopeConfig->getValue(
            self::GOOGLE_ADS_DESTINATION_ID_PATH,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        ));
    }

    /**
     * @return bool
     */
    public function isDynamicRemarketingEnabled()
    {
        return (bool) $this->_scopeConfig->getValue(
            self::DYNAMIC_REMARKETING_ENABLED_PATH,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /** Encode the core configuration for an inline script without changing its object/array shape. */
    public function getScriptJsonConfig(): string
    {
        return json_encode(
            json_decode($this->getJsonConfig(), false, 512, JSON_THROW_ON_ERROR),
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR
        );
    }

    /**
     * @return array
     */
    public function getProducts()
    {
        if ($this->hasData('products')) {
            return $this->getData('products');
        }

        $product = $this->getProduct();
        $products = [];
        if ($product->getTypeId() === \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
            $products = $this->configurableProductType->getUsedProducts($product);
        }
        $this->setData('products', $products);

        return $products;
    }
}
