<?php

class SimpleCopy
{
    /** @var modX $modx */
    public $modx;


    /**
     * @param modX $modx
     * @param array $config
     */
    public function __construct($modx, array $config = [])
    {
        $this->modx = $modx;
        $this->config = $config;
    }


    /**
     * Копирует текст без перевода. Параметры $from и $to игнорируются.
     *
     * @param string $text
     * @param string $from
     * @param string $to
     * @return string
     */
    public function translate($text, $from = '', $to = '')
    {
        return $text;
    }
}
