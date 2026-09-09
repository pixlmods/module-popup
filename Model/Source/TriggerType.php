<?php
/**
 * Copyright © Pixl Mods. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PixlMods\Popup\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class TriggerType implements OptionSourceInterface
{
    public const ON_LOAD = 'on_load';
    public const EXIT_INTENT = 'exit_intent';
    public const SCROLL = 'scroll';
    public const TIME_ON_PAGE = 'time_on_page';
    public const CLICK_ELEMENT = 'click_element';

    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => self::ON_LOAD, 'label' => __('On Page Load')],
            ['value' => self::EXIT_INTENT, 'label' => __('Exit Intent')],
            ['value' => self::SCROLL, 'label' => __('Scroll Percentage')],
            ['value' => self::TIME_ON_PAGE, 'label' => __('Time on Page')],
            ['value' => self::CLICK_ELEMENT, 'label' => __('Click on Element')],
        ];
    }
}
