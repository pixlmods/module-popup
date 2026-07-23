<?php
/**
 * Copyright © Pixl Mods. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PixlMods\Popup\Model\Source;

use Magento\Cms\Model\ResourceModel\Page\CollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;

class FrontendPages implements OptionSourceInterface
{
    /**
     * @var string
     */
    public const ALL_PAGES_VALUE = 'all_pages';

    public function __construct(
        private CollectionFactory $pageCollectionFactory
    ) {}

    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        $options = [];

        $options[] = [
            'value' => self::ALL_PAGES_VALUE,
            'label' => __('All Pages'),
        ];

        $options[] = [
            'label' => __('Platform Pages'),
            'value' => $this->getPlatformPages(),
        ];

        $cmsPages = $this->getCmsPages();
        if (!empty($cmsPages)) {
            $options[] = [
                'label' => __('CMS Pages'),
                'value' => $cmsPages,
            ];
        }

        return $options;
    }

    /**
     * Fixed list of key Magento "system" pages,
     * identified by their layout handles (full_action_name).
     *
     * @return array
     */
    private function getPlatformPages(): array
    {
        $pages = [
            'cms_index_index'          => __('Home Page'),
            'checkout_cart_index'      => __('Checkout - Cart'),
            'checkout_index_index'     => __('Checkout'),
            'checkout_onepage_success' => __('Checkout - Success Page'),
            'customer_account_login'   => __('Customer Login'),
            'customer_account_create'  => __('Customer Register'),
            'customer_account_index'   => __('My Account - Dashboard'),
            'sales_order_history'      => __('My Account - Orders'),
            'wishlist_index_index'     => __('My Account - Wishlist'),
            'catalogsearch_result_index' => __('Search Results'),
            'catalog_category_view'    => __('Category Page'),
            'catalog_product_view'     => __('Product Page'),
        ];

        $result = [];
        foreach ($pages as $handle => $label) {
            $result[] = [
                'value' => $handle,
                'label' => $label,
            ];
        }

        return $result;
    }

    /**
     * Searches for active CMS pages registered in Magento.
     *
     * @return array
     */
    private function getCmsPages(): array
    {
        $result = [];

        $collection = $this->pageCollectionFactory->create();
        $collection->addFieldToFilter('is_active', 1);
        $collection->addFieldToSelect(['identifier', 'title']);

        foreach ($collection as $page) {
            $result[] = [
                'value' => $page->getIdentifier(),
                'label' => $page->getTitle() . ' (' . $page->getIdentifier() . ')',
            ];
        }

        return $result;
    }
}
