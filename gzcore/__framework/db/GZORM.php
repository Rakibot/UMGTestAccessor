<?php

namespace GZCore\__framework\db;

use \GZCore\__framework\db\engines\GZEngine;
use \GZCore\__framework\GZConfig;
use \GZCore\__framework\GZUtils;
use \Exception;
use \PDO;

class GZORM extends GZEngine
{
    private static $engine;
    private static $hostName;
    private static $dbUserName;
    private static $dbUserPassword;
    private static $dbName;
    private static $dbPort;

    protected function __construct(string $operation, string $tableName)
    {
        parent::__construct($operation, $tableName);
        $this->params = [];
    }

    public static function init()
    {
        GZORM::$engine = GZConfig::$config->database->engine;
        GZORM::$hostName = GZConfig::$config->database->host;
        GZORM::$dbUserName = GZConfig::$config->database->user;
        GZORM::$dbUserPassword = GZConfig::$config->database->pass;
        GZORM::$dbName = GZConfig::$config->database->name;
        GZORM::$dbPort = GZConfig::$config->database->port;
    }

    public static function select(string $tableName): GZORM
    {
        return new GZORM('SELECT', $tableName);
    }

    public static function update(string $tableName): GZORM
    {
        return new GZORM('UPDATE', $tableName);
    }

    public static function delete(string $tableName): GZORM
    {
        return new GZORM('DELETE FROM', $tableName);
    }

    public static function insert(string $tableName): GZORM
    {
        return new GZORM('INSERT INTO', $tableName);
    }

    public static function execute(string $query, PDO $connection = null): array
    {
        $gzorm = new GZORM('', '');
        $gzorm->query = $query;
        if (empty($connection)) {
            return $gzorm->doDefault();
        } else {
            return $gzorm->doDefault($connection);
        }
    }

    public function addParam(GZParam $param)
    {
        $this->params[] = $param;
    }

    public function doQuery(PDO $connection = null): array
    {
        $conn = $connection ?? $this->createConnection();
        switch ($this->operation) {
            case 'SELECT':
                return $this->doSelect($conn);
            case 'INSERT INTO':
                return $this->doInsert($conn);
            case 'UPDATE':
                if (!$this->doUpdate($conn)) {
                    return [];
                }
                $select = GZORM::select($this->tableName);
                foreach ($this->wheres as $where) {
                    $select->addWhere($where);
                }
                return $select->doQuery();
            case 'DELETE FROM':
                return [$this->doDelete($conn)];
        }
    }

    private function doDefault(PDO $connection = null)
    {
        $conn = $connection ?? $this->createConnection();
        $stmt = $conn->prepare($this);
        $execRes = $stmt->execute();
        if(!$execRes){
            $errorInfo = $stmt->errorInfo();
            $stmt->closeCursor();
            throw new Exception($errorInfo[2], $errorInfo[1]);
        }
        $response = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        return $response;
    }

