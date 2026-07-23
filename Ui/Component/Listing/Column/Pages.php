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
use PixlMods\Popup\Model\Source\FrontendPages;

class Pages extends Column
{
    /**
     * @var array
     */
    private array $pagesMap = [];

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        FrontendPages $frontendPages,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->pagesMap = $this->flattenOptions($frontendPages->toOptionArray());
    }

    /**
     * @inheritDoc
     */
    public function prepareDataSource(array $dataSource)
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        foreach ($dataSource['data']['items'] as &$item) {
            if (empty($item['pages']) || $item['pages'] === FrontendPages::ALL_PAGES_VALUE) {
                $item['pages'] = __('All Pages');
                continue;
            }

            $titles = [];

            foreach (explode(',', $item['pages']) as $pageValue) {
                $pageValue = trim($pageValue);

                if (isset($this->pagesMap[$pageValue])) {
                    $titles[] = $this->pagesMap[$pageValue];
                }
            }

            $item['pages'] = !empty($titles) ? implode(', ', $titles) : $item['pages'];
        }

        return $dataSource;
    }

    /**
     * Converts the options array (with optgroups) into a simple value => label map.
     *
     * @param array $options
     * @return array
     */
    private function flattenOptions(array $options): array
    {
        $map = [];

        foreach ($options as $option) {
            if (is_array($option['value'])) {
                foreach ($option['value'] as $subOption) {
                    $map[$subOption['value']] = (string)$subOption['label'];
                }
                continue;
            }

            $map[$option['value']] = (string)$option['label'];
        }

        return $map;
    }
}
