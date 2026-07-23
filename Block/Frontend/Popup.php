<?php
/**
 * Copyright © Pixl Mods. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PixlMods\Popup\Block\Frontend;

use Magento\Cms\Model\Template\FilterProvider;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Store\Model\StoreManagerInterface;
use PixlMods\Popup\Model\ResourceModel\Popup\CollectionFactory;
use PixlMods\Popup\Model\Source\FrontendPages;

class Popup extends Template
{
    public function __construct(
        Template\Context $context,
        protected readonly StoreManagerInterface $storeManager,
        protected readonly CollectionFactory $popupCollectionFactory,
        protected readonly FilterProvider $filterProvider,
        protected readonly RequestInterface $request,
        protected readonly Registry $registry,
        protected readonly ScopeConfigInterface $scopeConfig,
        protected readonly CustomerSession $customerSession,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Return popup content
     */
    public function getPopupContent(): ?string
    {
        try {
            $storeId = (int) $this->storeManager
                ->getStore()
                ->getId();
        } catch (\Exception $e) {
            return null;
        }

        $collection = $this->popupCollectionFactory->create()
            ->addFieldToFilter('status', 1);

        foreach ($collection as $popup) {
            if (!$this->isAllowedStore($popup, $storeId)) {
                continue;
            }

            if (!$this->isAllowedDate($popup)) {
                continue;
            }

            if (!$this->isAllowedPage($popup)) {
                continue;
            }

            if (!$this->isAllowedCustomerGroup($popup)) {
                continue;
            }

            return $this->filterProvider
                ->getPageFilter()
                ->filter($popup->getContent());
        }

        return null;
    }

    /**
     * Converte uma string "a,b,c" em array, preservando valores como '0'
     * (ex: grupo "NOT LOGGED IN" ou store "All Store Views"), que o
     * array_filter() padrão removeria por serem "falsy" em PHP.
     *
     * @param string|null $value
     * @return string[]
     */
    private function explodeToArray(?string $value): array
    {
        if ($value === null || trim($value) === '') {
            return [];
        }

        $items = array_map('trim', explode(',', $value));

        return array_values(array_filter($items, static fn(string $item): bool => $item !== ''));
    }

    /**
     * Validate store
     *
     * '0' representa "Todos os Store Views" (padrão do multiselect de store do Magento)
     */
    private function isAllowedStore($popup, int $storeId): bool
    {
        $stores = $this->explodeToArray((string)$popup->getStores());

        if (empty($stores)) {
            return false;
        }

        return in_array('0', $stores, true)
            || in_array((string)$storeId, $stores, true);
    }

    /**
     * Validate dates
     *
     * Regras:
     * - Sem start e sem end -> sempre válido
     * - Só start -> válido a partir da data/hora de início, sem limite final
     * - Só end -> válido até a data/hora final, sem limite inicial
     * - Ambos -> válido dentro do intervalo
     */
    private function isAllowedDate($popup): bool
    {
        $startDate = $popup->getStartDate();
        $endDate = $popup->getEndDate();

        if (!$startDate && !$endDate) {
            return true;
        }

        $now = new \DateTimeImmutable();

        if ($startDate) {
            $start = new \DateTimeImmutable((string)$startDate);

            if ($now < $start) {
                return false;
            }
        }

        if ($endDate) {
            $end = new \DateTimeImmutable((string)$endDate);

            // Se foi salva apenas a data (sem horário), considera válido até o fim do dia
            if ($end->format('H:i:s') === '00:00:00') {
                $end = $end->setTime(23, 59, 59);
            }

            if ($now > $end) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate page
     *
     * O campo "pages" pode conter:
     * - FrontendPages::ALL_PAGES_VALUE -> exibe em qualquer página
     * - layout handles de páginas de plataforma (ex: checkout_cart_index)
     * - identifiers de páginas CMS (ex: sobre-nos)
     */
    private function isAllowedPage($popup): bool
    {
        $pages = $this->explodeToArray((string)$popup->getPages());

        if (empty($pages)) {
            return false;
        }

        if (in_array(FrontendPages::ALL_PAGES_VALUE, $pages, true)) {
            return true;
        }

        $currentHandle = $this->request->getFullActionName();

        if (in_array($currentHandle, $pages, true)) {
            return true;
        }

        $cmsIdentifier = $this->getCurrentCmsPageIdentifier();

        if ($cmsIdentifier !== null && in_array($cmsIdentifier, $pages, true)) {
            return true;
        }

        return false;
    }

    /**
     * Validate customer group
     *
     * Campo é opcional: se nenhum grupo for selecionado no admin,
     * o popup é exibido para todos os grupos (visitante, logado, VIP, etc).
     * O grupo "NOT LOGGED IN" (id 0) já cobre o visitante nativamente.
     */
    private function isAllowedCustomerGroup($popup): bool
    {
        $groups = $this->explodeToArray((string)$popup->getCustomerGroupIds());

        if (empty($groups)) {
            return true;
        }

        $currentGroupId = (string)$this->customerSession->getCustomerGroupId();

        return in_array($currentGroupId, $groups, true);
    }

    /**
     * Get current CMS page identifier
     *
     * Cobre tanto páginas CMS "normais" (via registry cms_page)
     * quanto a home page configurada em Stores > Configuration > Web
     */
    private function getCurrentCmsPageIdentifier(): ?string
    {
        $cmsPage = $this->registry->registry('cms_page');

        if ($cmsPage) {
            return $cmsPage->getIdentifier();
        }

        if ($this->request->getFullActionName() === 'cms_index_index') {
            $identifier = $this->scopeConfig->getValue('web/default/cms_home_page');

            if ($identifier) {
                return (string)$identifier;
            }
        }

        return null;
    }
}
