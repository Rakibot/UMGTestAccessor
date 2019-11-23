<?php

namespace GZCore\__framework;
use \DateTime;
use \DateInterval;
use \APCUIterator;

define('DATE_FORMAT', 'Y-m-d h:i:s');

class GZStore {
    private static $applicationName;
    private static $fileContent;
    private static $filePath;

    public static function init()
    {
        GZStore::$applicationName = GZConfig::$config->application->name;
        GZStore::$fileContent = [];
        GZStore::$filePath = GZUtils::pathJoin(GZUtils::getCurrentPath(), '..', GZStore::$applicationName . "-cache.json");
        
    }
    
    public static function storeInCache(string $key, $value, int $ttl = 0) {
        apcu_store(GZStore::$applicationName . "_$key", $value, $ttl);
    }

    public static function storeInFile(string $key, $value, int $ttl = 0, bool $persistAtEndRequest = true) {
        $now = new DateTime();
        GZStore::$fileContent[$key] = [
            'body' => $value,
            'expirationDate' => $ttl == 0 ? null : $now->add(new DateInterval("PT{$ttl}S"))->format(DATE_FORMAT)
        ];
        if ($persistAtEndRequest) {
            GZStore::writeFileCache();
        }
    }

    public static function restoreFromCache(string $key) {
        $success;
        $result = apcu_fetch(GZStore::$applicationName . "_$key", $success);
        $foo = apcu_cache_info();
        return $success ? $result : null;
    }

    public static function restoreFromFile(string $key) {
        GZStore::readFileCache();
        if (empty(GZStore::$fileContent[$key])) {
            return null;
        }

        $value = GZStore::$fileContent[$key];
        if ($value['expirationDate'] == null) {
            return $value['body'];
        }

        $now = new DateTime();
        $expirationDate = new DateTime($value['expirationDate']);
        if ($now >= $expirationDate) {
            return $value['body'];
        }

        GZStore::deleteFromFile($key);
        return null;
    }

    public static function getAllDataFromCache(): array {
        $elements = [];
        $applicationName = GZStore::$applicationName . "_";
        foreach(new APCUIterator("/^$applicationName/") as $counter) {
            $elements[ str_replace($applicationName, '', $counter['key']) ] = $counter['value'];
        }
        return $elements;
    }

    public static function getAllDataFromFile(): array {
        GZStore::readFileCache();
        return GZStore::$fileContent;
    }

    public static function deleteFromCache(string $key) {
        apcu_delete(GZStore::$applicationName . "_$key");
    }

    public static function deleteFromFile(string $key, bool $persistAtEndRequest = true) {
        unset(GZStore::$fileContent[$key]);
        if ($persistAtEndRequest) {
            GZStore::writeFileCache();
        }
    }

    public static function clearCache() {
        apcu_clear_cache();
    }

    public static function clearFile() {
        GZStore::$fileContent = [];
        GZStore::writeFileCache();        
    }

    private static function readFileCache() {
        if (!empty(GZStore::$fileContent)) {
            return;
        }

        if (!file_exists(GZStore::$filePath)) {
            return;
        }
        GZUtils::startLockRequests();
        $fileContent = file_get_contents(GZStore::$filePath);
        if ($fileContent !== false) {
            GZStore::$fileContent = json_decode($fileContent, true);
            if (json_last_error() != JSON_ERROR_NONE) {
                GZStore::$fileContent = [];
            }
        }
        GZUtils::endLockRequests();
    }

    private static function writeFileCache() {
        GZUtils::startLockRequests();
        $fileContent = json_encode(GZStore::$fileContent);
        file_put_contents(GZStore::$filePath, $fileContent);
        GZUtils::endLockRequests();
    }
}