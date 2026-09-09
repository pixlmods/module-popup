<?php
/**
 * Copyright © Pixl Mods. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PixlMods\Popup\Test\Unit\Block\Frontend;

use Magento\Cms\Model\Template\FilterProvider;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\DataObject;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PixlMods\Popup\Block\Frontend\Popup;
use PixlMods\Popup\Model\Config;
use PixlMods\Popup\Model\ResourceModel\Popup\Collection;
use PixlMods\Popup\Model\ResourceModel\Popup\CollectionFactory;
use PixlMods\Popup\Model\Source\FrontendPages;

class PopupTest extends TestCase
{
    private const CURRENT_STORE_ID = 1;

    private ObjectManagerHelper $objectManagerHelper;

    /** @var StoreManagerInterface|MockObject */
    private $storeManagerMock;

    /** @var CollectionFactory|MockObject */
    private $collectionFactoryMock;

    /** @var FilterProvider|MockObject */
    private $filterProviderMock;

    /** @var HttpRequest|MockObject */
    private $requestMock;

    /** @var Registry|MockObject */
    private $registryMock;

    /** @var CustomerSession|MockObject */
    private $customerSessionMock;

    /** @var Config|MockObject */
    private $configMock;

    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);

        $storeMock = $this->createMock(StoreInterface::class);
        $storeMock->method('getId')->willReturn(self::CURRENT_STORE_ID);

        $this->storeManagerMock = $this->createMock(StoreManagerInterface::class);
        $this->storeManagerMock->method('getStore')->willReturn($storeMock);

        $this->collectionFactoryMock = $this->createMock(CollectionFactory::class);

        $filterMock = $this->getMockBuilder(\stdClass::class)->addMethods(['filter'])->getMock();
        $filterMock->method('filter')->willReturnArgument(0);

        $this->filterProviderMock = $this->createMock(FilterProvider::class);
        $this->filterProviderMock->method('getPageFilter')->willReturn($filterMock);

        $this->requestMock = $this->createMock(HttpRequest::class);
        $this->registryMock = $this->createMock(Registry::class);
        $this->customerSessionMock = $this->createMock(CustomerSession::class);
        $this->customerSessionMock->method('getCustomerGroupId')->willReturn(0);

        $this->configMock = $this->createMock(Config::class);
        $this->configMock->method('isActive')->willReturn(true);
    }

    /**
     * Builds an always-eligible popup row, with the given fields overridden.
     */
    private function makePopup(array $overrides = []): DataObject
    {
        return new DataObject(array_merge([
            'id' => 1,
            'title' => 'Test Popup',
            'content' => '<p>Hello</p>',
            'status' => 1,
            'pages' => FrontendPages::ALL_PAGES_VALUE,
            'stores' => '0',
            'customer_group_ids' => '',
            'start_date' => null,
            'end_date' => null,
            'trigger_type' => 'on_load',
            'trigger_value' => '',
            'frequency' => 'always',
            'priority' => 10,
            'display_delay' => 0,
        ], $overrides));
    }

    /**
     * @param DataObject[] $items
     */
    private function buildBlock(array $items): Popup
    {
        $collectionMock = $this->objectManagerHelper->getCollectionMock(Collection::class, $items);
        $collectionMock->method('addFieldToFilter')->willReturnSelf();

        $this->collectionFactoryMock->method('create')->willReturn($collectionMock);

        return $this->objectManagerHelper->getObject(Popup::class, [
            'storeManager' => $this->storeManagerMock,
            'popupCollectionFactory' => $this->collectionFactoryMock,
            'filterProvider' => $this->filterProviderMock,
            'request' => $this->requestMock,
            'registry' => $this->registryMock,
            'scopeConfig' => $this->createMock(ScopeConfigInterface::class),
            'customerSession' => $this->customerSessionMock,
            'jsonSerializer' => new JsonSerializer(),
            'config' => $this->configMock,
        ]);
    }

    public function testFullyEligiblePopupIsReturned(): void
    {
        $this->requestMock->method('getFullActionName')->willReturn('cms_index_index');

        $block = $this->buildBlock([$this->makePopup()]);

        $data = $block->getPopupsData();

        $this->assertCount(1, $data);
        $this->assertSame(1, $data[0]['id']);
    }

    public function testModuleDisabledViaConfigReturnsNoPopups(): void
    {
        $this->configMock = $this->createMock(Config::class);
        $this->configMock->method('isActive')->willReturn(false);

        $this->requestMock->method('getFullActionName')->willReturn('cms_index_index');

        $block = $this->buildBlock([$this->makePopup()]);

        $this->assertSame([], $block->getPopupsData());
    }

    public function testPopupRestrictedToAnotherStoreIsExcluded(): void
    {
        $this->requestMock->method('getFullActionName')->willReturn('cms_index_index');

        $block = $this->buildBlock([$this->makePopup(['stores' => '99'])]);

        $this->assertSame([], $block->getPopupsData());
    }

    public function testPopupWithFutureStartDateIsExcluded(): void
    {
        $this->requestMock->method('getFullActionName')->willReturn('cms_index_index');

        $block = $this->buildBlock([
            $this->makePopup(['start_date' => date('Y-m-d H:i:s', strtotime('+1 day'))]),
        ]);

        $this->assertSame([], $block->getPopupsData());
    }

    public function testPopupWithPastEndDateIsExcluded(): void
    {
        $this->requestMock->method('getFullActionName')->willReturn('cms_index_index');

        $block = $this->buildBlock([
            $this->makePopup(['end_date' => date('Y-m-d H:i:s', strtotime('-1 day'))]),
        ]);

        $this->assertSame([], $block->getPopupsData());
    }

    public function testPopupWithinItsDateWindowIsIncluded(): void
    {
        $this->requestMock->method('getFullActionName')->willReturn('cms_index_index');

        $block = $this->buildBlock([
            $this->makePopup([
                'start_date' => date('Y-m-d H:i:s', strtotime('-1 day')),
                'end_date' => date('Y-m-d H:i:s', strtotime('+1 day')),
            ]),
        ]);

        $this->assertCount(1, $block->getPopupsData());
    }

    public function testPopupRestrictedToAnotherPageIsExcluded(): void
    {
        $this->requestMock->method('getFullActionName')->willReturn('checkout_cart_index');

        $block = $this->buildBlock([
            $this->makePopup(['pages' => 'customer_account_login']),
        ]);

        $this->assertSame([], $block->getPopupsData());
    }

    public function testPopupMatchingCurrentPageHandleIsIncluded(): void
    {
        $this->requestMock->method('getFullActionName')->willReturn('checkout_cart_index');

        $block = $this->buildBlock([
            $this->makePopup(['pages' => 'checkout_cart_index']),
        ]);

        $this->assertCount(1, $block->getPopupsData());
    }

    public function testPopupRestrictedToAnotherCustomerGroupIsExcluded(): void
    {
        $this->requestMock->method('getFullActionName')->willReturn('cms_index_index');
        $this->customerSessionMock = $this->createMock(CustomerSession::class);
        $this->customerSessionMock->method('getCustomerGroupId')->willReturn(0);

        $block = $this->buildBlock([
            $this->makePopup(['customer_group_ids' => '1,2']),
        ]);

        $this->assertSame([], $block->getPopupsData());
    }

    public function testPopupMatchingCurrentCustomerGroupIsIncluded(): void
    {
        $this->requestMock->method('getFullActionName')->willReturn('cms_index_index');
        $this->customerSessionMock->method('getCustomerGroupId')->willReturn(0);

        $block = $this->buildBlock([
            $this->makePopup(['customer_group_ids' => '0,1']),
        ]);

        $this->assertCount(1, $block->getPopupsData());
    }

    public function testEligiblePopupsAreSortedByAscendingPriority(): void
    {
        $this->requestMock->method('getFullActionName')->willReturn('cms_index_index');

        $block = $this->buildBlock([
            $this->makePopup(['id' => 1, 'priority' => 20]),
            $this->makePopup(['id' => 2, 'priority' => 5]),
            $this->makePopup(['id' => 3, 'priority' => 10]),
        ]);

        $ids = array_column($block->getPopupsData(), 'id');

        $this->assertSame([2, 3, 1], $ids);
    }
}
