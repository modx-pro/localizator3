<?php
/**
 * pdoFetchLocalizator — подкласс pdoFetch (pdoTools для MODX 3) с поддержкой локализованных TV.
 * Класс pdoFetch autoload'ится через composer (pdoTools 3.x для MODX 3).
 */

class pdoFetchLocalizator extends pdoFetch
{
    public function addTVs()
    {
        parent::addTVs();
        if (empty($this->config['tvsJoin'])) {
            return;
        }
        $localizator = $this->modx->getService(
            'localizator3',
            'localizator',
            $this->modx->getOption('localizator3_core_path', null, $this->modx->getOption('core_path') . 'components/localizator3/') . 'model/localizator3/'
        );
        if ($localizator instanceof localizator && $localizator->shouldUseResourceFields()) {
            return;
        }
        $this->config['localizator3_key'] = $this->modx->getOption('localizator3_key', $this->config, $this->modx->getOption('localizator3_key', null), true);
        $this->config['localizatorTVs'] = [];
        $where = ['name:IN' => array_keys($this->config['tvsJoin'])];
        if (\localizator3\localizatorContent::hasLocalizator3EnabledField($this->modx)) {
            $where['localizator3_enabled'] = 1;
        }
        $q = $this->modx->newQuery(\MODX\Revolution\modTemplateVar::class)
            ->where($where)
            ->select('name');
        if ($q->prepare() && $q->stmt->execute()) {
            $this->config['localizatorTVs'] = $q->stmt->fetchAll(PDO::FETCH_COLUMN);
        }
        foreach ($this->config['localizatorTVs'] as $name) {
            $name = strtolower($name);
            $alias = 'TV' . $name;
            if (isset($this->config['tvsJoin'][$name])) {
                $this->config['tvsJoin'][$name]['class'] = 'locTemplateVarResource';
                $this->config['tvsJoin'][$name]['on'] .= " AND `{$alias}`.`key` = " . $this->modx->quote($this->config['localizator3_key']);
            }
        }
    }

    public function prepareRows(array $rows = [])
    {
        $time = microtime(true);
        if (isset($this->config['localizatorTVs']) && !empty($this->config['localizatorTVs']) && (!empty($this->config['includeTVs']) && (!empty($this->config['prepareTVs']) || !empty($this->config['processTVs'])))) {
            $tvs = array_map('trim', explode(',', $this->config['includeTVs']));
            $prepare = ($this->config['prepareTVs'] == 1) ? $tvs : array_map('trim', explode(',', $this->config['prepareTVs']));
            $process = ($this->config['processTVs'] == 1) ? $tvs : array_map('trim', explode(',', $this->config['processTVs']));
            $processloctvs = array_intersect($process, $this->config['localizatorTVs']);
            $this->config['processTVs'] = implode(',', array_diff($process, $processloctvs));
            $this->config['prepareTVs'] = implode(',', array_diff($prepare, $processloctvs));
            if (!empty($processloctvs)) {
                $prepareTypes = $this->config['prepareTypes'] ?? [];
                foreach ($rows as &$row) {
                    if ($this->config['decodeJSON']) {
                        foreach ($row as $k => $v) {
                            if (!empty($v) && is_string($v) && ($v[0] == '[' || $v[0] == '{')) {
                                $tmp = json_decode($v, true);
                                if (json_last_error() == JSON_ERROR_NONE) {
                                    $row[$k] = $tmp;
                                }
                            }
                        }
                    }
                    foreach ($processloctvs as $tv) {
                        /** @var \MODX\Revolution\modTemplateVar $templateVar */
                        if (!$templateVar = $this->getStore($tv, 'tv')) {
                            if ($templateVar = $this->modx->getObject(\MODX\Revolution\modTemplateVar::class, ['name' => $tv])) {
                                $sourceCache = isset($prepareTypes[$templateVar->type]) ? $templateVar->getSourceCache($this->modx->context->get('key')) : null;
                                $templateVar->set('sourceCache', $sourceCache);
                                $this->setStore($tv, $templateVar, 'tv');
                            } else {
                                $this->addTime('Could not process or prepare TV "' . $tv . '"');
                                continue;
                            }
                        }
                        $tvPrefix = $this->config['tvPrefix'] ?? '';
                        $key = $tvPrefix . $templateVar->name;
                        $row[$key] = \localizator3\localizatorContent::renderTVOutput($this->modx, $templateVar, $row[$key], $row['id']);
                    }
                }
                $this->addTime('Processed Localizator TVs: ' . implode(', ', $processloctvs), microtime(true) - $time);
            }
        }
        return parent::prepareRows($rows);
    }
}
