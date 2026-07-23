<?php
/**
 * Copyright © Pixl Mods. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PixlMods\Popup\Block\Adminhtml\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use PixlMods\Popup\Ui\Component\Listing\Column\Actions;
use PixlMods\Popup\Block\Adminhtml\Edit\GenericButton;

class DeleteButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * @inheritdoc
     *
     * @return array
     */
    public function getButtonData()
    {
        $data = [];
        if ($this->getPopupId()) {
            $data = [
                'label' => __('Delete'),
                'on_click' => '',
                'data_attribute' => [
                    'mage-init' => [
                        'Magento_Ui/js/form/button-adapter' => [
                            'actions' => [
                                [
                                    'targetName' => 'pixlmods_popup_form.pixlmods_popup_form',
                                    'actionName' => 'deletePopup',
                                    'params' => [
                                        $this->getDeleteUrl(),
                                    ]
                                ]
                            ],
                        ],
                    ],
                ],
                'sort_order' => 20
            ];
        }
        return $data;
    }

    /**
     * Get delete button url.
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getDeleteUrl(): string
    {
        $entityId = $this->getEntityId();
        return $this->getUrl(Actions::POPUP_PATH_DELETE, ['entity_id' => $entityId]);
    }
}
