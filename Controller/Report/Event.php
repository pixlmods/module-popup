<?php
/**
 * Copyright © Pixl Mods. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PixlMods\Popup\Controller\Report;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Customer\Model\SessionFactory as CustomerSessionFactory;
use Magento\Store\Model\StoreManagerInterface;
use PixlMods\Popup\Model\Config;
use Psr\Log\LoggerInterface;

class Event extends Action implements HttpPostActionInterface, CsrfAwareActionInterface
{
    /**
     * Event types accepted from the frontend tracker (view/js/actions/send-event.js).
     */
    private const ALLOWED_EVENT_TYPES = ['view', 'close', 'conversion'];

    public function __construct(
        Context $context,
        private JsonFactory $resultJsonFactory,
        private ResourceConnection $resource,
        private CustomerSessionFactory $customerSessionFactory,
        private StoreManagerInterface $storeManager,
        private LoggerInterface $logger,
        private Config $config
    ) {
        parent::__construct($context);
    }

    /**
     * Execute the event reporting logic
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $storeId = (int) $this->storeManager->getStore()->getId();

        if (!$this->config->isTrackingEnabled($storeId)) {
            return $result->setData(['success' => true]);
        }

        $popupId = (int) $this->getRequest()->getParam('popup_id');
        $eventType = (string) $this->getRequest()->getParam('event_type');

        if (!$popupId || !in_array($eventType, self::ALLOWED_EVENT_TYPES, true)) {
            return $result->setHttpResponseCode(400)->setData(['success' => false]);
        }

        try {
            $connection = $this->resource->getConnection();

            $connection->insert(
                $connection->getTableName('pixlmods_popup_event'),
                [
                    'popup_id' => $popupId,
                    'event_type' => $eventType,
                    'session_id' => session_id(),
                    'customer_id' => $this->customerSessionFactory->create()->getCustomerId() ?: null,
                    'store_id' => $storeId,
                    'page_url' => $_SERVER['REQUEST_URI'] ?? null
                ]
            );
        } catch (\Exception $e) {
            $this->logger->error('Popup Event Tracking Error: ' . $e->getMessage(), ['exception' => $e]);
            return $result->setHttpResponseCode(500)->setData(['success' => false]);
        }

        return $result->setData([
            'success' => true
        ]);
    }

    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
