<?php
/**
 * Copyright © Pixl Mods. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PixlMods\Popup\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    private const XML_PATH_ACTIVE = 'pixlmods_popup/general/active';
    private const XML_PATH_TRACKING_ENABLED = 'pixlmods_popup/tracking/enabled';
    private const XML_PATH_DAY_FREQUENCY_DAYS = 'pixlmods_popup/tracking/day_frequency_days';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * Whether popups should be rendered on the storefront at all.
     *
     * @param int|string|null $store
     * @return bool
     */
    public function isActive($store = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ACTIVE, ScopeInterface::SCOPE_STORE, $store);
    }

    /**
     * Whether view/close/conversion events should be recorded.
     *
     * @param int|string|null $store
     * @return bool
     */
    public function isTrackingEnabled($store = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_TRACKING_ENABLED, ScopeInterface::SCOPE_STORE, $store);
    }

    /**
     * Cookie lifetime, in days, for the "Once per Day" display frequency.
     *
     * @param int|string|null $store
     * @return int
     */
    public function getDayFrequencyLifetime($store = null): int
    {
        $days = (int) $this->scopeConfig->getValue(
            self::XML_PATH_DAY_FREQUENCY_DAYS,
            ScopeInterface::SCOPE_STORE,
            $store
        );

        return $days > 0 ? $days : 1;
    }
}
