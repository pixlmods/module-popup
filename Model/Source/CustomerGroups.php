<?php
/**
 * Copyright © Pixl Mods. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PixlMods\Popup\Model\Source;

use Magento\Customer\Model\ResourceModel\Group\CollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;

class CustomerGroups implements OptionSourceInterface
{
    public function __construct(
        private CollectionFactory $groupCollectionFactory
    ) {}

    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        $collection = $this->groupCollectionFactory->create();
        return $collection->toOptionArray();
    }
}
