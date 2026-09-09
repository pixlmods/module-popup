<?php
/**
 * Copyright © Pixl Mods. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PixlMods\Popup\Model\ResourceModel\Popup\Report;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use PixlMods\Popup\Model\Popup as Model;
use PixlMods\Popup\Model\ResourceModel\Popup as ResourceModel;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'entity_id';

    protected function _construct()
    {
        $this->_init(Model::class, ResourceModel::class);
    }

    protected function _initSelect()
    {
        parent::_initSelect();

        $connection = $this->getConnection();

        $viewsExpr = $connection->getCheckSql("pe.event_type = 'view'", 1, 0);
        $closesExpr = $connection->getCheckSql("pe.event_type = 'close'", 1, 0);
        $conversionsExpr = $connection->getCheckSql("pe.event_type = 'conversion'", 1, 0);

        $this->getSelect()
            ->joinLeft(
                ['pe' => $this->getTable('pixlmods_popup_event')],
                'main_table.entity_id = pe.popup_id',
                []
            )
            ->columns([
                'views' => new \Zend_Db_Expr("SUM({$viewsExpr})"),
                'closes' => new \Zend_Db_Expr("SUM({$closesExpr})"),
                'conversions' => new \Zend_Db_Expr("SUM({$conversionsExpr})"),
                'conversion_rate' => new \Zend_Db_Expr(
                    "CASE WHEN SUM({$viewsExpr}) > 0
                     THEN ROUND(SUM({$conversionsExpr}) / SUM({$viewsExpr}) * 100, 2)
                     ELSE 0 END"
                ),
            ])
            ->group('main_table.entity_id');

        return $this;
    }
}
