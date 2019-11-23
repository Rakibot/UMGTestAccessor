<?php

namespace GZCore\__framework;
use \GZCore\__framework\db\GZORM;
use \GZCore\__framework\db\GZJoin;
use \GZCore\__framework\db\GZOrmJson;
use \GZCore\__framework\db\GZParam;
use \GZCore\__framework\db\GZWhere;
use \Exception;
use \PDO;
use \PDOStatement;

error_reporting(E_ALL|E_STRICT);
define('VARCHAR', 'varchar');

class GZDataSource
{
    private $connection = null;
    private $transactionWasInited = false;
    private $tablesSercheables = [];
    private $q = null;
    public static $schemaNames;

    function __construct()
    {
        //$this->fetchDataBaseMeta();
        $this->openConnection();
    }
	
    public function openConnection()	
    {
        if ($this->connection != null) {
            return;
        }
        try {
            $this->connection = GZUtils::getConnection();
        } catch(PDOException $e) {
            throw new Exception("El servidor no está disponible por el momento", "1000", null);
        }
    }
    
    public function delete(array $payload, string $tableName): array {
        $wheres = $this->getWheresFromPayload($payload, $tableName);
        $orm = GZORM::delete($tableName);
        foreach ($wheres as $where) {
            $orm->addWhere($where);
        }
        return $orm->doQuery();
    }
    
    public function put(array $payload, string $tableName): array {
        $wheres = $this->getWheresFromPayload($payload, $tableName);
        $orm = GZORM::update($tableName)->addValues($payload);
        foreach ($wheres as $where) {
            $orm->addWhere($where);
        }
        return $orm->doQuery();
    }
    
    public function post(array $payload, string $tableName): array {
        $orm = GZORM::insert($tableName)
            ->addValues($payload);
        return $orm->doQuery();
    }   
    //
    public function query(array $params, string $tableName): array {
        $level = 1;
        $subQueries = [];
        $columns = [];
        $paramsSubnivels = [];
        $tableMapping = GZGuard::getTableMapWithoutSensitiveData($tableName);
        $orm = GZORM::select($tableName);
        if (!empty($params['__joins'])) {
            foreach ($params['__joins']['relations'] as $join) {
                $orm->addJoin(GZJoin::inner($join['tableName'], "{$join['foreignTable']}.{$join['foreignColumn']}", "{$join['tableName']}.{$join['ownerColumn']}"));
            }
            $orm->addWhere($params['__joins']['condition']);
            unset($params['__joins']);
        }
        
        foreach ($params as $key => $value) {
            if (empty($key) || (empty($value) && $value != 0)) {
                continue;
            }
            $aux = &$paramsSubnivels;
            $lastKey = '';

            $keysSplitted =  preg_split("/(\.|\!)/", $key);
            $length = count($keysSplitted);
            $count = 1;
            foreach ($keysSplitted as $keySplitted) {
                if (!array_key_exists($keySplitted, $aux) && $count < $length) {
                    $aux[$keySplitted] = [];
                    $aux = &$aux[$keySplitted];
                }
                elseif ($count < $length) {
                    $aux = &$aux[$keySplitted];
                }
                $lastKey = $keySplitted;
                $count++;
            }
            $aux[$lastKey] = $value;
            $orm->addParam(new GZParam($key, str_replace('*', '%', $value)));
        }
        foreach ($tableMapping['columns'] as $column) {
            $columns[] = "$tableName.{$column['name']}";
        }
        $references = [];
        foreach ($tableMapping['columns'] as $ref) {
            if (!empty($ref['reference_to'])) {
                $references[] = $ref['reference_to'];
            }
        }
        $subQueries = $params['__sub_queries'] ?? -1;
        if ($subQueries != 0) {
            foreach ($references as $reference) {
                $childTableName = $reference['table'];
                if (array_key_exists($childTableName, $paramsSubnivels)) {
                    $subparams = $paramsSubnivels[$childTableName];
                    $addToWhere = true;
                } else {
                    $subparams = [];
                    $addToWhere = false;
                }
                $subquery = $this->subQueryString($tableMapping, $childTableName, $subparams, "$childTableName!", 1, $subQueries);
                $columns[] = $subquery;
                if ($addToWhere) {
                    $where = new GZWhere($subquery, 'IS NOT', 'NULL', '', false);
                    $orm->addWhere($where->enableFullCondition());
                }
            }
        }
        
        foreach ($paramsSubnivels as $key => $value) {
            if (strpos($key, '__') === 0) {
                continue;
            }
            if (!is_array($value)) {
                $isText = false;
                foreach ($tableMapping['columns'] as $column) {
                    if ($column['name'] == $key) {
                        $isText = $column['type'] == VARCHAR;
                        break;
                    }
                }

                if ($isText) {
                    $where = new GZWhere($key, 'LIKE', str_replace('*', '%', "$value"));
                } else {
                    $where = new GZWhere($key, '=', "$value");
                }
                $orm->addWhere($where);
            }
        }

        if (array_key_exists('__page', $params) && !empty($params['__page'])) {
            $orm->setSkip($params['__page']);
        }
        if (array_key_exists('__page_size', $params) && !empty($params['__page_size'])) {
            $orm->setSize($params['__page_size']);
        }
        if (array_key_exists('__group_by', $params) && !empty($params['__group_by'])) {
            $orm->setGroupBy($params['__group_by']);
        }
        if (array_key_exists('__order_by',$params) && !empty($params['__order_by'])){
            $orm->setOrderBy($params['__order_by']);
        }
        if(array_key_exists('__order_by_desc',$params) && !empty($params['__order_by_desc']) ){
            $orm->setOrderByDesc($params['__order_by_desc']);
        }
        
        GZORM::execute("SET SESSION group_concat_max_len = 100000", $this->connection);
        return $orm->addFields(...$columns)->doQuery($this->connection);
    }