    private function doSelect(PDO $connection): array
    {
        foreach ($this->fields as $key => $field) {
            $columnSplited = $this->splitColumnName($field);
            if (!$this->verifyColumn($columnSplited)) {
                unset($this->fields[$key]);
            }
        }
        foreach ($this->wheres as $key => $where) {
            $columnSplited = $this->splitColumnName($where->field1);
            if (!$this->verifyColumn($columnSplited)) {
                unset($this->wheres[$key]);
            }
        }
        foreach ($this->params as $key => $param) {
            $columnSplited = $this->splitColumnName(str_replace('!', '.', $param->paramName));
            if (!$this->verifyColumn($columnSplited)) {
                unset($this->params[$key]);
            }                
        }
        $stmt = $connection->prepare($this);
        if ($stmt === false) {
            throw new Exception($connection->error, $connection->errno);
        }
        foreach ($this->wheres as $where) {
            if (!$where->fullCondition) {
                $stmt->bindValue(":" . GZUtils::removeSpecialChars($where->field1), $where->field2);
            }
        }
        foreach ($this->params as $param) {
            $stmt->bindValue(":" . GZUtils::removeSpecialChars($param->paramName), $param->value);
        }
        $execRes = $stmt->execute();
        if(!$execRes){
            $errorInfo = $stmt->errorInfo();
            $stmt->closeCursor();
            throw new Exception($errorInfo[2], $errorInfo[1]);
        }
        $result = [];
        while (($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
            foreach (array_keys($row) as $columnName) {
                $column = $row[$columnName];
                $column = str_replace("\t", '\t', $column);
                $column = str_replace("\r", '\r', $column);
                $column = str_replace("\n", '\n', $column);
                $column = str_replace("\0", '', $column);
                $column = json_decode($column, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $row[$columnName] = $column;
                }
            }
            $result[] = $row;
        } 
        $stmt->closeCursor();
        return $result;
    }

    private function doInsert(PDO $connection): array
    {
        foreach ($this->values as $key => $value) {
            $columnSplited = $this->splitColumnName($key);
            if (!$this->verifyColumn($columnSplited)) {
                unset($this->values[$key]);
            }
        }
        $stmt = $connection->prepare($this);
        foreach ($this->values as $key => $value) {
            $stmt->bindValue(':' . GZUtils::removeSpecialChars($key), $value);
        }
        $execRes = $stmt->execute();
        if(!$execRes){
            $errorInfo = $stmt->errorInfo();
            $stmt->closeCursor();
            throw new Exception($errorInfo[2], $errorInfo[1]);
        }
        $stmt->closeCursor();
        $result = [];
        foreach ($this->tablesJoined[$this->tableName] as $column) {
            if ($column === $this->primaryKey) {
                $result[$column] = $connection->lastInsertId();
            } else {
                $result[$column] = array_key_exists($column, $this->values) ? $this->values[$column] : null;
            }
        }
        return $result;
    }

    private function doUpdate(PDO $connection): bool
    {
        if (empty($this->wheres)) {
            throw new Exception("Doesn't exist any condition do apply the update", 1000);
        }
        foreach ($this->values as $key => $value) {
            $columnSplited = $this->splitColumnName($key);
            if (!$this->verifyColumn($columnSplited)) {
                unset($this->values[$key]);
            }
        }
        foreach ($this->wheres as $key => $where) {
            $columnSplited = $this->splitColumnName($where->field1);
            if (!$this->verifyColumn($columnSplited)) {
                unset($this->wheres[$key]);
            }
        }
        $stmt = $connection->prepare($this);
        foreach ($this->values as $key => $value) {
            $stmt->bindValue(':' . GZUtils::removeSpecialChars($key), $value);
        }
        foreach ($this->wheres as $where) {
            if (!$where->fullCondition) {
                $stmt->bindValue(":" . GZUtils::removeSpecialChars($where->field1), $where->field2);
            }
        }
        $execRes = $stmt->execute();
        if(!$execRes){
            $errorInfo = $stmt->errorInfo();
            $stmt->closeCursor();
            throw new Exception($errorInfo[2], $errorInfo[1]);
        }
        $rowsAffected = $stmt->rowCount();
        $stmt->closeCursor();
        return $rowsAffected > 0 ? true : false;
    }

    private function doDelete(PDO $connection): bool
    {
        if (empty($this->wheres)) {
            throw new Exception("Doesn't exist any condition do apply the delete", 1000);
        }
        foreach ($this->wheres as $key => $where) {
            $columnSplited = $this->splitColumnName($where->field1);
            if (!$this->verifyColumn($columnSplited)) {
                unset($this->wheres[$key]);
            }
        }
        $stmt = $connection->prepare($this);
        foreach ($this->wheres as $where) {
            if (!$where->fullCondition) {
                $stmt->bindParam(":" . GZUtils::removeSpecialChars($where->field1), $where->field2);
            }
        }
        $execRes = $stmt->execute();
        if(!$execRes){
            $errorInfo = $stmt->errorInfo();
            $stmt->closeCursor();
            throw new Exception($errorInfo[2], $errorInfo[1]);
        }
        $rowsAffected = $stmt->rowCount();
        $stmt->closeCursor();
        return $rowsAffected > 0 ? true : false;
    }

    private function createConnection(): PDO
    {
        try {
            return GZUtils::getConnection();
        } catch(PDOException $e) {
            throw new Exception("El servidor no está disponible por el momento", "1000", null);
        }
    }
}
