<?php

namespace Localizator3\Test\Unit;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Unit tests for MyMemory translator.
 * Tests splitTextByBytes logic and translate behavior without external API calls.
 */
class MyMemoryTest extends TestCase
{
    private function createModxMock(): object
    {
        return new class {
            public function getOption($key, $options = null, $default = '', $skipEmpty = false)
            {
                return $default;
            }

            public function log($level, $msg)
            {
                return null;
            }
        };
    }

    private function invokeSplitTextByBytes(\MyMemory $translator, string $text, int $maxBytes): array
    {
        $method = new ReflectionMethod(\MyMemory::class, 'splitTextByBytes');
        $method->setAccessible(true);
        return $method->invoke($translator, $text, $maxBytes);
    }

    public function testSplitTextByBytesSplitsLongText(): void
    {
        $modx = $this->createModxMock();
        $translator = new \MyMemory($modx, []);
        $text = str_repeat('a', 1000);
        $chunks = $this->invokeSplitTextByBytes($translator, $text, 500);

        $this->assertCount(2, $chunks);
        $this->assertSame(500, strlen($chunks[0]));
        $this->assertSame(500, strlen($chunks[1]));
        $this->assertSame($text, implode('', $chunks));
    }

    public function testSplitTextByBytesHandlesShortText(): void
    {
        $modx = $this->createModxMock();
        $translator = new \MyMemory($modx, []);
        $text = 'Hello';
        $chunks = $this->invokeSplitTextByBytes($translator, $text, 500);

        $this->assertCount(1, $chunks);
        $this->assertSame('Hello', $chunks[0]);
    }

    public function testSplitTextByBytesHandlesUtf8Correctly(): void
    {
        $modx = $this->createModxMock();
        $translator = new \MyMemory($modx, []);
        $text = 'Привет мир';
        $chunks = $this->invokeSplitTextByBytes($translator, $text, 10);

        $this->assertNotEmpty($chunks);
        $this->assertSame($text, implode('', $chunks));
    }

    public function testSplitTextByBytesHandlesEmptyString(): void
    {
        $modx = $this->createModxMock();
        $translator = new \MyMemory($modx, []);
        $chunks = $this->invokeSplitTextByBytes($translator, '', 500);

        $this->assertSame([], $chunks);
    }

    public function testTranslateReturnsEmptyForEmptyInput(): void
    {
        $modx = $this->createModxMock();
        $translator = new \MyMemory($modx, []);
        $result = $translator->translate('', 'en', 'ru');

        $this->assertSame('', $result);
    }

    public function testTranslateReturnsEmptyForWhitespaceOnly(): void
    {
        $modx = $this->createModxMock();
        $translator = new \MyMemory($modx, []);
        $result = $translator->translate('   ', 'en', 'ru');

        $this->assertSame('', $result);
    }
}