    private function subQueryString(array $parentcolumnMapping, string $currentTableName, array $params, string $paramSubnivel, int $currentSubquery, int $limitSubqueries): GZOrmJson
    {
        $tableMapping = GZGuard::getTableMapWithoutSensitiveData($currentTableName);
        $subQueries = [];
        if ($limitSubqueries == -1 || $currentSubquery < $limitSubqueries) {
            foreach ($this->getReferencedColumns($tableMapping['columns']) as $reference) {
                $childTableName = $reference['table'];
                if (array_key_exists($childTableName, $params)) {
                    $subparams = $params[$childTableName];
                    $addToWhere = true;
                } else {
                    $subparams = [];
                    $addToWhere = false;
                }
                $subQueries[$childTableName] = [
                    'query' => $this->subQueryString($tableMapping, $childTableName, $subparams, "$paramSubnivel$childTableName!", $currentSubquery + 1, $limitSubqueries),
                    'addToWhere' => $addToWhere
                ];
            }
        }
        $columns = [];
        $foreignKey = null;
        $parentReference = null;
        $orm = GZOrmJson::select($currentTableName);
        $primaryKey = null;
        foreach ($tableMapping['columns'] as $column) {
            $columns[] = $column['name'];
            $columns[] = "$currentTableName.{$column['name']}";
            if ($column['key'] === 'PRI') {
                $primaryKey = $column;
            }
        }
        foreach ($subQueries as $key => $value) {
            $query = $value['query'];
            $columns[] = $key;
            $columns[] = $query;
            if ($value['addToWhere']) {
                $where = new GZWhere($query, 'IS NOT', 'NULL');
                $orm->addWhere($where->enableFullCondition());
            }
        }

        foreach ($parentcolumnMapping['columns'] as $column) {
            if (!empty($column['reference_to']) && $column['reference_to']['table'] === $currentTableName) {
                $parentReference = $column;
                break;
            }
        }

        $where = new GZWhere("$currentTableName.{$primaryKey['name']}", '=', "{$parentcolumnMapping['name']}.{$parentReference['name']}");
        $orm->addFields(...$columns)
            ->addWhere($where->enableFullCondition());

        foreach ($params as $key => $value) {
            if (!is_array($value)) {
                $orm->addWhere(new GZWhere("$currentTableName.$key", '=', "$value", "$paramSubnivel$key"));
            }
        }
        return $orm;
    }

    public function initTransaction() {
        /*$this->transactionWasInited = true;
        $this->openConnection();
        $this->connection->query('SET autocommit = 0');
        if (!$this->connection->begin_transaction()) {
            throw new Exception('No se puede iniciar la transacción', 1000);
        }*/
    }

    public function endTransaction() {
        /*if (!$this->connection->commit()) {
            throw new Exception('No se puede finalizar la transacción', 1000);
        }
        $this->connection->query('SET autocommit = 1');
        $this->transactionWasInited = false;
        $this->closeConnection();*/
    }

    public function callRollback() {
        /*if (!$this->connection->rollback()) {
            throw new Exception('No se puede realizar la reversa de cambios', 1000);
        }
        $this->connection->query('SET autocommit = 1');
        $this->transactionWasInited = false;
        $this->closeConnection();*/
        $this->endTransaction();
    }

