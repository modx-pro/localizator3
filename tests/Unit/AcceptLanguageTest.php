<?php

namespace Localizator3\Test\Unit;

use PHPUnit\Framework\TestCase;

class AcceptLanguageTest extends TestCase
{
    public function testExactMatch(): void
    {
        $this->assertSame('ru', \localizator_detect_language_from_accept('ru-RU,ru;q=0.9,en;q=0.8', ['ru', 'en']));
        $this->assertSame('en', \localizator_detect_language_from_accept('en-US,en;q=0.9', ['ru', 'en']));
    }

    public function testQualityOrder(): void
    {
        $this->assertSame('en', \localizator_detect_language_from_accept('ru;q=0.5,en;q=0.9', ['ru', 'en']));
        $this->assertSame('ru', \localizator_detect_language_from_accept('en;q=0.5,ru;q=0.9', ['ru', 'en']));
    }

    public function testPrefixMatch(): void
    {
        $this->assertSame('ru', \localizator_detect_language_from_accept('ru-RU', ['ru', 'en']));
        $this->assertSame('en', \localizator_detect_language_from_accept('en-GB', ['ru', 'en']));
    }

    public function testNoMatchReturnsFirstAvailable(): void
    {
        $this->assertSame('ru', \localizator_detect_language_from_accept('de,fr;q=0.9', ['ru', 'en']));
    }

    public function testEmptyAcceptReturnsFirstAvailable(): void
    {
        $this->assertSame('ru', \localizator_detect_language_from_accept('', ['ru', 'en']));
    }

    public function testEmptyAvailableReturnsNull(): void
    {
        $this->assertNull(\localizator_detect_language_from_accept('en-US,en;q=0.9', []));
    }

    public function testBothEmptyReturnsNull(): void
    {
        $this->assertNull(\localizator_detect_language_from_accept('', []));
    }

    public function testSingleLanguage(): void
    {
        $this->assertSame('en', \localizator_detect_language_from_accept('en', ['en']));
    }

    public function testCaseInsensitive(): void
    {
        $this->assertSame('ru', \localizator_detect_language_from_accept('RU-ru,RU;q=0.9', ['ru', 'en']));
    }
}
