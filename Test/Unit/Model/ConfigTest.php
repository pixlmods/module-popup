<?php
/**
 * Copyright © Pixl Mods. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PixlMods\Popup\Test\Unit\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PixlMods\Popup\Model\Config;

class ConfigTest extends TestCase
{
    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfigMock;

    private Config $config;

    protected function setUp(): void
    {
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $this->config = new Config($this->scopeConfigMock);
    }

    public function testIsActiveReadsTheConfiguredFlag(): void
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('isSetFlag')
            ->with('pixlmods_popup/general/active', ScopeInterface::SCOPE_STORE, 5)
            ->willReturn(true);

        $this->assertTrue($this->config->isActive(5));
    }

    public function testIsActiveReturnsFalseWhenDisabled(): void
    {
        $this->scopeConfigMock->method('isSetFlag')->willReturn(false);

        $this->assertFalse($this->config->isActive());
    }

    public function testIsTrackingEnabledReadsTheConfiguredFlag(): void
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('isSetFlag')
            ->with('pixlmods_popup/tracking/enabled', ScopeInterface::SCOPE_STORE, null)
            ->willReturn(true);

        $this->assertTrue($this->config->isTrackingEnabled());
    }

    /**
     * @dataProvider dayFrequencyLifetimeDataProvider
     */
    public function testGetDayFrequencyLifetimeFallsBackToOneDayWhenMisconfigured($configuredValue, int $expected): void
    {
        $this->scopeConfigMock->method('getValue')
            ->with('pixlmods_popup/tracking/day_frequency_days', ScopeInterface::SCOPE_STORE, null)
            ->willReturn($configuredValue);

        $this->assertSame($expected, $this->config->getDayFrequencyLifetime());
    }

    public static function dayFrequencyLifetimeDataProvider(): array
    {
        return [
            'normal value' => ['7', 7],
            'zero falls back to one' => ['0', 1],
            'negative falls back to one' => ['-3', 1],
            'empty falls back to one' => [null, 1],
        ];
    }
}
