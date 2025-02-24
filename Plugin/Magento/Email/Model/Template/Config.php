<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\XmlSitemap\Plugin\Magento\Email\Model\Template;

/* Fix for errro that email does not exists */
class Config
{

    public function beforeGetThemeTemplates(
        \Magento\Email\Model\Template\Config $subject,
                                             $templateId
    ): array {
        if ('mfxmlsitemap_generate_error_email_template' === $templateId) {
            return ['sitemap_generate_error_email_template'];
        }
        return [$templateId];
    }

    public function beforeParseTemplateIdParts(
        \Magento\Email\Model\Template\Config $subject,
                                             $templateId
    ): array {
        if ('mfxmlsitemap_generate_error_email_template' === $templateId) {
            return ['sitemap_generate_error_email_template'];
        }
        return [$templateId];
    }

    public function beforeGetTemplateLabel(
        \Magento\Email\Model\Template\Config $subject,
                                             $templateId
    ): array {
        if ('mfxmlsitemap_generate_error_email_template' === $templateId) {
            return ['sitemap_generate_error_email_template'];
        }
        return [$templateId];
    }

    public function beforeGetTemplateType(
        \Magento\Email\Model\Template\Config $subject,
                                             $templateId
    ): array {
        if ('mfxmlsitemap_generate_error_email_template' === $templateId) {
            return ['sitemap_generate_error_email_template'];
        }
        return [$templateId];
    }

    public function beforeGetTemplateModule(
        \Magento\Email\Model\Template\Config $subject,
                                             $templateId
    ): array {
        if ('mfxmlsitemap_generate_error_email_template' === $templateId) {
            return ['sitemap_generate_error_email_template'];
        }
        return [$templateId];
    }

    public function beforeGetTemplateArea(
        \Magento\Email\Model\Template\Config $subject,
                                             $templateId
    ): array {
        if ('mfxmlsitemap_generate_error_email_template' === $templateId) {
            return ['sitemap_generate_error_email_template'];
        }
        return [$templateId];
    }

    public function beforeGetTemplateFilename(
        \Magento\Email\Model\Template\Config $subject,
                                             $templateId,
                                             $designParams = []
    ): array {
        if ('mfxmlsitemap_generate_error_email_template' === $templateId) {
            return ['sitemap_generate_error_email_template', $designParams];
        }
        return [$templateId, $designParams];
    }
}
