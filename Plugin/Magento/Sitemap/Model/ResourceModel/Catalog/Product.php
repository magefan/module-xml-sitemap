<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\XmlSitemap\Plugin\Magento\Sitemap\Model\ResourceModel\Catalog;

use Magento\Sitemap\Model\ResourceModel\Catalog\Product as Subject;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magefan\XmlSitemap\Model\Config;

class Product
{
    /**
     * @var ProductCollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param ProductCollectionFactory $productCollectionFactory
     * @param Config $config
     */
    public function __construct(
        ProductCollectionFactory $productCollectionFactory,
        Config $config
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->config = $config;
    }

    /**
     * @param Subject $subject
     * @param array $result
     * @return array
     */
    public function afterGetCollection(Subject $subject, array $result, $storeId): array
    {

        if ($result && $this->config->isEnabled()) {
            $productCollection = $this->productCollectionFactory->create()
                ->addFieldToFilter('mf_exclude_xml_sitemap', ['eq' => 1]);

            if ($this->config->getExcludeOutOfStock()) {
                $productCollection->getSelect()->joinLeft(
                    ['css' => $productCollection->getTable('cataloginventory_stock_item')],
                    'e.entity_id = css.product_id',
                    []
                )->orWhere('css.is_in_stock = 0')
                ->group('e.entity_id');

                $connection = $productCollection->getConnection();
                if ($connection->isTableExists('inventory_source_item')) {
                    $productCollection->getSelect()->joinLeft(
                        ['isi' => $productCollection->getTable('inventory_source_item')],
                        'e.sku = isi.sku',
                        []
                    )->orWhere('isi.status = 0');
                }
            }

            if ($productCollection) {
                $excludedIds = $productCollection->getAllIds();
                if ($excludedIds) {
                    $excludedIds = array_flip($excludedIds);
                    foreach ($result as $key => $item) {
                        if (isset($excludedIds[(int)$item->getId()])) {
                            unset($result[(int)$key]);
                        }
                    }
                }
            }
        }

        return $result;
    }
}
