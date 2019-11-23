<?php

namespace GZCore\__framework;
use \Exception;
use \GZCore\__framework\db\GZORM;
use \GZCore\__framework\db\GZWhere;
use \GZCore\__framework\db\GZJoin;

define('CONTAINS_USER', 'contains_user');
define('USER_CONTAINS_ROL', 'user_contains_rol');
define('SECURITY_IS_OK', 'security_is_ok');
define('GUARD_FILTER', '__guard_filter');

class GZGuard {

    public static function verifyFrameworkTables() {
        if (!GZGuard::userConfigIsCorrect()) {
            return;
        }
        GZGuard::setContainsUser();

        if (!GZGuard::rolConfigIsCorrect()) {
            return;
        }
        GZGuard::setUserContainsRol();

        if (!GZGuard::metaConfigIsCorrect()) {
            return;
        }
        GZGuard::setSecurityIsOk();
        GZGuard::setTablePermissions();
    }

    public static function verifyAccess($command) {
        
            try {
                GZGuard::verifySession();
            } catch(Exception $ex){
                if (preg_match('/^_\w/', $command) !== 1) {
                    throw $ex;
                }
            }
        
    }

    public static function addSecurityFilters(array &$payload, string $tableName) {
        $userTable = GZConfig::$config->userTable->name;
        $userMap = GZUtils::getTableMap($userTable);
        $userPkField = $userMap['primaryKey'];
        $userId = GZsession::getUserId();
        $tableMap = GZUtils::getTableMap($tableName);
        $userRol = null;
        
        if (!empty($payload[$userPkField]) && $userId == $payload[$userPkField] && $tableName == $userTable) {
            $request = $_SERVER['REQUEST_METHOD'];
            if ($request == 'PUT' || $request == 'POST') {
                throw new Exception('The user just may be created or updated from the correspond helper', 1000);
            }
            return;
        }

        try {
            $userRol = GZsession::getRolId();
        } catch(Exception $e) {
            return;
        }

        $isAdmin = $userRol == GZConfig::$config->rolTable->adminId;
        if (!$isAdmin && in_array($tableName, [
            $userTable,
            GZConfig::$config->rolTable->name,
            GZConfig::$config->metaTable->name,
            GZConfig::$config->metaRolTable->name
        ])) {
            throw new Exception("Only admins can edit data from $tableName");
        } else if ($isAdmin) {
            return;
        }

        $relation = GZGuard::getRelationWithUser($tableName);
        if (count($relation) == 1) {
            $ownerColumn = $relation[0]['foreignColumn'];
            $payload[$ownerColumn] = $userId;
            $payload['__guard_filter'] = $ownerColumn;
        } else if (count($relation) > 1) {
            $payload['__joins'] = [
                'relations' => $relation,
                'condition' => new GZWhere("$userTable.{$userMap['primaryKey']}", '=', $userId)
            ];
        }
    }

    public static function addFiltersToGet(array &$payload, string $tableName) {
        if (!GZGuard::userWasConfigured() || in_array($tableName, GZConfig::$config->publicTables->table)) {
            return;
        }

        $subTables = [];
        foreach ($payload as $key => $value) {
            $keySplited = preg_split("/(\.|\!)/", $key, 2);
            if (count($keySplited) == 2) {
                $subtable = &$subTables[$keySplited[0]];
                if (empty($subtable)) {
                    $subtable = [];
                }
                $subtable[$keySplited[1]] = $value;
            }
        }
        foreach ($subTables as $key => $value) {
            GZGuard::addFiltersToGet($value, $key);
            foreach ($value as $subKey => $subValue) {
                $payload["$key.$subKey"] = $subValue;
            }
        }

        $userRol = null;
        try {
            $userRol = GZsession::getRolId();
        } catch(Exception $e) {
            return;
        }
        $userTableName = GZConfig::$config->userTable->name;
        if ($userRol != GZConfig::$config->rolTable->adminId) {
            $userMap = GZUtils::getTableMap($userTableName);
            $userId = GZSession::getUserId();
            if ($tableName == $userTableName) {
                $payload[$userMap['primaryKey']] = $userId;
                return;
            }
            $relation = GZGuard::getRelationWithUser($tableName);
            if (count($relation) == 1) {
                $payload[$relation[0]['foreignColumn']] = $userId;
            } else if (count($relation) > 1) {
                $payload['__joins'] = [
                    'relations' => $relation,
                    'condition' => new GZWhere("$userTableName.{$userMap['primaryKey']}", '=', $userId)
                ];
            }
        }
    }

