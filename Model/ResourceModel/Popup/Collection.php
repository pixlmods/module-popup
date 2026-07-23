<?php
/**
 * Copyright © Pixl Mods. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PixlMods\Popup\Model\ResourceModel\Popup;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use PixlMods\Popup\Model\Popup as Model;
use PixlMods\Popup\Model\ResourceModel\Popup as ResourceModel;

class Collection extends AbstractCollection
{
    /**
     * Initialize the collection model and resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(Model::class, ResourceModel::class);
    }
}
