<?php

/**
 * Shared helpers for downloading and installing MODX transport packages from a provider.
 * Loaded before resolver_01_vuetools (alphabetical order).
 */

if (!function_exists('localizator3DownloadTransportPackage')) {
    function localizator3DownloadTransportPackage($src, $dst)
    {
        if (ini_get('allow_url_fopen')) {
            $file = @file_get_contents($src);
        } elseif (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $src);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 180);
            $safeMode = @ini_get('safe_mode');
            $openBasedir = @ini_get('open_basedir');
            if (empty($safeMode) && empty($openBasedir)) {
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            }
            $file = curl_exec($ch);
            curl_close($ch);
        } else {
            return false;
        }

        if ($file === false || $file === '') {
            return false;
        }

        file_put_contents($dst, $file);

        return file_exists($dst);
    }
}

if (!function_exists('localizator3InstallTransportPackage')) {
    /**
     * @param modX|\MODX\Revolution\modX $modx
     * @param string $packageName
     * @return array{success: int, message: string}
     */
    function localizator3InstallTransportPackage($modx, $packageName)
    {
        $providerClass = class_exists(\MODX\Revolution\Transport\modTransportProvider::class)
            ? \MODX\Revolution\Transport\modTransportProvider::class
            : 'transport.modTransportProvider';
        $packageClass = class_exists(\MODX\Revolution\Transport\modTransportPackage::class)
            ? \MODX\Revolution\Transport\modTransportPackage::class
            : 'transport.modTransportPackage';

        $provider = $modx->getObject($providerClass, [
            'service_url:LIKE' => '%modstore.pro%',
        ]);
        if (!$provider) {
            $provider = $modx->getObject($providerClass, [
                'service_url:LIKE' => '%simpledream.ru%',
            ]);
        }
        if (!$provider) {
            $provider = $modx->getObject($providerClass, ['name' => 'modstore.pro']);
        }
        if (!$provider) {
            $provider = $modx->getObject($providerClass, 1);
        }
        if (!$provider) {
            return [
                'success' => 0,
                'message' => "Transport provider not found for <b>{$packageName}</b>.",
            ];
        }

        $modx->getVersionData();
        $productVersion = $modx->version['code_name'] . '-' . $modx->version['full_version'];

        $response = $provider->request('package', 'GET', [
            'supports' => $productVersion,
            'query' => $packageName,
        ]);

        if (empty($response) || empty($response->response)) {
            return [
                'success' => 0,
                'message' => "Could not find <b>{$packageName}</b> in MODX repository.",
            ];
        }

        $foundPackages = simplexml_load_string($response->response);
        if ($foundPackages === false) {
            return [
                'success' => 0,
                'message' => "Invalid provider response while searching for <b>{$packageName}</b>.",
            ];
        }

        foreach ($foundPackages as $foundPackage) {
            if ((string)$foundPackage->name !== $packageName) {
                continue;
            }

            $signature = (string)$foundPackage->signature;
            $sig = explode('-', $signature);
            $versionSignature = explode('.', $sig[1] ?? '1.0.0');
            $url = (string)$foundPackage->location;
            $destination = $modx->getOption('core_path') . 'packages/' . $signature . '.transport.zip';

            if (!localizator3DownloadTransportPackage($url, $destination)) {
                return [
                    'success' => 0,
                    'message' => "Could not download package <b>{$packageName}</b>.",
                ];
            }

            /** @var modTransportPackage $package */
            $package = $modx->newObject($packageClass);
            $package->set('signature', $signature);
            $package->fromArray([
                'created' => date('Y-m-d H:i:s'),
                'updated' => null,
                'state' => 1,
                'workspace' => 1,
                'provider' => $provider->get('id'),
                'source' => $signature . '.transport.zip',
                'package_name' => $packageName,
                'version_major' => $versionSignature[0] ?? 1,
                'version_minor' => !empty($versionSignature[1]) ? $versionSignature[1] : 0,
                'version_patch' => !empty($versionSignature[2]) ? $versionSignature[2] : 0,
            ], '', true, true);

            if (!empty($sig[2])) {
                $releaseParts = preg_split('/([0-9]+)/', $sig[2], -1, PREG_SPLIT_DELIM_CAPTURE);
                if (is_array($releaseParts) && !empty($releaseParts)) {
                    $package->set('release', $releaseParts[0]);
                    $package->set('release_index', $releaseParts[1] ?? '0');
                } else {
                    $package->set('release', $sig[2]);
                }
            }

            if ($package->save() && $package->install()) {
                return [
                    'success' => 1,
                    'message' => "<b>{$packageName}</b> was successfully installed.",
                ];
            }

            return [
                'success' => 0,
                'message' => "Could not install package <b>{$packageName}</b>.",
            ];
        }

        return [
            'success' => 0,
            'message' => "Could not find <b>{$packageName}</b> in MODX repository.",
        ];
    }
}

return true;