    public static function getTableMapWithoutSensitiveData(string $tableName): array {
        if (!GZGuard::userWasConfigured() || $tableName !== GZConfig::$config->userTable->name) {
            return GZUtils::getTableMap($tableName);
        }

        $tableMap = GZUtils::getTableMap($tableName);
        $columns = [];
        $columnsWithSensitiveData = [
            GZConfig::$config->userTable->passField,
            GZConfig::$config->userTable->verifyEmailTokenField
        ];
        foreach($tableMap['columns'] as $column) {
            if (in_array($column['name'], $columnsWithSensitiveData)) {
                continue;
            }
            $columns[] = $column;
        }
        $tableMap['columns'] = $columns;
        return $tableMap;
    }

    public static function userWasConfigured(): bool {
        return GZUtils::restoreData(CONTAINS_USER) ?? false;
    }

    public static function userContainsRol(): bool {
        return GZUtils::restoreData(USER_CONTAINS_ROL) ?? false;
    }

    public static function securityIsOk(): bool {
        return GZUtils::restoreData(SECURITY_IS_OK) ?? false;
    }

    // Las unicas operaciones permitidas son: Create, Read, Update y Delete
    public static function rolHavePermission(string $operation, string $tableName): bool {
        if (!GZGuard::securityIsOk()) { // Devuelve Ok cuando la seguridad no está al 100%
            return true;
        }
        $tableMap = GZUtils::getTableMap($tableName);
        $rolesWithPermission = $tableMap["rolesTo$operation"];
        if ($rolesWithPermission === null) { // No fue establecido un permiso personalizado
            return true;
        }

        return in_array(GZSession::getRolId(), $rolesWithPermission);
    }

    public static function canInsertAccordingFilters(array $payload): bool {
        if (empty($payload['__joins'])) {
            return true;
        }

        // Recorro la lista en orden inverso para obtener las relaciones desde el usuario hasta la tabla actual
        $userTableName = GZConfig::$config->userTable->name;
        $userMap = GZUtils::getTableMap($userTableName);
        $select = GZORM::select($userTableName);
        $select->addFields("$userTableName.{$userMap['primaryKey']}");
        $joins = $payload['__joins'];
        $relations = $joins['relations'];
        $firstRelation = $relations[0];
        $foreignColumn = $firstRelation['foreignColumn'];
        for ($i = count($relations) - 1; $i > 0; $i--) {
            $relation = $relations[$i];
            $select->addJoin(GZJoin::inner($relation['foreignTable'], "{$relation['tableName']}.{$relation['ownerColumn']}", "{$relation['foreignTable']}.{$relation['foreignColumn']}"));
        }
        $select->addWhere($joins['condition']);
        $select->addWhere(new GZWhere("{$firstRelation['tableName']}.{$firstRelation['ownerColumn']}", '=', $payload[$foreignColumn]));
        return count($select->doQuery()) > 0;
    }

    private static function userConfigIsCorrect(): bool {
        $userMap = GZGuard::getTableMap('userTable');
        if (empty($userMap)) {
            return false;
        }
        $fieldsRequired = array_unique([
            GZConfig::$config->userTable->userField,
            GZConfig::$config->userTable->emailField,
            GZConfig::$config->userTable->passField,
            GZConfig::$config->userTable->verifyEmailTokenField
        ]);
        if (!GZGuard::structureIsCorrect($userMap, $fieldsRequired)) {
            throw new Exception("The table " . GZConfig::$config->userTable->name . " doesn't contains the required fields", 1000);
        }
        return true;
    }

