<?php
declare(strict_types=1);

namespace MageOS\ShoppingFeed\Test\Integration;

use MageOS\ShoppingFeed\Model\Feed;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Block\Product\View\Description;
use Magento\Framework\View\LayoutInterface;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Theme\Block\Html\Title;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea frontend
 * @magentoAppIsolation enabled
 * @magentoDbIsolation enabled
 */
class MicrodataMarkupTest extends TestCase
{
    /** @magentoConfigFixture current_store mageos_shopping_feed/google/microdata_enabled 1 */
    public function testNativeTitleAndSkuRemainWhenThereIsNoReplacementFeed(): void
    {
        $this->assertMarkup(false);
    }

    /** @magentoConfigFixture current_store mageos_shopping_feed/google/microdata_enabled 1 */
    public function testNativeTitleAndSkuAreRemovedWhenAReplacementFeedIsSelected(): void
    {
        $om = Bootstrap::getObjectManager();
        $store = $om->get(StoreManagerInterface::class)->getStore();
        $om->create(Feed::class)->setType('google_shopping')->setStoreId($store->getId())
            ->setName('Microdata markup regression')->setStatus(0)->setUseMicrodata(1)
            ->setSchedules([])->setUploads([])->save();
        $this->assertMarkup(true);
    }

    private function assertMarkup(bool $replacement): void
    {
        $om = Bootstrap::getObjectManager();
        $layout = $om->create(LayoutInterface::class);
        $title = $layout->createBlock(Title::class, 'page.main.title')
            ->setAddBaseAttribute('itemprop="name"')->setTemplate('Magento_Theme::html/title.phtml');
        $title->setPageTitle('Native title');
        $product = $om->create(Product::class)->setSku('NATIVE-SKU');
        $registry = $om->get(Registry::class);
        $registry->unregister('product');
        $registry->register('product', $product);
        $sku = $layout->createBlock(Description::class, 'product.info.sku')
            ->setAtCall('getSku')->setAtCode('sku')->setAtLabel('none')->setAddAttribute('itemprop="sku"')
            ->setTemplate('Magento_Catalog::product/view/attribute.phtml');
        $titleHtml = $title->toHtml();
        $skuHtml = $sku->toHtml();
        self::assertStringContainsString('Native title', $titleHtml);
        self::assertStringContainsString('NATIVE-SKU', $skuHtml);
        if ($replacement) {
            self::assertStringNotContainsString('itemprop="name"', $titleHtml);
            self::assertStringNotContainsString('itemprop="sku"', $skuHtml);
        } else {
            self::assertStringContainsString('itemprop="name"', $titleHtml);
            self::assertStringContainsString('itemprop="sku"', $skuHtml);
        }
    }
}
