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
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Framework\View\Element\Template;
use Magento\Store\Model\StoreManagerInterface;
use PixlMods\Popup\Model\Config;
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
        protected readonly JsonSerializer $jsonSerializer,
        protected readonly Config $config,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Return all eligible popups (store/date/page/customer-group filters already applied),
     * ordered by priority (lower number = higher priority), with the trigger metadata
     * the frontend JS needs to decide which one to display and when.
     *
     * @return array
     */
    public function getPopupsData(): array
    {
        try {
            $storeId = (int) $this->storeManager
                ->getStore()
                ->getId();
        } catch (\Exception $e) {
            return [];
        }

        if (!$this->config->isActive($storeId)) {
            return [];
        }

        $collection = $this->popupCollectionFactory->create()
            ->addFieldToFilter('status', 1);

        $result = [];

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

            $result[] = [
                'id' => (int)$popup->getId(),
                'content' => $this->filterProvider->getPageFilter()->filter((string)$popup->getContent()),
                'trigger_type' => (string)$popup->getTriggerType(),
                'trigger_value' => (string)$popup->getTriggerValue(),
                'frequency' => (string)$popup->getFrequency(),
                'priority' => (int)$popup->getPriority(),
                'display_delay' => max(0, (int)$popup->getDisplayDelay()),
            ];
        }

        usort($result, static fn(array $a, array $b): int => $a['priority'] <=> $b['priority']);

        return $result;
    }

    /**
     * Popups data, safely JSON-encoded, ready to embed inside a
     * <script type="text/x-magento-init"> block as the widget's config.
     *
     * The popup HTML content is base64-encoded so it can never contain the
     * "</script>" sequence (the base64 alphabet has no "<" or ">" characters),
     * which would otherwise be able to prematurely close the script tag.
     */
    public function getPopupsDataJson(): string
    {
        $popups = array_map(
            static function (array $popup): array {
                $popup['content'] = base64_encode($popup['content']);
                return $popup;
            },
            $this->getPopupsData()
        );

        $json = $this->jsonSerializer->serialize($popups);

        return str_replace('</', '<\/', $json);
    }

    /**
     * Cookie lifetime, in days, for the "Once per Day" display frequency.
     *
     * @return int
     */
    public function getDayFrequencyLifetime(): int
    {
        return $this->config->getDayFrequencyLifetime();
    }

    /**
     * Converts a "a,b,c" string into an array, preserving values ​​like '0'
     * (e.g., group "NOT LOGGED IN" or store "All Store Views"), which
     * the standard array_filter() would remove because they are "falsy" in PHP.
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
     * '0' represents "All Store Views" (Magento's default for the store multiselect)
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
     * Rules:
     * - No start and no end -> always valid
     * - Start only -> valid from the start date/time, no end limit
     * - End only -> valid until the end date/time, no start limit
     * - Both -> valid within the range
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
     * The "pages" field can contain:
     * - FrontendPages::ALL_PAGES_VALUE -> displays on any page
     * - platform page layout handles (e.g., checkout_cart_index)
     * - CMS page identifiers (e.g., about-us)
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
     * Field is optional: if no group is selected in the admin,
     * the popup is displayed for all groups (guest, logged-in, VIP, etc.).
     * The "NOT LOGGED IN" group (ID 0) natively covers guests.
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
     * Covers both "normal" CMS pages (via the cms_page registry)
     * and the home page configured under Stores > Configuration > Web
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
