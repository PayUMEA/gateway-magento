<?php
/**
 * Copyright © 2022 PayU Financial Services. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PayU\Gateway\Block\Adminhtml\System\Config\Fieldset;

use Magento\Backend\Block\Template;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;

/**
 * class Hint
 * @package PayU\Gateway\Block\Adminhtml\System\Config\Fieldset
 */
class Hint extends Template implements RendererInterface
{
    /**
     * @var string
     * @deprecated 100.1.0
     */
    protected $_template = 'PayU_Gateway::system/config/fieldset/hint.phtml';

    /**
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $html = '';

        if ($element->getComment()) {
            $html .= sprintf('<tr id="row_%s">', $element->getHtmlId());
            $html .= '<td colspan="1"><p class="note"><span>';
            $html .= sprintf(
                '<a href="%s" target="_blank">Configuration Details</a>',
                $element->getComment()
            );
            $html .= '</span></p></td></tr>';
        }

        return $html;
    }
}
