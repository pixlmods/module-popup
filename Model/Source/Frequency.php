<?php
/**
 * Copyright © Pixl Mods. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PixlMods\Popup\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Frequency implements OptionSourceInterface
{
    public const ALWAYS = 'always';
    public const SESSION = 'session';
    public const DAY = 'day';
    public const ONCE = 'once';

    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => self::ALWAYS, 'label' => __('Always')],
            ['value' => self::SESSION, 'label' => __('Once per Session')],
            ['value' => self::DAY, 'label' => __('Once per Day')],
            ['value' => self::ONCE, 'label' => __('Never Show Again After Closing')],
        ];
    }
}
