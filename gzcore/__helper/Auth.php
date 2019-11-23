<?php

/**
 * File that help to developer the basics operations from the users
 * @author Raúl Alexander Mendoza Muñoz
 * @version 2.0
 */

namespace GZCore\__helper;
use \GZCore\__framework\GZConfig;
use \GZCore\__framework\GZSession;
use \GZCore\__framework\GZUtils;
use \GZCore\__framework\GZGuard;
use \GZCore\__framework\GZErrorHandler;
use \GZCore\__framework\db\GZORM;
use \GZCore\__framework\db\GZWhere;
use \Exception;
use \DateTime;
use \DateInterval;

define('UNACTIVATED_ACCOUNT', 'unactivated account');
define('WRONG_LOGIN', 'The user or password are wrong');
define('MAXIMUM_OF_ATTEMPTS_REACHED', 'Maximum of attempts reached');
define('USER_TABLE_WRONG', 'The user table was not configurated');

/**
 * Class that handle the login, creation, recovery pass and activate users
 * @package GZCore\__helper
 * @subpackage Auth
 */
class Auth extends GZHelper {

    /**
     * Constructor that define which methods can access withouth a
     * authentication token
     */
    public function __construct() {
        $this->addMethodWithoutAuth('registryUser');
        $this->addMethodWithoutAuth('login');
        $this->addMethodWithoutAuth('activateAccount');
    }

    /**
     * Method that create a new user, store it on the database and crypt the
     * password of it
     * @param array $payload The data sended from the client, this must contains
     * at least the follow indexes: user, password and email. Some times user
     * and email can reference the same field.
     * @return array The user created without the password field.
     */
    public function registryUser(array $payload): array {
        if (!GZGuard::userWasConfigured()) {
            throw new Exception(USER_TABLE_WRONG, 1000);
        }
        $this->onlyFor('POST');
        $userField = GZConfig::$config->userTable->userField;
        $passField = GZConfig::$config->userTable->passField;
        $emailField = GZConfig::$config->userTable->emailField;
        $verifyEmailTokenField = GZConfig::$config->userTable->verifyEmailTokenField;
        if (empty($payload[$userField]) || empty($payload[$passField])) {
            throw new Exception("The user and password cann't be empty", 1000);
        }
        if (empty($payload[$emailField])) {
            throw new Exception("The user email cann't be empty", 1000);
        }
        
        $verifyEmailToken = GZUtils::getUuid();
        $payload[$passField] = $this->encriptPassword($payload[$passField]);
        $payload[$verifyEmailTokenField] = $verifyEmailToken;
        $result = GZORM::insert(GZConfig::$config->userTable->name)
            ->addValues($payload)
            ->doQuery();
        GZUtils::sendEmail($payload[$emailField], 'Account created', GZUtils::renderEmailBody('registered_user.html', [
            'tokenUrl' => $this->getServerURL() . "/_Auth/activateAccount?token=$verifyEmailToken"
        ]));
        return $this->removeSensitiveFields($result);
    }

    public function updateUser(array $payload) {
        if (!GZGuard::userWasConfigured()) {
            throw new Exception(USER_TABLE_WRONG, 1000);
        }
        $this->onlyFor('PUT');
        $userIsAdmin;
        $userTable = GZConfig::$config->userTable->name;
        $userMap = GZUtils::getTableMap($userTable);
        $userPassField = GZConfig::$config->userTable->passField;
        $userPrimaryKey = $userMap['primaryKey'];
        try {
            $userIsAdmin = GZSession::getRolId() == GZConfig::$config->rolTable->adminId;
        } catch(Exception $e) {
            $userIsAdmin = false;
        }

        $where = null;
        if ($userIsAdmin && !empty($payload[$userPrimaryKey])) {
            $where = new GZWhere($userPrimaryKey, '=', $payload[$userPrimaryKey]);
        } else {
            $where = new GZWhere($userPrimaryKey, '=', GZSession::getUserId());
        }

        if (!empty($payload[$userPassField])) {
            $payload[$userPassField] = $this->encriptPassword($payload[$userPassField]);
        }

        $result = GZORM::update($userTable)->addValues($payload)->addWhere($where)->doQuery();
        if(!empty($result)){
            $result = $result[0];
            if ($result) {
                $user = GZORM::select($userTable)->addWhere($where)->doQuery();
                return $this->removeSensitiveFields($user[0]);
            }
        }
        return $result;
    }

