<?php
namespace ModxPro\PdoTools;

use ModxPro\PdoTools\Fetch;
use MODX\Revolution\modX;
use MODX\Revolution\modTemplateVar;

/**
 * pdoFetchLocalizator — расширение Fetch pdoTools 3 с поддержкой локализованных TV (Localizator3).
 *
 * Перехватывает addTVs() и prepareRows(), чтобы:
 *   • Использовать locTemplateVarResource вместо modTemplateVarResource
 *   • Обрабатывать локализованные TV через localizatorContent::renderTVOutput()
 *
 * @package localizator3
 * @see https://github.com/modx-pro/localizator3/issues/31
 */
class FetchLocalizator extends Fetch
{
    /** @var modX */
    protected $modx;

    /** @var array */
    protected $config = [];

    /**
     * pdoFetchLocalizator constructor.
     *
     * @param modX $modx
     * @param array $config
     */
    public function __construct(modX $modx, array $config = [])
    {
        parent::__construct($modx, $config);
    }

    /**
     * Добавляет TV-поля к запросу с поддержкой локализации.
     *
     * Перехватывает стандартный addTVs(), заменяет класс на
     * locTemplateVarResource и подставляет WHERE по локализационному ключу.
     */
    public function addTVs()
    {
        parent::addTVs();

        if (empty($this->config['tvsJoin'])) {
            return;
        }

        $localizator = $this->localizatorService();
        if ($localizator === null) {
            return;
        }

        if (method_exists($localizator, 'shouldUseResourceFields') && $localizator->shouldUseResourceFields()) {
            return;
        }

        $this->config['localizator3_key'] = $this->modx->getOption(
            'localizator3_key',
            $this->config,
            $this->modx->getOption('localizator3_key', null, null),
            true
        );

        $this->config['localizatorTVs'] = [];

        $where = ['name:IN' => array_keys($this->config['tvsJoin'])];

        if (class_exists('\\localizator3\\localizatorContent') && \localizator3\localizatorContent::hasLocalizator3EnabledField($this->modx)) {
            $where['localizator3_enabled'] = 1;
        }

        /** @var \xPDOQuery $q */
        $q = $this->modx->newQuery(modTemplateVar::class)
            ->where($where)
            ->select('name');

        if ($q->prepare() && $q->stmt->execute()) {
            $this->config['localizatorTVs'] = $q->stmt->fetchAll(\PDO::FETCH_COLUMN);
        }

        foreach ($this->config['localizatorTVs'] as $tvName) {
            $tvName = strtolower($tvName);
            $alias  = 'TV' . $tvName;
            if (isset($this->config['tvsJoin'][$tvName])) {
                $this->config['tvsJoin'][$tvName]['class'] = \localizator3\locTemplateVarResource::class;
                $this->config['tvsJoin'][$tvName]['on']   .= ' AND `' . $alias . '`.`key` = ' . $this->modx->quote($this->config['localizator3_key']);
            }
        }
    }

    /**
     * Обрабатывает строки с учётом локализованных TV.
     *
     * @param array $rows Исходные строки выборки.
     * @return array Обработанные строки.
     */
    public function prepareRows(array $rows = []): array
    {
        $time = microtime(true);

        if (
            isset($this->config['localizatorTVs'])
            && !empty($this->config['localizatorTVs'])
            && isset($this->config['includeTVs'])
            && (!empty($this->config['prepareTVs']) || !empty($this->config['processTVs']))
        ) {
            $tvs        = array_map('trim', explode(',', $this->config['includeTVs']));
            $prepare    = ($this->config['prepareTVs'] == 1)
                ? $tvs
                : array_map('trim', explode(',', $this->config['prepareTVs']));
            $process    = ($this->config['processTVs'] == 1)
                ? $tvs
                : array_map('trim', explode(',', $this->config['processTVs']));

            $processLocTvs = array_intersect($process, $this->config['localizatorTVs']);

            $this->config['processTVs'] = implode(',', array_diff($process, $processLocTvs));
            $this->config['prepareTVs'] = implode(',', array_diff($prepare, $processLocTvs));

            if (!empty($processLocTvs)) {
                $prepareTypes = is_array($this->config['prepareTypes'] ?? null)
                    ? $this->config['prepareTypes']
                    : [];

                foreach ($rows as &$row) {
                    if (!empty($this->config['decodeJSON'])) {
                        foreach ($row as $k => $v) {
                            if (is_string($v) && strlen($v) > 0 && ($v[0] === '[' || $v[0] === '{')) {
                                $tmp = json_decode($v, true);
                                if (json_last_error() === JSON_ERROR_NONE) {
                                    $row[$k] = $tmp;
                                }
                            }
                        }
                    }

                    foreach ($processLocTvs as $tv) {
                        /** @var modTemplateVar|null $templateVar */
                        $templateVar = $this->getStore($tv, 'tv');

                        if (!$templateVar) {
                            $templateVar = $this->modx->getObject(modTemplateVar::class, ['name' => $tv]);
                            if (!$templateVar) {
                                $this->addTime('Could not process or prepare TV "' . $tv . '"');
                                continue;
                            }

                            $sourceCache = $prepareTypes[$templateVar->get('type')] ?? null;
                            if ($sourceCache !== null) {
                                $templateVar->set('sourceCache', $sourceCache);
                            }
                            $this->setStore($tv, $templateVar, 'tv');
                        }

                        $tvPrefix = $this->config['tvPrefix'] ?? '';
                        $key      = $tvPrefix . $templateVar->get('name');
                        $rowValue = $row[$key] ?? null;

                        if (class_exists('\\localizator3\\localizatorContent')) {
                            $row[$key] = \localizator3\localizatorContent::renderTVOutput(
                                $this->modx,
                                $templateVar,
                                $rowValue,
                                $row['id'] ?? null
                            );
                        }
                    }
                }
                unset($row);

                $this->addTime(
                    'Processed Localizator TVs: ' . implode(', ', $processLocTvs),
                    microtime(true) - $time
                );
            }
        }

        return $this->prefixListingUris(parent::prepareRows($rows));
    }

