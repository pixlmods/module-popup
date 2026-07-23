<?php
/**
 * Copyright © Pixl Mods. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PixlMods\Popup\Model;

use Magento\Framework\Model\AbstractModel;
use PixlMods\Popup\Model\ResourceModel\Popup as ResourceModel;

class Popup extends AbstractModel
{
    /**
     * Initialize the model with its resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(ResourceModel::class);
    }
}
