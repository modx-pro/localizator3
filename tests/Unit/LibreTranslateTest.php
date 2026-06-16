<?php

namespace Localizator3\Test\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for LibreTranslate translator.
 */
class LibreTranslateTest extends TestCase
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

    public function testTranslateReturnsEmptyForEmptyInput(): void
    {
        $modx = $this->createModxMock();
        $translator = new \LibreTranslate($modx, ['url' => 'http://localhost:5000']);
        $result = $translator->translate('', 'en', 'ru');

        $this->assertSame('', $result);
    }

    public function testTranslateReturnsEmptyForWhitespaceOnly(): void
    {
        $modx = $this->createModxMock();
        $translator = new \LibreTranslate($modx, ['url' => 'http://localhost:5000']);
        $result = $translator->translate('   ', 'en', 'ru');

        $this->assertSame('', $result);
    }
}