    /**
     * Подмешивает перевод полей ресурса в прямой pdoMenu и pdoResources.
     * Обёртка getLocalizedResources уже кладёт join localizator, второй раз он не добавляется.
     */
    public function addJoins()
    {
        $this->applyListingLocalization();
        parent::addJoins();
    }

    /**
     * {@inheritdoc}
     */
    public function makeUrl($id, $options = [], $args = [])
    {
        $url = parent::makeUrl($id, $options, $args);
        if (!is_string($url) || $url === '') {
            return $url;
        }

        return $this->languageUrl($url);
    }

    /**
     * @return \localizator|null
     */
    protected function localizatorService()
    {
        $service = $this->modx->getService(
            'localizator3',
            'localizator',
            $this->modx->getOption(
                'localizator3_core_path',
                null,
                $this->modx->getOption('core_path', null, MODX_CORE_PATH) . 'components/localizator3/'
            ) . 'model/localizator3/'
        );

        return $service instanceof \localizator ? $service : null;
    }

    protected function applyListingLocalization(): void
    {
        foreach (['leftJoin', 'innerJoin', 'select'] as $part) {
            if (isset($this->listingConfigArray($this->config[$part] ?? null)['localizator'])) {
                return;
            }
        }

        $localizator = $this->localizatorService();
        if ($localizator === null || !method_exists($localizator, 'getListingQuery')) {
            return;
        }

        $key = (string)$this->modx->getOption(
            'localizator3_key',
            $this->config,
            $this->modx->getOption('localizator3_key', null, ''),
            true
        );
        $class = (string)($this->config['class'] ?? \MODX\Revolution\modResource::class);
        $onlyWithLocalization = filter_var($this->config['onlyWithLocalization'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $listing = $localizator->getListingQuery($class, $key, $onlyWithLocalization);

        foreach (['leftJoin', 'innerJoin', 'select'] as $part) {
            if (empty($listing[$part])) {
                continue;
            }
            $existing = $this->config[$part] ?? null;
            $existingArray = $this->listingConfigArray($existing);
            if ($part === 'select' && is_string($existing) && $existing !== '' && $existingArray === []) {
                $alias = $this->modx->getAlias((string)($this->config['class'] ?? \MODX\Revolution\modResource::class));
                $existingArray = [$alias => $existing];
            }
            $this->config[$part] = array_merge($listing[$part], $existingArray);
        }
    }

    /**
     * @param mixed $value
     * @return array
     */
    protected function listingConfigArray($value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (is_string($value) && $value !== '' && ($value[0] === '{' || $value[0] === '[')) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    /**
     * uri без начального слэша, но с префиксом языка: шаблон '/' ~ $uri даёт /en/page.
     *
     * @param array $rows
     * @return array
     */
    protected function prefixListingUris(array $rows): array
    {
        $bare = $this->languageBase();
        if ($bare === '') {
            return $rows;
        }

        $siteStart = (int)$this->modx->getOption('site_start');
        foreach ($rows as &$row) {
            if (!is_array($row) || !array_key_exists('uri', $row)) {
                continue;
            }
            $uri = ltrim((string)$row['uri'], '/');
            if ($uri === $bare || strpos($uri, $bare . '/') === 0) {
                continue;
            }
            $id = isset($row['id']) ? (int)$row['id'] : 0;
            $row['uri'] = $id === $siteStart
                ? $bare . '/'
                : $bare . '/' . $uri;
        }
        unset($row);

        return $rows;
    }

    protected function languageBase(): string
    {
        return trim((string)$this->modx->getOption('base_url', null, '/'), '/');
    }

    protected function languageUrl(string $url): string
    {
        $bare = $this->languageBase();
        if ($bare === '' || strpos($url, '://') !== false) {
            return $url;
        }

        $hash = '';
        $query = '';
        $path = $url;
        $hashPos = strpos($path, '#');
        if ($hashPos !== false) {
            $hash = substr($path, $hashPos);
            $path = substr($path, 0, $hashPos);
        }
        $queryPos = strpos($path, '?');
        if ($queryPos !== false) {
            $query = substr($path, $queryPos);
            $path = substr($path, 0, $queryPos);
        }

        $trimmed = ltrim($path, '/');
        if ($trimmed === '' || $trimmed === $bare) {
            return '/' . $bare . '/' . $query . $hash;
        }
        if (strpos($trimmed, $bare . '/') === 0) {
            return '/' . $trimmed . $query . $hash;
        }

        return '/' . $bare . '/' . $trimmed . $query . $hash;
    }
}
