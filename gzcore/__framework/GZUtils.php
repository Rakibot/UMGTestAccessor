<?php

namespace GZCore\__framework;

define('MAP_GENERATED', '__map_generated');

use \Exception;
use \PDO;
use \DateTime;
use \DateTimeZone;
use \PHPMailer\PHPMailer\PHPMailer;
use \Mustache_Engine;
use \GZCore\__framework\db\GZORM;
use \GZCore\__framework\db\GZWhere;
use \GZCore\__framework\db\GZJoin;
use \GZCore\__framework\db\engines\GZEngine;

class GZUtils
{
    private static $file;
    private static $persistData;
    private static $lockCount;

    public static function init() {
        GZUtils::$persistData = GZConfig::$config->application->persistData;
        GZUtils::$lockCount = 0;
    }

    public static function generateMap()
    {
        GZUtils::startLockRequests();
        if (GZUtils::restoreData(MAP_GENERATED)) {
            GZUtils::endLockRequests();
            return;
        }
        
        foreach (GZEngine::getAllTables() as $row)
        {
            $table = ['columns' => []];
            $tableName = $row['table_name'];
            $primaryKey = '';
            foreach (GZEngine::getTableSchema($tableName) as $columnRow) {
                $table['columns'][] = [
                    'name' => $columnRow['name'],
                    'type' => strtolower($columnRow['type']),
                    'key' => $columnRow['key'],
                    'sercheable' => false,
                    'editable' => true,
                    'reference_to' => $columnRow['reference_to'] ?? [],
                    'referenced_by' => $columnRow['referenced_by'] ?? []
                ];
                if ($columnRow['key'] === 'PRI') {
                    $primaryKey = $columnRow['name'];
                }
            }
            $table['primaryKey'] = $primaryKey;
            $table['name'] = $tableName;
            $table['rolesToCreate'] = null;
            $table['rolesToRead'] = null;
            $table['rolesToUpdate'] = null;
            $table['rolesToDelete'] = null;
            GZUtils::storeData("__$tableName", $table, 0, false);
        }
        try {
            GZGuard::verifyFrameworkTables();
        } catch(Exception $e) {
            GZUtils::clearData();
            throw $e;
        }
        GZUtils::storeData(MAP_GENERATED, true);
        GZUtils::endLockRequests();
    }

    public static function getConnection(): PDO {
        $connectionString = GZEngine::getConnectionString();
        $dbUserName = GZConfig::$config->database->user;
        $dbUserPassword = GZConfig::$config->database->pass;
        return new PDO($connectionString, $dbUserName, $dbUserPassword);
    }

    public static function startLockRequests()
    {
        GZUtils::$lockCount++;
        if (!empty(GZUtils::$file)) {
            return;
        }
        $lockFile = GZUtils::pathJoin(GZUtils::getCurrentPath(), '..', 'request.lock');
        GZUtils::$file = fopen($lockFile, 'r');
        flock(GZUtils::$file, LOCK_EX);
    }

    public static function endLockRequests()
    {
        GZUtils::$lockCount--;
        if (GZUtils::$lockCount != 0) {
            return;
        }
        flock(GZUtils::$file, LOCK_UN);
        fclose(GZUtils::$file);
        GZUtils::$file = null;
    }

    public static function getAllTablesMap(): array {
        $allData;
        $tables = [];
        switch(GZUtils::$persistData) {
            case 'apcu':
                $allData = GZStore::getAllDataFromCache();
                break;
            default:
                $allData = GZStore::getAllDataFromFile();
                break;
        }
        foreach ($allData as $key => $value) {
            if (strpos($key, '__') === 0) {
                $tables[ str_replace('__', '', $key) ] = $value;
            }
        }
        return $tables;
    }

    public static function getTableMap(string $tableName): array
    {
        if (strpos($tableName, 'INFORMATION_SCHEMA') === 0) {
            return [];
        }
        $tableMapping = GZUtils::restoreData("__$tableName");
        if ($tableMapping == null) {
            throw new Exception("No existe un mapeo de la tabla $tableName", 1000);
        }
        return $tableMapping;
    }

    public static function storeData(string $key, $value, int $ttl = 0, bool $persistAtEndRequest = true) {
        switch(GZUtils::$persistData) {
            case 'apcu':
                GZStore::storeInCache($key, $value, $ttl);
                break;
            default:
                GZStore::storeInFile($key, $value, $ttl, $persistAtEndRequest);
        }
    }

