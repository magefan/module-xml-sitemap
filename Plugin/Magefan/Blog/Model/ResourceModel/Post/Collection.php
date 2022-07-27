<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\XmlSitemap\Plugin\Magefan\Blog\Model\ResourceModel\Post;

use Magefan\XmlSitemap\Model\Config;

class Collection
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @param Config $config
     */
    public function __construct(
        Config $config
    )
    {
        $this->config = $config;
    }

    public function beforeLoad($subject, $printQuery = false, $logQuery = false) {
        if ($this->config->isEnabled()) {
            $backTrace = \Magento\Framework\Debug::backtrace(true, true, false);

            if (false !== strpos($backTrace, 'Magento\Sitemap\Model\Sitemap')) {
                $subject->addFieldToFilter('mf_exclude_xml_sitemap', ['neq' => 1]);
            }
        }
    }
}