    private static function rolConfigIsCorrect(): bool {
        $rolMap = GZGuard::getTableMap('rolTable');
        if (empty($rolMap)) {
            return false;
        }
        if (!GZGuard::structureIsCorrect($rolMap, [ GZConfig::$config->rolTable->rolField ])) {
            throw new Exception("The table " . GZConfig::$config->rolTable->name . "doesn't contains the required fields", 1000);
        }
        return true;
    }

    private static function metaConfigIsCorrect(): bool {
        $mapMetaValues = [
            [
                'tableName' => 'metaTable',
                'requiredFields' => [
                    GZConfig::$config->metaTable->tableField
                ]
            ],
            [
                'tableName' => 'metaRolTable',
                'requiredFields' => [
                    GZConfig::$config->metaRolTable->canCreateField,
                    GZConfig::$config->metaRolTable->canReadField,
                    GZConfig::$config->metaRolTable->canUpdateField,
                    GZConfig::$config->metaRolTable->canDeleteField
                ]
            ]
        ];

        foreach ($mapMetaValues as $meta) {
            $tableMap = GZGuard::getTableMap($meta['tableName']);
            if (empty($tableMap)) {
                return false;
            }
            if (!GZGuard::structureIsCorrect($tableMap, $meta['requiredFields'])) {
                throw new Exception("The table " . GZConfig::$config->{$meta['tableName']}->name . " doesn't contains the required fields", 1000);
            }
        }
        return true;
    }

    private static function setTablePermissions() {
        $metaFK;
        $rolFK;
        $tablesMap = GZUtils::getAllTablesMap();
        $metaRolTable = GZConfig::$config->metaRolTable;
        $metaTableName = GZConfig::$config->metaTable->name;
        $metaTable = $tablesMap[$metaTableName];
        $crud = [
            $metaRolTable->canCreateField => null,
            $metaRolTable->canReadField => null,
            $metaRolTable->canUpdateField => null,
            $metaRolTable->canDeleteField => null,
        ];
        foreach ($tablesMap[$metaRolTable->name]['columns'] as $column) {
            if (!empty($column['reference_to'])) {
                if ($column['reference_to']['table'] === $metaTableName) {
                    $metaFK = $column['name'];
                }
                else if ($column['reference_to']['table'] === GZConfig::$config->rolTable->name) {
                    $rolFK = $column['name'];
                }
            }
        }
        foreach ($crud as $key => &$value) {
            $where2 = new GZWhere($metaFK, '=', "{$metaTable['name']}.{$metaTable['primaryKey']}");
            $where = new GZWhere($key, '=', 1);
            $value = GZORM::select($metaRolTable->name)
                ->groupConcat($rolFK)
                ->addWhere($where->enableFullCondition())
                ->addWhere($where2->enableFullCondition())
                ->setGroupBy($metaFK)
                ->setAlias($key);
        }

        $metaData = GZORM::select($metaTableName)
            ->addFields(
                GZConfig::$config->metaTable->tableField . ' as table_name',
                $crud[$metaRolTable->canCreateField],
                $crud[$metaRolTable->canReadField],
                $crud[$metaRolTable->canUpdateField],
                $crud[$metaRolTable->canDeleteField]
            )->doQuery();
        
        foreach ($metaData as $singleMeta) {
            if (empty($tablesMap[$singleMeta['table_name']])) {
                continue;
            }
            $table = &$tablesMap[$singleMeta['table_name']];
            $table['rolesToCreate'] = explode(',', $singleMeta[$metaRolTable->canCreateField]);
            $table['rolesToRead'] = explode(',', $singleMeta[$metaRolTable->canReadField]);
            $table['rolesToUpdate'] = explode(',', $singleMeta[$metaRolTable->canUpdateField]);
            $table['rolesToDelete'] = explode(',', $singleMeta[$metaRolTable->canDeleteField]);
            GZUtils::storeData("__{$table['name']}", $table, 0, false);
        }
    }

    private static function getTableMap(string $tableName): array {
        if (empty(GZConfig::$config->{$tableName}) || empty(GZConfig::$config->{$tableName}->name)) {
            return [];
        }

        $tableMap;
        try {
            $tableMap = GZUtils::getTableMap(GZConfig::$config->{$tableName}->name);
        } catch(Exception $e) {
            return [];
        }
        return $tableMap;
    }

