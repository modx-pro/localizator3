<?php

/**
 * MyMemory — бесплатный API перевода.
 * Без ключа: 5000 символов/день. С email (параметр de): 50000 символов/день.
 * Макс 500 байт за запрос — текст разбивается автоматически.
 *
 * @see https://mymemory.translated.net/doc/spec.php
 */
class MyMemory
{
    private const API_URL = 'https://api.mymemory.translated.net/get';
    private const MAX_CHUNK_BYTES = 500;

    /** @var \MODX\Revolution\modX $modx */
    public $modx;

    /** @var array $config */
    protected $config;

    public function __construct($modx, array $config = [])
    {
        $this->modx = $modx;
        $this->config = array_merge([
            'email' => $this->modx->getOption('localizator3_mymemory_email', null, '', true),
        ], $config);
    }

    /**
     * @param string $text
     * @param string $from
     * @param string $to
     * @return string
     */
    public function translate($text, $from, $to)
    {
        if (trim($text) === '') {
            return '';
        }

        $chunks = $this->splitTextByBytes($text, self::MAX_CHUNK_BYTES);
        $langpair = $from . '|' . $to;
        $result = '';

        foreach ($chunks as $chunk) {
            $translated = $this->translateChunk($chunk, $langpair);
            if ($translated === null) {
                return '';
            }
            $result .= $translated;
        }

        return $result;
    }

    private function translateChunk(string $chunk, string $langpair): ?string
    {
        $params = ['q' => $chunk, 'langpair' => $langpair];
        if (!empty($this->config['email'])) {
            $params['de'] = $this->config['email'];
        }

        $url = self::API_URL . '?' . http_build_query($params);
        $response = $this->sendGetRequest($url);
        $data = json_decode($response, true);

        if (is_array($data) && ($data['responseStatus'] ?? 0) === 200) {
            return $data['responseData']['translatedText'] ?? '';
        }

        $error = is_array($data) ? ($data['responseDetails'] ?? $response) : $response;
        $this->modx->log(1, 'localizator3: MyMemory error - ' . ($error ?: 'Unknown error'));
        return null;
    }

    private function sendGetRequest(string $url): string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response !== false ? $response : '';
    }

    /**
     * Разбивает текст на части по байтам. Использует mb_strcut для корректной работы с UTF-8.
     *
     * @param string $text
     * @param int $maxBytes
     * @return array<string>
     */
    protected function splitTextByBytes(string $text, int $maxBytes): array
    {
        $result = [];
        $bytePos = 0;
        $totalBytes = strlen($text);

        while ($bytePos < $totalBytes) {
            $chunk = mb_strcut($text, $bytePos, $maxBytes, 'UTF-8');
            if ($chunk === '') {
                break;
            }
            $result[] = $chunk;
            $bytePos += strlen($chunk);
        }

        return $result;
    }
}
