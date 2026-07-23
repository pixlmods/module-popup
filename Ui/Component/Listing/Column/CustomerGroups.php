<?php
/**
 * Copyright © Pixl Mods. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PixlMods\Popup\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use PixlMods\Popup\Model\Source\CustomerGroups as CustomerGroupsSource;

class CustomerGroups extends Column
{
    /**
     * @var array
     */
    private array $groupsMap = [];

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        CustomerGroupsSource $customerGroupsSource,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);

        foreach ($customerGroupsSource->toOptionArray() as $option) {
            $this->groupsMap[(string)$option['value']] = (string)$option['label'];
        }
    }

    /**
     * @inheritDoc
     */
    public function prepareDataSource(array $dataSource)
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        $fieldName = $this->getData('name');

        foreach ($dataSource['data']['items'] as &$item) {
            $titles = [];

            foreach (explode(',', $item[$fieldName]) as $groupId) {
                $groupId = trim($groupId);

                if (isset($this->groupsMap[$groupId])) {
                    $titles[] = $this->groupsMap[$groupId];
                }
            }

            $item[$fieldName] = !empty($titles) ? implode(', ', $titles) : $item[$fieldName];
        }

        return $dataSource;
    }
}