    /**
     * Find the user that match with the token sended and delete this token. If
     * a user contains data on her/his token, this user is considered as
     * disabled.
     * @param array $payload The data sended from the client, this must be an
     * array with a unique index, this index must be token.
     * @return array The user activated without her/his password
     */
    public function activateAccount(array $payload): array {
        if (!GZGuard::userWasConfigured()) {
            throw new Exception(USER_TABLE_WRONG, 1000);
        }
        $this->onlyFor('GET');
        $userTable = GZConfig::$config->userTable->name;
        $userMap = GZUtils::getTableMap($userTable);
        $userIdField = $userMap['primaryKey'];
        $verifyEmailTokenField = GZConfig::$config->userTable->verifyEmailTokenField;
        if (empty($payload['token'])) {
            throw new Exception("The token can't be empty", 1000);
        }

        $user = GZORM::select($userTable)
            ->addWhere(new GZWhere($verifyEmailTokenField, '=', $payload['token']))
            ->doQuery();

        if (empty($user)) {
            throw new Exception("The token doesn't exists", 1000);
        }

        $user = $user[0];
        $user = GZORM::update($userTable)
            ->addValues([
                $verifyEmailTokenField => null
            ])->addWhere(new GZWhere($userIdField, '=', $user[$userIdField]))
            ->doQuery();

        return $this->removeSensitiveFields($user[0]);
    }

    /**
     * 
     */
    public function login(array $payload): string {
        $this->onlyFor('POST');
        //$rolForeignKey = $this->getUserRolField();
        //$userField = GZConfig::$config->userTable->userField;
        $passField = GZConfig::$config->loginCode->passField;
        $userTable = GZConfig::$config->loginCode->name;
        //$maxWrongLogin = GZConfig::$config->userTable->maxWrongLogin;
        //$userMap = GZUtils::getTableMap($userTable);
        //$verifyEmailTokenField = GZConfig::$config->userTable->verifyEmailTokenField;
        //$userPrimaryKey = $userMap['primaryKey'];

        if (empty($payload[$passField])) {
            throw new Exception("The password cann't be empty", 1000);
        }

        $user = GZORM::execute("SELECT * FROM $userTable ORDER BY Id DESC LIMIT 1");
        if (empty($user)) {
            throw new Exception(WRONG_LOGIN, 1000);
        }

        $user = $user[0];
        /*if (!empty($user[$verifyEmailTokenField])) {
            throw new Exception('This account is not activated', 1000);
        }
        $wrongLoginCount = GZUtils::restoreData("user_{$payload[$userField]}") ?? 0;
        if ($wrongLoginCount >= $maxWrongLogin) {
            $this->handleWrongLogin($wrongLoginCount, $payload[$userField]);
            throw new Exception(MAXIMUM_OF_ATTEMPTS_REACHED, 1000);
        }*/
        if ($user[$passField] != $payload[$passField]) {
            //$this->handleWrongLogin($wrongLoginCount, $payload[$userField]);
            throw new Exception(WRONG_LOGIN, 1000);
        }

        /*$userID =$user[$userPrimaryKey];
        $languages = GZORM::execute("SELECT ul.*,l.short_name FROM user_language AS ul INNER JOIN `language` AS l ON l.id = ul.language_id WHERE ul.user_id = $userID AND ul.`default` = 1");
        $language;*/
        
        /*if(empty($languages)){
            $language = "es";
        }else{
            $language=$languages[0]["short_name"];
        }*/

        /*if (!empty($rolForeignKey)) {
            GZSession::saveUser([
                $userPrimaryKey => $user[$userPrimaryKey],
                $rolForeignKey => $user[$rolForeignKey],
                $userField => $user[$userField],
                'name' => $user["name"],
                "last_name" => $user["last_name"],
                "language" => $language
            ]);
        } else {
            GZSession::saveUser([
                $userPrimaryKey => $user[$userPrimaryKey],
            ]);
        }
        GZUtils::removeData("user_{$payload[$userField]}");*/
        GZSession::saveUser([
            $passField => $user[$passField],
        ]);
        return GZSession::getToken();
    }

