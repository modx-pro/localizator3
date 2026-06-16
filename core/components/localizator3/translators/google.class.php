<?php

class Google
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

        $this->config = array_merge(array(
            'key' => $this->modx->getOption('localizator3_key_google')
        ), $config);
    }


    /**
     * @param string $text
     * @param string $from
     * @param string $to
     *
     * @return string
     */
    public function translate($text, $from, $to)
    {

        if (!$this->config['key']) {
            return $this->modx->error->failure($this->modx->lexicon('localizator_item_err_google_key'));
        }

        if (trim($text) === '') {
            return '';
        }

        $data = [
            'key' => $this->config['key'],
            'source' => $from,
            'target' => $to,
            'q' => $text,
            'format' => 'html',
        ];

        $ch = curl_init('https://www.googleapis.com/language/translate/v2');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data, '', '&'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $rawResponse = curl_exec($ch);
        curl_close($ch);

        $response = is_string($rawResponse) ? json_decode($rawResponse, true) : null;

        if (is_array($response) && ($response['code'] ?? 0) == 200) {
            return $response['data']['translations'][0]['translatedText'] ?? '';
        }

        $errorMessage = $response['error']['errors'][0]['message'] ?? $response['error']['message'] ?? $rawResponse ?? 'Unknown error';
        $this->modx->log(1, 'localizator3: Google translate error - ' . $errorMessage);
        return '';
    }
}
