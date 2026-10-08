<?php
declare(strict_types=1);
namespace MageOS\ShoppingFeed\Plugin\Adminhtml;

class NebulaDefinition
{
    public function __construct(private \Magento\Framework\AuthorizationInterface $authorization)
    {
    }

    public function afterGetDefinition($subject, array $result): array
    {
        if (($result['id'] ?? '') === 'mageos_shopping_feed_grid') {
            $result['settings']['massActions'] = array_values(array_filter(
                $result['settings']['massActions'] ?? [],
                fn (array $action): bool => !empty($action['acl']) && $this->authorization->isAllowed($action['acl'])
            ));
            foreach ($result['settings']['massActions'] as &$action) {
                $action['url'] = $subject->getUrl($action['url']);
            }
        }
        return $result;
    }

    public function beforeGetUrl($subject, $route = '', $params = []): array
    {
        if ($subject->getGridId() !== 'mageos_shopping_feed_grid') {
            return [$route, $params];
        }
        if ($route === '*/*/*') {
            // Nebula omits active column filters from pagination and sort links.
            // A supplied null remains the explicit clear-filters action.
            if (!array_key_exists('filters', $params)) {
                $params['filters'] = $subject->getActiveFilters();
            }
            // Nebula's controls update query parameters. Path parameters would take precedence.
            foreach (['sort', 'dir', 'page', 'pageSize', 'search', 'filters'] as $key) {
                if (array_key_exists($key, $params)) {
                    $params['_query'][$key] = $params[$key];
                    unset($params[$key]);
                }
            }
        }
        if ($route === 'nebula/grid/export') {
            foreach ($params as $key => $value) {
                if (preg_match('/^filters\[([a-z_]+)\]$/D', (string) $key, $match)) {
                    $params['_query']['filters'][$match[1]] = $value;
                    unset($params[$key]);
                }
            }
        }
        return [$route, $params];
    }
}