    public static function restoreData(string $key) {
        switch(GZUtils::$persistData) {
            case 'apcu':
                return GZStore::restoreFromCache($key);
            default:
                return GZStore::restoreFromFile($key);
        }
    }

    public static function removeData(string $key, bool $persistAtEndRequest = true) {
        switch(GZUtils::$persistData) {
            case 'apcu':
                return GZStore::deleteFromCache($key);
            default:
                return GZStore::deleteFromFile($key, $persistAtEndRequest);
        }
    }

    public static function clearData() {
        GZUtils::startLockRequests();
        switch(GZUtils::$persistData) {
            case 'apcu':
                return GZStore::clearCache();
            default:
                return GZStore::clearFile();
        }
        GZUtils::endLockRequests();
    }

    public static function getCurrentPath(): string
    {
        return realpath(dirname(__FILE__));
    }

    public static function pathJoin(string ...$paths): string
    {
        return implode(DIRECTORY_SEPARATOR, $paths);
    }

    public static function removeSpecialChars(string $s): string
    {
        $_s = str_replace('.', '___', $s);
        return str_replace('!', '___', $_s);
    }

    public static function getUuid(): string
    {
        GZUtils::startLockRequests();
        $result = '';
        $sep = '-';
        $result .= GZUtils::random(32); //unsigned 32 bit integer
        $result .= $sep;
        $result .= GZUtils::random(16); //unsigned 16 bit integer
        $result .= $sep;
        $result .= GZUtils::hexString(1 << 2, 4); //version (0100)
        $result .= GZUtils::random(12);
        $result .= $sep;
        $result .= GZUtils::hexString(1 << 7 | mt_rand(0, pow(2, 6) - 1), 8);
        $result .= GZUtils::random(8); //unsigned 8  bit integer
        $result .= $sep;
        $result .= GZUtils::random(48); //unsigned 48 bit integer
        return $result;
        GZUtils::endLockRequests();
    }

    public static function sendEmail(string $to, string $subject, string $body, string ...$filesToAtach)
    {
        if (!GZConfig::$config->email->enabled) {
            return;
        }
        $mail = new PHPMailer(true);
        if (GZConfig::$config->application->debug) {
            $mail->isMail();
            //$mail->SMTPDebug = 4;                                 // Enable verbose debug output
        } else {
            $mail->SMTPDebug = 1;
        }
        try {
            $mail->isSMTP();                                      // Set mailer to use SMTP
            $mail->Host = GZConfig::$config->email->host;         // Specify main and backup SMTP servers
            $mail->SMTPAuth = true;                               // Enable SMTP authentication
            $mail->Username = GZConfig::$config->email->user;     // SMTP username
            $mail->Password = GZConfig::$config->email->pass;     // SMTP password
            if (!empty(GZConfig::$config->email->security)) {
                $mail->SMTPSecure = GZConfig::$config->email->security;// Enable TLS encryption, `ssl` also accepted
            }
            $mail->Port = GZConfig::$config->email->port;         // TCP port to connect to
        
            //Recipients
            $mail->setFrom(GZConfig::$config->email->user, 'Mailer');
            $mail->addAddress($to);                               // Add a recipient
        
            foreach ($filesToAtach as $fileToAtach) {
                $mail->addAttachment($fileToAtach);               // Add attachments
            }
        
            //Content
            $mail->isHTML(true);                                  // Set email format to HTML
            $mail->Subject = $subject;
            $mail->Body    = $body;
        
            $mail->send();
        } catch (Exception $e) {
            throw new Exception('Mailer Error: ' . $mail->ErrorInfo, 1000);
        }
    }

    public static function renderEmailBody(string $emailTemplateFile, array $params = []): string {
        $template = file_get_contents(GZUtils::pathJoin(GZUtils::getCurrentPath(), '..', '__emailTemplates', $emailTemplateFile));
        $mustache = new Mustache_Engine();
        return $mustache->render($template, $params);
    }

    public static function getUtcTime(): DateTime {
        return new DateTime('now', new DateTimeZone('UTC'));
    }

    private static function random(int $bitNum): string
    {
        $result = '';
        $bits = 16;
        $sum = 0;
        while ($bitNum != $sum && $bits > 0) {
            $bits = (($bitNum - $sum) > 16) ? 16 : $bitNum - $sum;
            $sum += $bits;
            $val = mt_rand(0, pow(2, $bits) -1);
            $result = GZUtils::hexString($val, $bits) . $result;
        }
        return $result;
    }

    private static function hexString($val, $bits)
    {
        $digits = (int) ($bits / 4 + 0.9);
        return sprintf('%0' . $digits . 'x', $val);
    }
}