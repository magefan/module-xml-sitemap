<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\XmlSitemap\Model\ItemProvider;

use Magefan\XmlSitemap\Model\Config;
use Magento\Sitemap\Model\ItemProvider\ItemProviderInterface;
use Magento\Sitemap\Model\SitemapItemInterfaceFactory;

class AdditionalLinks implements ItemProviderInterface
{
    /**
     * Sitemap item factory
     *
     * @var SitemapItemInterfaceFactory
     */
    private $itemFactory;

    /**
     * @var Config
     */
    private $config;

    /**
     * @param SitemapItemInterfaceFactory $itemFactory
     * @param Config $config
     */
    public function __construct(
        SitemapItemInterfaceFactory $itemFactory,
        Config $config
    ) {
        $this->itemFactory = $itemFactory;
        $this->config = $config;
    }

    /**
     * @inheritdoc
     */
    public function getItems($storeId)
    {
        $items = [];
        if ($this->config->isEnabled($storeId) && $this->config->isAdditionalLinksEnabled($storeId)) {
            foreach ($this->config->getAdditionalLinks($storeId) as $key => $link) {
                if (isset($link['path']) && isset($link['frequency'])) {
                    $items[] = $this->itemFactory->create([
                        'url' => $link['path'],
                        'priority' => isset($link['priority']) ? $link['priority'] : 0.5,
                        'changeFrequency' => $link['frequency'],
                    ]);
                }
            }
        }

        return $items;
    }
}
