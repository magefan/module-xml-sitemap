<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\XmlSitemap\Model\Config\Backend;

use Magento\Framework\App\Config\Value;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface as StoreScopeInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;

class XmlSiteMapSettings extends Value
{
    const KEY_VALUE_RELATION = [
        'mfxmlsitemap/limit/max_lines' => 'sitemap/limit/max_lines',
        'mfxmlsitemap/limit/max_file_size' => 'sitemap/limit/max_file_size',
        'mfxmlsitemap/page/priority' => 'sitemap/page/priority',
        'mfxmlsitemap/page/changefreq' => 'sitemap/page/changefreq',
        'mfxmlsitemap/category/priority' => 'sitemap/category/priority',
        'mfxmlsitemap/category/changefreq' => 'sitemap/category/changefreq',
        'mfxmlsitemap/product/priority' => 'sitemap/product/priority',
        'mfxmlsitemap/product/changefreq' => 'sitemap/product/changefreq',
        'mfxmlsitemap/product/image_include' => 'sitemap/product/image_include',
        'mfxmlsitemap/store/priority' => 'sitemap/store/priority',
        'mfxmlsitemap/store/changefreq' => 'sitemap/store/changefreq',
        'mfxmlsitemap/generate/enabled' => 'sitemap/generate/enabled',
        'mfxmlsitemap/generate/error_email' => 'sitemap/generate/error_email',
        'mfxmlsitemap/generate/error_email_template' => 'sitemap/generate/error_email_template',
        'mfxmlsitemap/generate/error_email_identity' => 'sitemap/generate/error_email_identity',
        'mfxmlsitemap/generate/time' => 'sitemap/generate/time',
        'mfxmlsitemap/generate/frequency' => 'sitemap/generate/frequency',
        'mfxmlsitemap/search_engines/submission_robots' => 'sitemap/search_engines/submission_robots'
    ];

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param RequestInterface $request
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        RequestInterface $request,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->request = $request;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * @return void
     */
    public function afterLoad()
    {
        $scopeTypes = [
            StoreScopeInterface::SCOPE_WEBSITES,
            StoreScopeInterface::SCOPE_WEBSITE,
            StoreScopeInterface::SCOPE_STORES,
            StoreScopeInterface::SCOPE_STORE
        ];

        $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
        $scopeCode = null;

        foreach ($scopeTypes as $scope) {
            $param = $this->request->getParam($scope);
            if ($param) {
                $scopeType = $scope;
                $scopeCode = $param;
            }
        }

        $devSettingsValue = $this->_config->getValue(
            self::KEY_VALUE_RELATION[$this->getData('path')],
            $scopeType,
            $scopeCode
        );
        $this->setData('value', $devSettingsValue);
    }
}