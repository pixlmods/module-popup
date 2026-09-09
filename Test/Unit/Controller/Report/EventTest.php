<?php
/**
 * Copyright © Pixl Mods. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PixlMods\Popup\Test\Unit\Controller\Report;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Customer\Model\SessionFactory as CustomerSessionFactory;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\Json as JsonResult;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PixlMods\Popup\Controller\Report\Event;
use PixlMods\Popup\Model\Config;
use Psr\Log\LoggerInterface;

class EventTest extends TestCase
{
    private ObjectManagerHelper $objectManagerHelper;

    /** @var RequestInterface|MockObject */
    private $requestMock;

    /** @var AdapterInterface|MockObject */
    private $connectionMock;

    /** @var ResourceConnection|MockObject */
    private $resourceMock;

    /** @var LoggerInterface|MockObject */
    private $loggerMock;

    /** @var Config|MockObject */
    private $configMock;

    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);

        $this->requestMock = $this->createMock(RequestInterface::class);

        $this->connectionMock = $this->createMock(AdapterInterface::class);
        $this->connectionMock->method('getTableName')->willReturnArgument(0);

        $this->resourceMock = $this->createMock(ResourceConnection::class);
        $this->resourceMock->method('getConnection')->willReturn($this->connectionMock);

        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->configMock = $this->createMock(Config::class);
        $this->configMock->method('isTrackingEnabled')->willReturn(true);
    }

    /**
     * Json result exposes neither the decoded payload nor the HTTP code it was given
     * (setData()/setHttpResponseCode() only feed protected properties), so tests read
     * them back through reflection instead.
     */
    private function readJsonData(JsonResult $result): array
    {
        $property = new \ReflectionProperty(JsonResult::class, 'json');
        $property->setAccessible(true);

        return json_decode((string) $property->getValue($result), true);
    }

    private function readHttpResponseCode(JsonResult $result): ?int
    {
        $property = new \ReflectionProperty(\Magento\Framework\Controller\AbstractResult::class, 'httpResponseCode');
        $property->setAccessible(true);

        return $property->getValue($result);
    }

    /**
     * @param array<string, mixed> $params
     */
    private function buildController(array $params): Event
    {
        $this->requestMock->method('getParam')->willReturnMap(
            array_map(
                static fn ($key, $value) => [$key, null, $value],
                array_keys($params),
                array_values($params)
            )
        );

        $storeMock = $this->createMock(StoreInterface::class);
        $storeMock->method('getId')->willReturn(1);

        $storeManagerMock = $this->createMock(StoreManagerInterface::class);
        $storeManagerMock->method('getStore')->willReturn($storeMock);

        $customerSessionMock = $this->createMock(CustomerSession::class);
        $customerSessionMock->method('getCustomerId')->willReturn(null);

        $customerSessionFactoryMock = $this->createMock(CustomerSessionFactory::class);
        $customerSessionFactoryMock->method('create')->willReturn($customerSessionMock);

        $resultJson = $this->objectManagerHelper->getObject(JsonResult::class, [
            'serializer' => new \Magento\Framework\Serialize\Serializer\Json(),
        ]);
        $resultJsonFactoryMock = $this->createMock(JsonFactory::class);
        $resultJsonFactoryMock->method('create')->willReturn($resultJson);

        $context = $this->objectManagerHelper->getObject(Context::class, [
            'request' => $this->requestMock,
        ]);

        return $this->objectManagerHelper->getObject(Event::class, [
            'context' => $context,
            'resultJsonFactory' => $resultJsonFactoryMock,
            'resource' => $this->resourceMock,
            'customerSessionFactory' => $customerSessionFactoryMock,
            'storeManager' => $storeManagerMock,
            'logger' => $this->loggerMock,
            'config' => $this->configMock,
        ]);
    }

    public function testValidEventIsRecorded(): void
    {
        $this->connectionMock->expects($this->once())
            ->method('insert')
            ->with(
                'pixlmods_popup_event',
                $this->callback(function (array $data) {
                    return $data['popup_id'] === 5
                        && $data['event_type'] === 'view'
                        && $data['store_id'] === 1;
                })
            );

        $controller = $this->buildController(['popup_id' => 5, 'event_type' => 'view']);

        /** @var JsonResult $result */
        $result = $controller->execute();

        $this->assertSame(['success' => true], $this->readJsonData($result));
    }

    public function testTrackingDisabledSkipsInsertButStillReportsSuccess(): void
    {
        $this->configMock = $this->createMock(Config::class);
        $this->configMock->method('isTrackingEnabled')->willReturn(false);

        $this->connectionMock->expects($this->never())->method('insert');

        $controller = $this->buildController(['popup_id' => 5, 'event_type' => 'view']);

        /** @var JsonResult $result */
        $result = $controller->execute();

        $this->assertSame(['success' => true], $this->readJsonData($result));
    }

    public function testMissingPopupIdIsRejected(): void
    {
        $this->connectionMock->expects($this->never())->method('insert');

        $controller = $this->buildController(['popup_id' => 0, 'event_type' => 'view']);

        /** @var JsonResult $result */
        $result = $controller->execute();

        $this->assertSame(400, $this->readHttpResponseCode($result));
        $this->assertSame(['success' => false], $this->readJsonData($result));
    }

    public function testUnknownEventTypeIsRejected(): void
    {
        $this->connectionMock->expects($this->never())->method('insert');

        $controller = $this->buildController(['popup_id' => 5, 'event_type' => 'not_a_real_event']);

        /** @var JsonResult $result */
        $result = $controller->execute();

        $this->assertSame(400, $this->readHttpResponseCode($result));
        $this->assertSame(['success' => false], $this->readJsonData($result));
    }

    public function testDatabaseFailureIsLoggedAndReturnsServerError(): void
    {
        $this->connectionMock->method('insert')->willThrowException(new \RuntimeException('DB is gone'));
        $this->loggerMock->expects($this->once())->method('error');

        $controller = $this->buildController(['popup_id' => 5, 'event_type' => 'conversion']);

        /** @var JsonResult $result */
        $result = $controller->execute();

        $this->assertSame(500, $this->readHttpResponseCode($result));
        $this->assertSame(['success' => false], $this->readJsonData($result));
    }

    public function testCsrfValidationIsExplicitlyBypassed(): void
    {
        $controller = $this->buildController(['popup_id' => 5, 'event_type' => 'view']);

        $this->assertTrue($controller->validateForCsrf($this->requestMock));
        $this->assertNull($controller->createCsrfValidationException($this->requestMock));
    }
}
