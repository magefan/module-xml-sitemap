<?php

/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\XmlSitemap\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Serialize\SerializerInterface;

class Config
{
    /**
     * Extension enabled config path
     */
    const XML_PATH_EXTENSION_ENABLED = 'mfxmlsitemap/general/enabled';

    /**
     * Exclude out of stock config path
     */
    const XML_PATH_EXCLUDE_OUT_OF_STOCK = 'mfxmlsitemap/general/exclude_out_of_stock';

    /**
     * Additional links enabled config path
     */
    const XML_PATH_ADDITIONAL_LINKS_ENABLED = 'mfxmlsitemap/additional_links/enabled';

    /**
     * Additional links config path
     */
    const XML_PATH_ADDITIONAL_LINKS = 'mfxmlsitemap/additional_links/links';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * Config constructor.
     * @param SerializerInterface $serializer
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        SerializerInterface $serializer,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->serializer = $serializer;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Retrieve store config value
     * @param string $path
     * @param null $storeId
     * @return mixed
     */
    public function getConfig($path, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Retrieve true if module is enabled
     *
     * @param null $storeId
     * @return bool
     */
    public function isEnabled($storeId = null): bool
    {
        return (bool)$this->getConfig(
            self::XML_PATH_EXTENSION_ENABLED,
            $storeId
        );
    }

    /**
     * @param $storeId
     * @return bool
     */
    public function getExcludeOutOfStock($storeId = null): bool
    {
        return (bool)$this->getConfig(
            self::XML_PATH_EXCLUDE_OUT_OF_STOCK,
            $storeId
        );
    }

    /**
     * @param $storeId
     * @return bool
     */
    public function isAdditionalLinksEnabled($storeId = null): bool
    {
        return (bool)$this->getConfig(
            self::XML_PATH_ADDITIONAL_LINKS_ENABLED,
            $storeId
        );
    }

    /**
     * @param $storeId
     * @return array
     */
    public function getAdditionalLinks($storeId = null): array
    {
        $data = $this->getConfig(self::XML_PATH_ADDITIONAL_LINKS, $storeId);
        if ($data) {
            $additionalLinks = array_values($this->serializer->unserialize($data));
        } else {
            $additionalLinks = [];
        }
        return $additionalLinks;
    }
}
