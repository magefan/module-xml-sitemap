<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\XmlSitemap\Block\Adminhtml\System\Config\Form;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magefan\XmlSitemap\Block\Adminhtml\System\Config\Form\Field\FrequencyColumn;
use Magento\Framework\DataObject;
/**
 * Class DynamicRow
 */
class DynamicRow extends AbstractFieldArray
{
    /**
     * @var FrequencyColumn
     */
    private $frequencyRenderer;

    /**
     * Prepare rendering the new field by adding all the needed columns
     */
    protected function _prepareToRender()
    {
        $this->addColumn('url', [
            'style' => 'width:170px',
            'label' => __('URL'),
            'class' => 'required-entry'
        ]);
        $this->addColumn('frequency', [
            'label' => __('Frequency'),
            'class' => 'required-entry',
            'style' => 'width:200px',
            'renderer' => $this->getFrequencyRenderer()
        ]);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }

    protected function _prepareArrayRow(DataObject $row): void
    {
        $options = [];

        $frequency = $row->getFrequency();
        if ($frequency !== null) {
            $options['option_' . $this->getFrequencyRenderer()->calcOptionHash($frequency)] = 'selected="selected"';
        }

        $row->setData('option_extra_attrs', $options);
    }

    /**
     * @return FrequencyColumn
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getFrequencyRenderer()
    {
        if (!$this->frequencyRenderer) {
            $this->frequencyRenderer = $this->getLayout()->createBlock(
                FrequencyColumn::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->frequencyRenderer;
    }
    
    /**
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $html = parent::_getElementHtml($element);
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $secureHtmlRenderer = $objectManager->get(\Magefan\Community\Api\SecureHtmlRendererInterface::class);
        $script = '
                document.addEventListener("DOMContentLoaded", function(event) {
                    require([
                        \'jquery\',
                        \'Magento_Theme/js/sortable\'
                    ], function ($) {
                        setTimeout(function () {
                            $(\'#mfxmlsitemap_additional_links_links\').sortable({
                                containment: "parent",
                                items: \'tr\',
                                tolerance: \'pointer\',
                            });
                        }, 1000);
                    });
                });
            ';
        $html .= $secureHtmlRenderer->renderTag('script', [], $script, false);
        return $html;
    }
}
