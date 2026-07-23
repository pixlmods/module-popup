<?php
/**
 * Copyright © Pixl Mods. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PixlMods\Popup\Block\Adminhtml\Edit;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use PixlMods\Popup\Model\PopupFactory;
use PixlMods\Popup\Model\ResourceModel\Popup as PopupResource;

class GenericButton
{
    public function __construct(
        protected PopupFactory $popupFactory,
        protected PopupResource $popupResource,
        protected UrlInterface $urlBuilder,
        protected RequestInterface $request
    ) {}

    /**
     * Return popup entity_id.
     *
     * @return int|null
     */
    public function getPopupId(): ?int
    {
        $popup = $this->popupFactory->create();
        $entityId = (int) $this->request->getParam('entity_id');

        if (!$entityId) {
            return null;
        }

        $this->popupResource->load($popup, $entityId);

        return $popup->getId() ? (int) $popup->getId() : null;
    }

    /**
     * Return current request entity_id.
     *
     * @return int|null
     */
    public function getEntityId(): ?int
    {
        $entityId = (int) $this->request->getParam('entity_id');
        return $entityId ?: null;
    }

    /**
     * Generate URL.
     */
    public function getUrl($route = '', array $params = []): string
    {
        return $this->urlBuilder->getUrl($route, $params);
    }
}
