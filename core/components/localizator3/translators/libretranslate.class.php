<?php

/**
 * LibreTranslate — бесплатный open-source переводчик.
 * Self-hosted: pip install libretranslate → http://localhost:5000
 * Публичный API: https://libretranslate.com (опционально api_key)
 *
 * @see https://docs.libretranslate.com/
 */
class LibreTranslate
{
    private const TRANSLATE_ENDPOINT = '/translate';
    private const DEFAULT_URL = 'http://localhost:5000';

    /** @var \MODX\Revolution\modX $modx */
    public $modx;

    /** @var array $config */
    protected $config;

    public function __construct($modx, array $config = [])
    {
        $this->modx = $modx;
        $this->config = array_merge([
            'url' => $this->modx->getOption('localizator3_libretranslate_url', null, self::DEFAULT_URL, true),
            'key' => $this->modx->getOption('localizator3_key_libretranslate', null, '', true),
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

        $url = rtrim($this->config['url'], '/') . self::TRANSLATE_ENDPOINT;
        $payload = $this->buildPayload($text, $from, $to);
        $response = $this->sendPostRequest($url, $payload);

        return $this->parseResponse($response);
    }

    private function buildPayload($text, $from, $to): array
    {
        $payload = [
            'q' => $text,
            'source' => $from,
            'target' => $to,
            'format' => 'html',
        ];
        if (!empty($this->config['key'])) {
            $payload['api_key'] = $this->config['key'];
        }
        return $payload;
    }

    private function sendPostRequest(string $url, array $payload): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'body' => $response !== false ? $response : '',
            'httpCode' => $httpCode,
        ];
    }

    private function parseResponse(array $response): string
    {
        $data = json_decode($response['body'], true);
        $httpCode = $response['httpCode'];

        if ($httpCode === 200 && isset($data['translatedText'])) {
            return $data['translatedText'];
        }

        $errorMessage = is_array($data) ? ($data['error'] ?? $response['body']) : $response['body'];
        $this->logError($errorMessage ?: 'Unknown error', $httpCode);
        return '';
    }

    private function logError(string $message, int $httpCode): void
    {
        $this->modx->log(1, 'localizator3: LibreTranslate error - ' . $message . ' (HTTP ' . $httpCode . ')');
    }
}
