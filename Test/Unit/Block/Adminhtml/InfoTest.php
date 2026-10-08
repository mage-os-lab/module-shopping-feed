<?php

declare(strict_types=1);

namespace MageOS\ShoppingFeed\Test\Unit\Block\Adminhtml;

use Composer\InstalledVersions;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Escaper;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\Module\ResourceInterface;
use MageOS\ShoppingFeed\Block\Adminhtml\Info;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class InfoTest extends TestCase
{
    /** @var array */
    private array $composerState;

    protected function setUp(): void
    {
        parent::setUp();
        InstalledVersions::getAllRawData();
        $this->composerState = (new \ReflectionClass(InstalledVersions::class))->getStaticProperties();

        // Keep the platform's installed package metadata out of the test fixtures.
        (new \ReflectionProperty(InstalledVersions::class, 'canGetVendors'))->setValue(null, false);
        $this->setInstalledPackage(null);
    }

    protected function tearDown(): void
    {
        foreach ($this->composerState as $property => $value) {
            (new \ReflectionProperty(InstalledVersions::class, $property))->setValue(null, $value);
        }
        parent::tearDown();
    }

    /** @dataProvider installedVersions */
    #[DataProvider('installedVersions')]
    public function testComposerVersionTakesPrecedenceOverSchemaVersion(string $version, string $label): void
    {
        $this->setInstalledPackage(['pretty_version' => $version]);

        self::assertSame($this->listHtml($label), $this->render(['MageOS_ShoppingFeed'], []));
    }

    public static function installedVersions(): array
    {
        return [
            'prefixed release' => ['v1.2.2', 'MageOS_ShoppingFeed v1.2.2'],
            'unprefixed release' => ['1.2.2', 'MageOS_ShoppingFeed v1.2.2'],
            'prerelease' => ['1.2.3-beta.1', 'MageOS_ShoppingFeed v1.2.3-beta.1'],
            'development branch' => ['dev-main', 'MageOS_ShoppingFeed dev-main'],
            'escaped branch' => ['dev-<script>', 'MageOS_ShoppingFeed dev-&lt;script&gt;'],
        ];
    }

    /** @dataProvider unavailableVersions */
    #[DataProvider('unavailableVersions')]
    public function testUnavailableComposerVersionFallsBackToSchemaVersion(?array $package): void
    {
        $this->setInstalledPackage($package);

        self::assertSame(
            $this->listHtml('MageOS_ShoppingFeed v1.0.0'),
            $this->render(['MageOS_ShoppingFeed'], ['MageOS_ShoppingFeed' => '1.0.0'])
        );
    }

    public static function unavailableVersions(): array
    {
        return [
            'source installation' => [null],
            'provided package' => [['provided' => ['*']]],
            'null pretty version' => [['pretty_version' => null]],
            'empty pretty version' => [['pretty_version' => '']],
        ];
    }

    public function testMissingVersionDoesNotRenderAnEmptyVersionSuffix(): void
    {
        self::assertSame(
            $this->listHtml('MageOS_ShoppingFeed'),
            $this->render(['MageOS_ShoppingFeed'], ['MageOS_ShoppingFeed' => false])
        );
    }

    public function testRelatedModulesKeepTheirOwnVersions(): void
    {
        $this->setInstalledPackage(['pretty_version' => 'v1.2.2']);

        self::assertSame(
            $this->listHtml('MageOS_ShoppingFeed v1.2.2', 'MageOS_ShoppingFeedAddon v0.4.0'),
            $this->render(
                ['Magento_Catalog', 'MageOS_ShoppingFeed', 'MageOS_ShoppingFeedAddon'],
                ['MageOS_ShoppingFeedAddon' => '0.4.0']
            )
        );
    }

    private function setInstalledPackage(?array $package): void
    {
        InstalledVersions::reload([
            'root' => [],
            'versions' => $package === null ? [] : ['mage-os/module-shopping-feed' => $package],
        ]);
    }

    private function render(array $modules, array $databaseVersions): string
    {
        $moduleList = $this->createMock(ModuleListInterface::class);
        $moduleList->expects(self::once())->method('getNames')->willReturn($modules);
        $moduleResource = $this->createMock(ResourceInterface::class);
        $moduleResource->expects(self::exactly(count($databaseVersions)))
            ->method('getDbVersion')
            ->willReturnCallback(static function (string $moduleName) use ($databaseVersions) {
                self::assertArrayHasKey($moduleName, $databaseVersions);
                return $databaseVersions[$moduleName];
            });

        $block = (new \ReflectionClass(Info::class))->newInstanceWithoutConstructor();
        foreach (['_moduleList' => $moduleList, '_moduleResource' => $moduleResource,
            '_escaper' => new Escaper()] as $property => $value) {
            (new \ReflectionProperty(Info::class, $property))->setValue($block, $value);
        }

        return (new \ReflectionMethod(Info::class, '_getElementHtml'))
            ->invoke($block, $this->createStub(AbstractElement::class));
    }

    private function listHtml(string ...$labels): string
    {
        return '<ul style="list-style-type: none; margin-top: 7px;"><li>'
            . implode('</li><li>', $labels) . '</li></ul>';
    }
}
