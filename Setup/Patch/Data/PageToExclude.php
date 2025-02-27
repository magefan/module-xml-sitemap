<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\XmlSitemap\Setup\Patch\Data;

use Magento\Cms\Model\ResourceModel\Page\CollectionFactory as PageCollectionFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;

class PageToExclude implements DataPatchInterface, PatchRevertableInterface
{

    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var PageCollectionFactory
     */
    private $pageCollectionFactory;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param PageCollectionFactory $pageCollectionFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        PageCollectionFactory   $pageCollectionFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->pageCollectionFactory = $pageCollectionFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $pageCollection = $this->pageCollectionFactory->create();
        $connection = $pageCollection->getConnection();
        $connection->update(
            $pageCollection->getTable('cms_page'),
            ['mf_exclude_xml_sitemap' => 1],
            ['identifier IN (?)' => ['no-route', 'home', 'enable-cookies']]
        );

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * Revert function
     */
    public function revert()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }
}
