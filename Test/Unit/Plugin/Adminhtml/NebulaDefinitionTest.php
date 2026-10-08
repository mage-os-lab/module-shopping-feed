<?php
declare(strict_types=1);
namespace MageOS\ShoppingFeed\Test\Unit\Plugin\Adminhtml;

use MageOS\ShoppingFeed\Plugin\Adminhtml\NebulaDefinition;
use PHPUnit\Framework\TestCase;

class NebulaDefinitionTest extends TestCase
{
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
