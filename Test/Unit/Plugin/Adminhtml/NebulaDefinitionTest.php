<?php
declare(strict_types=1);
namespace MageOS\ShoppingFeed\Test\Unit\Plugin\Adminhtml;

use MageOS\ShoppingFeed\Plugin\Adminhtml\NebulaDefinition;
use PHPUnit\Framework\TestCase;

class NebulaDefinitionTest extends TestCase
{
    public function testGridNavigationPreservesActiveColumnFilters(): void
    {
        $subject = new class {
            public function getGridId(): string
            {
                return 'mageos_shopping_feed_grid';
            }

            public function getActiveFilters(): array
            {
                return ['name' => 'Native page fixture', 'status' => '0'];
            }
        };
        $plugin = new NebulaDefinition($this->createStub(\Magento\Framework\AuthorizationInterface::class));
        foreach ([['page' => 2], ['sort' => 'name', 'dir' => 'asc'], ['pageSize' => 50]] as $navigation) {
            [$route, $params] = $plugin->beforeGetUrl($subject, '*/*/*', $navigation);
            self::assertSame('*/*/*', $route);
            self::assertSame($subject->getActiveFilters(), $params['_query']['filters'] ?? null);
            foreach ($navigation as $key => $value) {
                self::assertSame($value, $params['_query'][$key]);
            }
        }
    }

    public function testGridNavigationRetainsExplicitFilterOverrides(): void
    {
        $subject = new class {
            public function getGridId(): string
            {
                return 'mageos_shopping_feed_grid';
            }

            public function getActiveFilters(): array
            {
                throw new \LogicException('Explicit filters must not be replaced.');
            }
        };
        $plugin = new NebulaDefinition($this->createStub(\Magento\Framework\AuthorizationInterface::class));
        foreach ([null, [], ['name' => 'Replacement']] as $filters) {
            [, $params] = $plugin->beforeGetUrl($subject, '*/*/*', ['filters' => $filters]);
            self::assertSame($filters, $params['_query']['filters']);
        }
    }

    public function testThirdPartyMassActionWithoutAclIsOmitted(): void
    {
        $authorization = $this->createMock(\Magento\Framework\AuthorizationInterface::class);
        $authorization->expects(self::never())->method('isAllowed');
        $subject = $this->createMock(\Magento\Framework\View\Element\Template::class);
        $subject->expects(self::never())->method('getUrl');
        $definition = ['id' => 'mageos_shopping_feed_grid', 'settings' => ['massActions' => [
            ['label' => 'Unscoped', 'url' => 'thirdparty/action'],
            ['label' => 'Empty ACL', 'url' => 'thirdparty/action', 'acl' => ''],
        ]]];
        $result = (new NebulaDefinition($authorization))->afterGetDefinition($subject, $definition);
        self::assertSame([], $result['settings']['massActions']);
    }

    public function testMassActionsRespectPermissionsAndHaveResolvedUrls(): void
    {
        $auth=$this->createMock(\Magento\Framework\AuthorizationInterface::class);
        $auth->expects(self::exactly(2))->method('isAllowed')->willReturnCallback(
            static fn($resource): bool => $resource === 'MageOS_ShoppingFeed::save'
        );
        $subject=$this->createMock(\Magento\Framework\View\Element\Template::class);
        $subject->expects(self::once())->method('getUrl')->with('mageos_shopping_feed/feed/massClone')
            ->willReturn('https://local.test/admin/mageos_shopping_feed/feed/massClone/key/example/');
        $definition=['id'=>'mageos_shopping_feed_grid','settings'=>['massActions'=>[
            ['label'=>'Clone','url'=>'mageos_shopping_feed/feed/massClone','acl'=>'MageOS_ShoppingFeed::save'],
            ['label'=>'Delete','url'=>'mageos_shopping_feed/feed/massDelete','acl'=>'MageOS_ShoppingFeed::delete'],
        ]]];
        $result=(new NebulaDefinition($auth))->afterGetDefinition($subject,$definition);
        self::assertCount(1,$result['settings']['massActions']);
        self::assertSame('https://local.test/admin/mageos_shopping_feed/feed/massClone/key/example/',$result['settings']['massActions'][0]['url']);
    }
}
