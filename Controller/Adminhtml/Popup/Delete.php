<?php
/**
 * Copyright © Pixl Mods. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PixlMods\Popup\Controller\Adminhtml\Popup;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use PixlMods\Popup\Model\PopupFactory;
use Magento\Framework\Exception\LocalizedException;

class Delete extends Action
{
    public function __construct(
        Context $context,
        protected readonly PopupFactory $popupFactory
    ) {
        parent::__construct($context);
    }

    /**
     * Delete Popup Item
     *
     * @return void
     */
    public function execute()
    {
        $entity_id = $this->getRequest()->getParam('entity_id');

        if ($entity_id) {
            try {
                $popup = $this->popupFactory->create();
                $popup->load($entity_id);

                if (!$popup->getId()) {
                    $this->messageManager->addErrorMessage(__('This popup no longer exists.'));
                    return $this->_redirect('*/*/');
                }

                $popup->delete();
                $this->messageManager->addSuccessMessage(__('Popup deleted successfully.'));
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }
        }

        return $this->_redirect('*/*/');
    }
}