    public function validateSession(array $payload){
        $this->onlyFor('POST');
        $passField = GZConfig::$config->loginCode->passField;
        $userTable = GZConfig::$config->loginCode->name;
        $token = GZSession::getToken();
        $data = explode(".",$token)[1];
        GZErrorHandler::printInfo($data);
        GZErrorHandler::printInfo(base64_decode($data));
        $user = json_decode(base64_decode($data),true)["usuario"];
        $code = $user[$passField];

        $validCode = GZORM::execute("SELECT * FROM $userTable ORDER BY Id DESC LIMIT 1");
        if (empty($validCode)) {
            throw new Exception(WRONG_LOGIN, 1000);
        }
        return ($validCode[0][$passField] == $code);
    }

    public function canTakeExam(array $payload){
        $this->onlyFor('GET');
        GZErrorHandler::printInfo("Can take exam");

        $alumn = GZORM::select('umg_derex_alumnos')
                        ->addWhere(new GZWhere("Matricula","=",$payload["code"]))
                        ->doQuery();

        if(empty($alumn)){
            throw new Exception("No found",404);
        }
        return $alumn;
    }

    public function recoveryPass(array $payload) {
        if (!GZGuard::userWasConfigured()) {
            throw new Exception(USER_TABLE_WRONG, 1000);
        }
        
    }

    public function logout(array $payload): string {
        if (!GZGuard::userWasConfigured()) {
            throw new Exception(USER_TABLE_WRONG, 1000);
        }
        $this->onlyFor('POST');
        return 'Cerraste la sesión';
    }

    private function encriptPassword(string $password): string {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    private function verifyPassword(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }

    private function handleWrongLogin(int $wrongLoginCount, string $user) {
        sleep(3);
        GZUtils::storeData("user_$user", $wrongLoginCount + 1, GZConfig::$config->userTable->blockingTime);
    }

    private function getServerURL(): string {
        $request = preg_replace("/\/_Auth\/.*$/", '', $_SERVER['REQUEST_URI']);
        return "http://{$_SERVER['HTTP_HOST']}$request";
    }

    private function getUserRolField(): string {
        $userTable = GZConfig::$config->userTable->name;
        $rolTable = GZConfig::$config->rolTable->name;
        $userMap = GZUtils::getTableMap($userTable);
        if (GZGuard::userContainsRol()) {
            foreach ($userMap['columns'] as $column) {
                if (!empty($column['reference_to']) && $column['reference_to']['table'] === $rolTable) {
                    return $column['name'];
                }
            }
        }
        return '';
    }

    private function removeSensitiveFields(array $user): array {
        $passField = GZConfig::$config->userTable->passField;
        $verifyEmailTokenField = GZConfig::$config->userTable->verifyEmailTokenField;
        unset($user[$passField]);
        unset($user[$verifyEmailTokenField]);
        return $user;
    }

    public function getUser(array $payload){
        if(!array_key_exists("user_id",$payload)){
            throw new Exception("user_id not found",1001);
        }

        $userTable = GZConfig::$config->userTable->name;
        $userMap = GZUtils::getTableMap($userTable);
        $userIdField = $userMap['primaryKey'];

        $userId = $payload["user_id"];

        $user = GZORM::select($userTable)
                ->addWhere(new GZWhere($userIdField,"=",$userId))
                ->doQuery();

        if(empty($user)){
            throw new Exception("User not found",1001);
        }

        return $this->removeSensitiveFields($user[0]);
    }
}