    private static function structureIsCorrect(array $tableMap, array $fieldsRequired): bool {
        foreach ($tableMap['columns'] as $column) {
            $index = array_search($column['name'], $fieldsRequired);
            if ($index !== false) {
                unset($fieldsRequired[$index]);
            }
        }
        return empty($fieldsRequired);
    }

    private static function setContainsUser() {
        GZUtils::storeData(CONTAINS_USER, true, 0, false);
    }

    private static function setUserContainsRol() {
        GZUtils::storeData(USER_CONTAINS_ROL, true, 0, false);
    }

    private static function setSecurityIsOk() {
        GZUtils::storeData(SECURITY_IS_OK, true, 0, false);
    }

    private static function verifySession() {
        $headers = getallheaders();
        if (array_key_exists('Authorization', $headers)) {
            $authorization = 'Authorization';
        } else if (array_key_exists('authorization', $headers)) {
            $authorization = 'authorization';
        } else {
            return;
        }
        GZSession::verifyJwt($headers[$authorization]);
        $newToken = GZSession::updateToken();
        header("Authorization: $newToken");
    }

    private static function getRelationWithUser(string $tableName): array {
        return GZGuard::bfs($tableName);
        /*$tableMap = GZUtils::getTableMap($tableName);
        $userTable = GZConfig::$config->userTable->name;
        foreach ($tableMap['columns'] as $column) {
            if (!empty($column['reference_to']) && $column['reference_to']['table'] === $userTable) {
                return $column;
            }
        }
        return [];*/
    }

    private static function bfs(string $tableName): array {
        $nodes = GZUtils::getAllTablesMap(); //genero una copia del mapeo de las tablas
        $userTable = GZConfig::$config->userTable->name;
        $node = &$nodes[$tableName];
        $node['visited'] = true;
        $node['distance'] = 0;
        $node['parent'] = null;
        $enqueue = [];
        array_unshift($enqueue, $node);
        while (count($enqueue) > 0) {
            $current_node = array_shift($enqueue);
            if ( array_key_exists("name",$current_node) && $current_node['name'] === $userTable) {
                return GZGuard::generateRoad($nodes);
            }
            if(array_key_exists("columns",$current_node)){
            foreach ($current_node['columns'] as $columnSchema) { // obtengo todas las columnas
                $childs;
                // ignoro aquellas columnas que no tienen referencias con otras tablas
                if (empty($columnSchema['reference_to']) && empty($columnSchema['referenced_by'])) {
                    continue;
                } else if (!empty($columnSchema['reference_to'])) {
                    $childs = [ $columnSchema['reference_to'] ];
                } else if (!empty($columnSchema['referenced_by'])) {
                    $childs = $columnSchema['referenced_by'];
                }
                foreach ($childs as $childSchema) {
                    $child = &$nodes[$childSchema['table']];
                    if (empty($child['visited'])) { // Si esta vacio es que aún no se le ha asignado el valor de visidado
                        $child['visited'] = true;
                        $child['distance'] = $current_node['distance'] + 1;
                        $child['parent'] = $current_node['name'];
                        $child['ownerColumn'] = $childSchema['column'];
                        $child['foreignColumn'] = $columnSchema['name'];
                        array_unshift($enqueue, $child);
                    }
                }
            }
            }
        }
        return [];
    }

    public static function generateRoad(array $nodes): array {
        $userTable = GZConfig::$config->userTable->name;
        $nodeRoad = $nodes[$userTable];
        $nodeRoadNames = [];
        while ($nodeRoad['parent'] != null) {
            array_unshift($nodeRoadNames, [
                'tableName' => $nodeRoad['name'],
                'ownerColumn' => $nodeRoad['ownerColumn'],
                'foreignColumn' => $nodeRoad['foreignColumn'],
                'foreignTable' => $nodeRoad['parent']
            ]);
            $nodeRoad = $nodes[$nodeRoad['parent']];
        }
        return $nodeRoadNames;
    }
}