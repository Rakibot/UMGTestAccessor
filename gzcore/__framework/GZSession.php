<?php

namespace GZCore\__framework;
use \Exception;
use GZCore\__framework\db\GZORM;
use GZCore\__framework\db\GZWhere;

class GZSession
{
    private static $user = [];
    private static $userId = null;
    private static $userRolId = null;
    private static $token = null;
    private static $key = null;
    private static $timeOut = null;
    private static $aud = null;
    private static $userIdField = '';

    private static function init()
    {
        if (GZSession::$key == null || GZSession::$timeOut == null || GZSession::$aud == null)
        {
            if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') { 
                $protocol = "https";
            } else {
                $protocol = "http";
            }
            GZSession::$key = GZConfig::$config->jwt->key;
            GZSession::$timeOut = GZConfig::$config->jwt->timeOut;
            GZSession::$aud = $protocol . "://" . $_SERVER['HTTP_HOST'];
        }
    }

    public static function getUser(): array {
        if (!GZGuard::userWasConfigured()) {
            throw new Exception('The user table is not configured', 1000);
        }
        $userMap = GZUtils::getTableMap(GZConfig::$config->userTable->name);
        $user = GZORM::select(GZConfig::$config->userTable->name)
            ->addWhere(new GZWhere($userMap['primaryKey'], '=', GZSession::getUserId()))
            ->doQuery();
        if (empty($user)) {
            throw new Exception('The user does not exists');
        }
        return $user[0];
    }

    public static function getUserId(): int {
        if (GZSession::$userId != null) {
            return GZSession::$userId;
        }
        $userMap = GZUtils::getTableMap(GZConfig::$config->userTable->name);
        GZSession::$userId = GZSession::$user[$userMap['primaryKey']];
        return GZSession::$userId;
    }

    public static function getRolId(): int {
        // If the table rol wasn't configurated, throws an exception
        if (!GZGuard::userContainsRol()) {
            throw new Exception("The user doesn't contains rol", 1000);
        }
        // If the rol was be geted from a previous search, then return this rol
        if (GZSession::$userRolId != null) {
            return GZSession::$userRolId;
        }

        $userMap = GZUtils::getTableMap(GZConfig::$config->userTable->name);
        $rolField;
        foreach ($userMap['columns'] as $column) {
            if (!empty($column['reference_to']) && $column['reference_to']['table'] === GZConfig::$config->rolTable->name) {
                $rolFieldName = $column['name'];
                break;
            }
        }
        GZSession::$userRolId = GZSession::$user[$rolFieldName];
        return GZSession::$userRolId;
    }

    public static function getToken(): string {
        return GZSession::$token;
    }

    public static function existsSession(): bool {
        return !empty(GZSession::$user);
    }

    public static function verifyJwt($token): bool {
        GZSession::init();
        $tokenSplited = explode('.', $token);
        if (count($tokenSplited) !== 3) {
            throw new Exception('El token enviado tiene un formato incorrecto', 403);
        }

        $jwt = $tokenSplited[0] . '.' . $tokenSplited[1];
        if (GZSession::sign($jwt) !== str_replace("\\", '', $tokenSplited[2])) {
            throw new Exception('El token enviado no es valido', 403);
        }

        $header = json_decode(base64_decode($tokenSplited[0]));
        $body = json_decode(base64_decode($tokenSplited[1]));
        $now = time()*1000;

        if ($now < $body->iat) {
            throw new Exception('La fecha de creación es invalida', 403);
        }

        if ($now > $body->exp) {
            throw new Exception('El token ya ha caducado', 403);
        }

        GZSession::$user = (array)$body->usuario;
        GZSession::$token = $token;
        return true;
    }

    public static function saveUser(array $user) {
        GZSession::init();
        $header = [
            'typ' => 'JWT',
            'alg' => 'HS256'
        ];

        $body = [
            'iss' => GZSession::$aud,
            'aud' => GZSession::$aud,
            'iat' => time()*1000,
            'exp' => (time()*1000) + GZSession::$timeOut * 1000,
            'usuario' => $user
        ];

        $headerB64 = str_replace('=', '', base64_encode(json_encode($header)));
        $bodyB64 = str_replace('=', '', base64_encode(json_encode($body)));
        $jwt = $headerB64 . '.' . $bodyB64;
        GZSession::$user = $user;
        GZSession::$token = $jwt . '.' . GZSession::sign($jwt);
    }

    public static function updateToken(): string
    {
        if (empty(GZSession::$user)) {
            throw new Exception("To upload token, is necesary save an user first", 1000);
        }
        GZSession::saveUser(GZSession::$user);
        return GZSession::$token;
        
    }

    public static function addNewTokenToHeaders($token)
    {
        header('Authenticate: ' . $token);
        header('Access-Control-Expose-Headers: Authenticate');
    }

    private static function sign($token)
    {
        return str_replace('=', '', base64_encode(hash_hmac('sha256', $token, GZSession::$key, true)));
    }
}
