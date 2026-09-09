<?php
/**
 * Copyright © Pixl Mods. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PixlMods\Popup\Block\Adminhtml\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use PixlMods\Popup\Block\Adminhtml\Edit\GenericButton;

class SaveAndContinueButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * @inheritdoc
     *
     * @return array
     */
    public function getButtonData()
    {
        return [
            'label' => __('Save and Continue Edit'),
            'class' => 'save',
            'data_attribute' => [
                'mage-init' => [
                    'button' => ['event' => 'saveAndContinueEdit']
                ],
                'form-role' => 'save'
            ],
            'sort_order' => 40,
        ];
    }
}
