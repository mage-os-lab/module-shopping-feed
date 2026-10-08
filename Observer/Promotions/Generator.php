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

namespace MageOS\ShoppingFeed\Observer\Promotions;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class Generator implements ObserverInterface
{
    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\SalesRule\Model\RuleFactory
     */
    protected $ruleFactory;

    /**
     * @var Provider
     */
    protected $provider;

    /**
     * @var \MageOS\ShoppingFeed\Model\Promotions\Provider\Map
     */
    protected $map;

    /**
     * @var \MageOS\ShoppingFeed\Model\Promotions\Provider\Collection
     */
    protected $promotionsCollection;

    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     */
    protected $fileDriver;


    public function __construct(
        \Magento\Framework\Json\Helper\Data $helper,
        \MageOS\ShoppingFeed\Model\Promotions\Provider $provider,
        \MageOS\ShoppingFeed\Model\Promotions\Provider\Map $map,
        \MageOS\ShoppingFeed\Model\Promotions\Provider\Collection $promotionsCollection,
        \Magento\SalesRule\Model\RuleFactory $ruleFactory,
        \Magento\Framework\Filesystem\Driver\File $fileDriver
    ) {

        $this->helper = $helper;
        $this->provider = $provider;
        $this->map = $map;
        $this->ruleFactory = $ruleFactory;
        $this->promotionsCollection = $promotionsCollection;
        $this->fileDriver = $fileDriver;
    }

    /**
     * @param EventObserver $observer
     */
    public function execute(EventObserver $observer)
    {
        /** @var \MageOS\ShoppingFeed\Model\Generator $generator */
        $generator = $observer->getEvent()->getData('generator');
        if ($generator->isTestMode()) {
            return $this;
        }

        $feed = $observer->getEvent()->getData('feed');
        $generator->getLogger()->info(sprintf('START GOOGLE_PROMOTIONS FEED #%s', $feed->getId()));
        if (!$feed->getConfig('promotions_enabled')) {
            $generator->getLogger()->info('Promotions disabled!');
            return $this;
        }

        // Adjust promotions data to cart rules data.
        $this->provider->setFeed($feed);
        $this->provider->validateGooglePromotions();

        $config = $feed->getConfig('promotions_provider_widget');
        $config = is_array($config) ? $config : [];
        $promotion = is_array($config['promotion'] ?? null) ? $config['promotion'] : [];
        $counter = (int) ($config['counter'] ?? 0);
        $hashString = $counter . $this->helper->jsonEncode($promotion);
        $hash = hash('sha256', $hashString);
        $file = $this->provider->getPromotionFile();

        $fileLines = [];
        if (!isset($config['hash']) || $config['hash'] != $hash || !$this->fileDriver->isExists($file)) {
            $this->provider->clearPromotionCache();
            $this->map->setProvider($this->provider);

            $fileLines[] = $this->createFeedHeader();
            foreach ($promotion as $key => $row) {
                if (is_array($row) && !empty($row['include'])) {
                    /** @var \Magento\SalesRule\Model\Rule $rule */
                    $rule = $this->ruleFactory->create()->load($key);
                    $fileLines[] = $this->createFeedLine($counter, $rule, $this->map, $row);
                }
            }
        }

        $fileOutput = '';
        foreach ($fileLines as $line) {
            $fileOutput .= implode("\t", $line) . "\n";
        }

        if (!empty($fileOutput)) {
            $this->writePromotionFile($file, $fileOutput);

            $linesCount = count($fileLines) > 0 ? count($fileLines) - 1 : 0;
            if ($linesCount) {
                // Save the new hash so we don't regenerate the feed if no changes were made.
                $config['hash'] = $hash;
                $feed = $this->promotionsCollection->updatePromotionsData($feed, $config);
                // Update the provider feed to take new config
                $this->provider->setFeed($feed);
                // Notify ftp uploads
                $this->provider->setFileCreated();
            }

            $generator->getLogger()->info(sprintf('Added %d lines | in %s', $linesCount, $file));
            $feed->saveMessages([
                'promotion_file' => $this->provider->getPromotionFile(),
                'promotion_added' => $linesCount
            ]);
        } else {
            $generator->getLogger()->info(sprintf('No changes | in %s', $file));
        }
        return $this;
    }

    /**
     * Replace a promotion feed only after the complete new file has been written.
     *
     * @param string $file
     * @param string $contents
     * @return void
     */
    protected function writePromotionFile($file, $contents)
    {
        $temporaryFile = $file . '.tmp';
        if ($this->fileDriver->isExists($temporaryFile)) {
            $this->fileDriver->deleteFile($temporaryFile);
        }

        try {
            $this->fileDriver->filePutContents($temporaryFile, $contents);
            $this->fileDriver->rename($temporaryFile, $file);
        } catch (\Throwable $exception) {
            if ($this->fileDriver->isExists($temporaryFile)) {
                $this->fileDriver->deleteFile($temporaryFile);
            }
            throw $exception;
        }
    }

    /**
     * Creates feed header
     *
     * @return array
     */
    protected function createFeedHeader()
    {
        return [
            'promotion_id',
            'product_applicability',
            'long_title',
            'promotion_effective_dates',
            'promotion_display_dates',
            'redemption_channel',
            'promotion_destination',
            'promotion_destination',
            'offer_type',
            'generic_redemption_code',
            'minimum_purchase_amount'
        ];
    }

    /**
     * Create feed line
     *
     * @param $counter
     * @param \Magento\SalesRule\Model\Rule $rule
     * @param array $row
     * @param \MageOS\ShoppingFeed\Model\Promotions\Provider\Map $map
     * @return array
     */
    protected function createFeedLine($counter, \Magento\SalesRule\Model\Rule $rule, $map, $row = [])
    {
        return [
            $map->mapPromotionId($counter, $rule),
            $map->mapProductApplicability($rule),
            trim(preg_replace('/[\t\r\n ]+/', ' ', (string)($row['title'] ?? $rule->getName()))),
            $map->mapEffectiveDates($row),
            $map->mapDisplayDates($row),
            'online',
            'shopping_ads',
            'free_listings',
            $map->mapOfferType($rule),
            $map->mapGenericRedemptionCode($rule),
            $map->mapMinimumPurchaseAmount($rule)
        ];
    }
}