    private function getReferencedColumns(array $columns): array
    {
        $references = [];
        foreach ($columns as $ref) {
            if (!empty($ref['reference_to'])) {
                $references[] = $ref['reference_to'];
            }
        }
        return $references;
    }

    private function getWheresFromPayload(array $payload, string $tableName): array
    {
        $tableMap = GZUtils::getTableMap($tableName);
        $primaryKey = $tableMap['primaryKey'];
        $wheres = [];
        
        if (!empty($payload[$primaryKey])) {
            $wheres[] = new GZWhere("$tableName.$primaryKey", '=', $payload[$primaryKey]);
        } else {
            if (!empty($payload['__where'])) {
                $where = $payload['__where'];
                $wheres[] = new GZWhere("$tableName.$where", '=', $payload[$where]);
                if (!empty($payload['__whereAnd'])) {
                    $where = $payload['__whereAnd'];
                    $wheres[] = new GZWhere("$tableName.$where", '=', $payload[$where]);
                }
            }
        }
        if (!empty($payload[GUARD_FILTER])) {
            $where = $payload[GUARD_FILTER];
            $wheres[] = new GZWhere($where, '=', $payload[$where]);
        }
        if (!empty($payload['__joins'])) {
            $relations = $payload['__joins']['relations'];
            $firstRelation = $relations[0];
            $subQueryForeignKeys = GZORM::select($firstRelation['foreignTable'])->addFields("{$firstRelation['foreignColumn']} as foreign_keys");
            foreach ($wheres as $where) {
                $subQueryForeignKeys->addWhere($where);
            }
            $foreignKeys = [];
            foreach ($subQueryForeignKeys->setGroupBy($firstRelation['foreignColumn'])->doQuery() as $foreignKey) {
                $foreignKeys[] = $foreignKey['foreign_keys'];
            }
            $subQuery = GZORM::select($firstRelation['tableName'])->addFields("{$firstRelation['tableName']}.{$firstRelation['ownerColumn']}");
            $relationsLenght = count($relations);
            for ($i = 1; $i < $relationsLenght; $i++) { //Se inicializa en 1 porque el elemento 0 ya fue utilizado
                $relation = $relations[$i];
                $subQuery->addJoin(GZJoin::inner($relation['tableName'], "{$relation['foreignTable']}.{$relation['foreignColumn']}", "{$relation['tableName']}.{$relation['ownerColumn']}"));
            }
            $condition = $payload['__joins']['condition'];
            $condition2 = new GZWhere("{$firstRelation['tableName']}.{$firstRelation['ownerColumn']}", 'in', '(' . implode(',', $foreignKeys) . ')');
            $subQuery
                ->addWhere($condition->enableFullCondition())
                ->addWhere($condition2->enableFullCondition())
                ->setSize(0);
            $where = new GZWhere($firstRelation['foreignColumn'], 'in', $subQuery);
            $wheres[] = $where->enableFullCondition();
        }

        return $wheres;
    }
    
    private function handleMySQLErrors(PDOStatement $stmt = null): void {
        if ($stmt === null) {
            $erro = $this->connection->error;
            $erno = $this->connection->errno;
        } else {
            $errorInfo = $stmt->errorInfo();
            $erro = $errorInfo[2];
            $erno = $errorInfo[1];
            $stmt->closeCursor();
        }
        error_log(print_r(debug_backtrace(), true));
        throw new Exception($this->translateError($erno, $erro), $erno);
    }

    /*private function translateError($errno, $error) {
        $variablePattern = "/'[\w\.,@\s\-áéíóúÑñ]+'/";
        $variables = [];
        preg_match_all($variablePattern, $error, $variables);
        switch ($errno) {
            case 1052:
                return "La columna {$variables[0][0]} es ambigua";
            case 1054:
                return "El campo {$variables[0][0]} no existe";
            case 1062:
                return "Ya existe el valor {$variables[0][0]} para {$variables[0][1]}";
            case 1064:
                return "Existe un error en la sintaxis de la consulta";
            case 1146:
                return "La tabla {$variables[0][0]} no existe";
            case 1264:
                return "Valor fuera de rango para {$variables[0][0]}";
            case 1292:
                return "El formato de fecha es incorrecto para {$variables[0][1]}";
            case 1364:
                return "El campo {$variables[0][0]} es requerido";
            case 1366:
                return "El valor del campo {$variables[0][1]} debe ser numerico";
            case 1406:
                return "El valor para {$variables[0][0]} es demasiado grande";
            default:
                return $error;
        }
    }*/
}

?>
