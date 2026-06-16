<?php

class Yandex
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
            'key' => $this->modx->getOption('localizator3_key_yandex')
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
            return $this->modx->error->failure($this->modx->lexicon('localizator_item_err_yandex_key'));
        }

        if (trim($text) === '') {
            return '';
        }

        $data = [
            'key' => $this->config['key'],
            'lang' => $from . '-' . $to,
            'format' => 'html',
        ];

        $output = '';
        $parts = $this->prepare_text($text);

        foreach ($parts as $part) {
            $data['text'] = $part;
            $ch = curl_init('https://translate.yandex.net/api/v1.5/tr.json/translate');
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
                $output .= implode('', $response['text'] ?? []);
            } else {
                $code = $response['code'] ?? 'N/A';
                $this->modx->log(1, 'localizator3: Yandex error - ' . $code . ', see https://tech.yandex.ru/translate/doc/dg/reference/translate-docpage/');
                return '';
            }
        }

        return $output;
    }


    /**
     * @param string $text
     * @param int $limit
     *
     * @return array
     */
    public function prepare_text($text, $limit = 2000)
    {
        if ($limit > 0) {
            $ret = array();
            $limiten = mb_strlen($text, "UTF-8");
            for ($i = 0; $i < $limiten; $i += $limit) {
                $ret[] = mb_substr($text, $i, $limit, "UTF-8");
            }
            return $ret;
        }
        return preg_split("//u", $text, -1, PREG_SPLIT_NO_EMPTY);
    }
}
