<?php

/** @var \xPDO\Transport\xPDOTransport $transport */
if (!$transport->xpdo) {
    return false;
}
$modx = $transport->xpdo;
$validTransport = $transport instanceof \xPDO\Transport\xPDOTransport;
if (!$validTransport) {
    return false;
}
/** @var array $options */
switch ($options[\xPDO\Transport\xPDOTransport::PACKAGE_ACTION]) {
    case \xPDO\Transport\xPDOTransport::ACTION_INSTALL:
        break;
        case \xPDO\Transport\xPDOTransport::ACTION_UPGRADE:
            $emptyChunkFiles = array(
                'languages.tpl' => 'chunk.languages.tpl',
                'languages.dropdown.tpl' => 'chunk.languages.dropdown.tpl',
            );
            $chunkDir = MODX_CORE_PATH . 'components/localizator3/elements/chunks/';
            foreach ($emptyChunkFiles as $chunkName => $fileName) {
                $chunk = $modx->getObject(\MODX\Revolution\modChunk::class, array('name' => $chunkName));
                if (!$chunk || trim((string)$chunk->get('snippet')) !== '') {
                    continue;
                }
                $path = $chunkDir . $fileName;
                if (!is_readable($path)) {
                    $modx->log(\MODX\Revolution\modX::LOG_LEVEL_WARN, '[Localizator3] Chunk file not found: ' . $path);
                    continue;
                }
                $body = file_get_contents($path);
                if ($body === false || trim($body) === '') {
                    continue;
                }
                $chunk->set('snippet', $body);
                if ($chunk->save()) {
                    $modx->log(\MODX\Revolution\modX::LOG_LEVEL_INFO, '[Localizator3] Filled empty chunk: ' . $chunkName);
                }
            }
            if (!empty($options['update_chunks'])) {
                $modx->log(\modX::LOG_LEVEL_INFO, '== UpdateChunks: migrating');
                foreach ($options['update_chunks'] as $v) {
                    if ($chunk = $modx->getObject('modChunk', array('name' => $v))) {
                        foreach ($transport->vehicles as $item) {
                            /** @var \xPDO\Transport\xPDOTransportVehicle $vehicle */
                            if ($item['class'] == 'modCategory' && $vehicle = $transport->get($item['filename'])) {
                                foreach ($vehicle->payload['related_objects']['Chunks'] as $item2) {
                                    if ($data = json_decode($item2['object'], true)) {
                                        if ($data['name'] == $v) {
                                            $chunk->set('snippet', $data['snippet']);
                                            $chunk->save();
                                            $modx->log(\modX::LOG_LEVEL_INFO, '✓ Updated chunk: ' . $v);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                $modx->log(\modX::LOG_LEVEL_INFO, '== UpdateChunks: migrated');
            }
            break;
}

return true;
