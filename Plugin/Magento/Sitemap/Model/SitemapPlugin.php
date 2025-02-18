<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\XmlSitemap\Plugin\Magento\Sitemap\Model;

use Magefan\XmlSitemap\Model\Config;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Sitemap\Model\SitemapFactory;

class SitemapPlugin
{
    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var SitemapFactory
     */
    private $sitemapFactory;

    /**
     * @var Config
     */
    private $config;

    /**
     * @param DirectoryList $directoryList
     * @param SitemapFactory $sitemapFactory
     * @param Config $config
     */
    public function __construct(
        DirectoryList $directoryList,
        SitemapFactory $sitemapFactory,
        Config $config
    )
    {
        $this->directoryList = $directoryList;
        $this->sitemapFactory = $sitemapFactory;
        $this->config = $config;
    }

    /**
     * @param \Magento\Sitemap\Model\Sitemap $subject
     * @param $result
     * @return mixed
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function afterGenerateXml(\Magento\Sitemap\Model\Sitemap $subject, $result)
    {
        if ($this->config->isEnabled() && $this->config->getDisableUrlsWithSpecificCharacters()) {
            $path = $this->sitemapFactory->create()->setStoreId($result->getStoreId())->getSitemapUrl($result->getSitemapPath(), $result->getSitemapFilename());
            $xml = simplexml_load_file($path);

            if ($xml->sitemap) {
                foreach ($xml->sitemap as $sitemap) {
                    $individualXml = simplexml_load_file((string)$sitemap->loc[0]);
                    $position = strpos((string)$sitemap->loc[0], $result->getSitemapPath());
                    if ($position !== false) {
                        $filePath = substr((string)$sitemap->loc[0], $position);
                        $this->removeUrlsWithSpecificCharacters($individualXml, $filePath);
                    }
                }
            } else {
                $this->removeUrlsWithSpecificCharacters($xml, $result->getSitemapPath() . $result->getSitemapFilename());
            }
        }
        return $result;
    }

    private function removeUrlsWithSpecificCharacters($xmlUrls, $sitemapPath) {

        foreach ($xmlUrls->url as $xmlUrl) {
            if (preg_match('/[?#<>@!&*()$%^\\+=,{}"\']/', (string)$xmlUrl->loc)) {
                $dom = dom_import_simplexml($xmlUrl);
                $dom->parentNode->removeChild($dom);
            }
        }
        $filePath = $this->directoryList->getPath(DirectoryList::PUB) . $sitemapPath;
        $xmlUrls->asXML($filePath);
    }
}
