<?php
/**
 * Copyright © Pixl Mods. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PixlMods\Popup\Controller\Adminhtml\Popup;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Registry;
use PixlMods\Popup\Model\PopupFactory;

class Edit extends Action
{
    public function __construct(
        Context $context,
        protected readonly PageFactory $resultPageFactory,
        protected readonly Registry $coreRegistry,
        protected readonly PopupFactory $popupFactory
    ) {
        parent::__construct($context);
    }

    /**
     * Edit Popup Item
     *
     * @return void
     */
    public function execute()
    {
        $entity_id = (int) $this->getRequest()->getParam('entity_id');
        $popup = $this->popupFactory->create();

        if ($entity_id && !$popup->load($entity_id)->getId()) {
            $this->messageManager->addErrorMessage(__('This popup no longer exists.'));
            return $this->_redirect('*/*/');
        }

        $this->coreRegistry->register('pixlmods_popup', $popup);

        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend(
            $popup->getId() ? __("Edit Popup '%1'", $popup->getTitle()) : __('Add New Popup')
        );

        return $resultPage;
    }
}
