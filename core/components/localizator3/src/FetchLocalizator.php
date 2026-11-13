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

        $localizator = $this->modx->getService(
            'localizator3',
            'localizator',
            $this->modx->getOption(
                'localizator3_core_path',
                null,
                $this->modx->getOption('core_path', null, MODX_CORE_PATH) . 'components/localizator3/'
            ) . 'model/localizator3/'
        );

        if (!$localizator) {
            return;
        }

        if ($localizator instanceof \localizator && method_exists($localizator, 'shouldUseResourceFields') && $localizator->shouldUseResourceFields()) {
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
            ->select($this->modx->getSelectColumns(modTemplateVar::class, modTemplateVar::class));

        if ($q->prepare() && $q->stmt->execute()) {
            $this->config['localizatorTVs'] = $q->stmt->fetchAll(\PDO::FETCH_COLUMN);
        }

        foreach ($this->config['localizatorTVs'] as $tvName) {
            $tvName = strtolower($tvName);
            $alias  = 'TV' . $tvName;
            if (isset($this->config['tvsJoin'][$tvName])) {
                $this->config['tvsJoin'][$tvName]['class'] = 'locTemplateVarResource';
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

        return parent::prepareRows($rows);
    }
}
