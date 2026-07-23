<?php
/**
 * Copyright © Pixl Mods. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PixlMods\Popup\Controller\Adminhtml\Popup;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use PixlMods\Popup\Model\PopupFactory;
use PixlMods\Popup\Model\Source\FrontendPages;
use Psr\Log\LoggerInterface;

class Save extends Action implements HttpPostActionInterface
{
    public function __construct(
        Context $context,
        protected PopupFactory $popupFactory,
        protected LoggerInterface $logger
    ) {
        parent::__construct($context);
    }

    /**
     * Save Popup Item
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        $id = $this->getRequest()->getParam('entity_id');

        if (!$data) {
            $this->messageManager->addErrorMessage(__('Invalid data received.'));
            return $this->_redirect('*/*/');
        }

        try {
            $model = $this->popupFactory->create();

            if ($id) {
                $model->load($id);
                if (!$model->getId()) {
                    $this->messageManager->addErrorMessage(__('This popup no longer exists.'));
                    return $this->_redirect('*/*/');
                }
            }

            $data['pages'] = $this->prepareMultiselectValue(
                $data['pages'] ?? null,
                FrontendPages::ALL_PAGES_VALUE
            );

            $data['stores'] = $this->prepareMultiselectValue($data['stores'] ?? null);

            $data['customer_group_ids'] = $this->prepareMultiselectValue(
                $data['customer_group_ids'] ?? null
            );

            $data['start_date'] = $this->prepareDateValue($data['start_date'] ?? null);
            $data['end_date'] = $this->prepareDateValue($data['end_date'] ?? null);

            $model->setData($data);

            if (!$id) {
                $model->setId(null);
            }

            $model->save();

            $this->messageManager->addSuccessMessage(__('Popup saved successfully.'));
            return $this->_redirect('*/*/');
        } catch (\Exception $e) {
            $this->logger->error('Popup Save Error: ' . $e->getMessage(), ['exception' => $e]);
            $this->messageManager->addErrorMessage(__('Error saving the popup.'));
        }

        return $this->_redirect('*/*/');
    }

    /**
     * Converte o array vindo do multiselect em string separada por vírgula.
     *
     * Se um "valor coringa" (ex: FrontendPages::ALL_PAGES_VALUE) for informado
     * e estiver selecionado, ignora os demais valores e salva só ele.
     *
     * @param array|string|null $value
     * @param string|null $wildcardValue
     * @return string
     */
    private function prepareMultiselectValue($value, ?string $wildcardValue = null): string
    {
        if (!is_array($value) || empty($value)) {
            return '';
        }

        if ($wildcardValue !== null && in_array($wildcardValue, $value, true)) {
            return $wildcardValue;
        }

        return implode(',', $value);
    }

    /**
     * Normaliza campos de data: string vazia vira null,
     * pra não quebrar a coluna datetime nullable no banco.
     *
     * @param string|null $value
     * @return string|null
     */
    private function prepareDateValue(?string $value): ?string
    {
        $value = trim((string)$value);

        return $value === '' ? null : $value;
    }
}
