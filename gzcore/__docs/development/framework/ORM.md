# La clase `\GZCore\__framework\GZORM`

Clase dedicada a construir consultas de manera segura a partir de los parametros
enviados en los diferentes metodos de esta misma clase.

## Sinopsis de la clase

```php
<?php

namespace GZCore\__framework;

class GZORM extends GZOrmMaster {
    /* Propiedades */
    private static string $engine;
    private static string $hostName;
    private static string $dbUserName;
    private static string $dbUserPassword;
    private static string $dbName;
    private static string $dbPort;
    private array $params;

    /* Métodos */
    protected function __construct(string $operation, string $tableName);
    public static function init(): void;
    private static function getConnectionString(): string;
    public static function select(string $tableName): GZORM;
    public static function update(string $tableName): GZORM;
    public static function delete(string $tableName): GZORM;
    public static function insert(string $tableName): GZORM;
    public function __toString(): string;
    public function addParam(GZParam $param): void;
    public function doQuery([PDO $connection]): array;
    private function doSelect(PDO $connection): array;
    private function doInsert(PDO $connection): array;
    private function doUpdate(PDO $connection): bool;
    private function doDelete(PDO $connection): bool;
    private function createConnection(): PDO;
}
```

| Índice    |
|-----------|
| [__construct]()
| [init]()
| [getConnectionString]()
| [select]()
| [update]()
| [delete]()
| [insert]()
| [__toString]()
| [addParam]()
| [doQuery]()
| [doSelect]()
| [doInsert]()
| [doUpdate]()
| [doDelete]()
| [createConnection]()



## `\GZCore\__framework\GZORM::__construct`

Crea una nueva instancia para una operación **SQL** dada

### Descripción

```php
protected function GZORM::__construct(string $operation, string $tableName);
```

Crea un objeto para manipular las operaciones **SLQ** de manera segura; según la
operación dada en el argumento `$operation` será la manera en la que se
comportará el objeto y como serializará la busqueda.

> El constructor es privado y la unica forma de acceder a este es mediante los
métodos estaticos  `select`, `update`, `delete` o `insert`.

### Parametros

 * `$operation` Operacion a ejecutar en la base de datos, los unicos valores
 aceptados son: **SELECT**, **UPDATE**, **DELETE FROM** e **INSERT INTO**
 * `$tableName`Nombre de la tabla donde se ejecutará la operación dada.

### Ejemplo

```php
<?php

namespace GZCore\__framework;

$select = GZORM::select('user');
```

## `\GZCore\__framework\GZORM::init`

Inicializa los valores estaticos

### Descripción

```php
public static function GZORM::init(): void;
```

Inicializa los valores estaticos al arrancar el framework para almacenar en
memoria las credenciales de base de datos.

### Ejemplo

```php
<?php

GZORM::init();
```

## `\GZCore\__framework\GZORM::getConnectionString`

Genera la cadena de conexión

### Descripción

```php
private static function getConnectionString(): string;
```

Método el cual genera la cadena de conexión a partir de las credenciales
obtenidas previamente por el metodo estatico `init`

### Ejemplo

```php
<?php

GZORM::init();
$connectionstring = GZORM::getConnectionString();
```

## `\GZCore\__framework\GZORM::select`

Obtiene una instancia de `GZORM` como obtener datos

### Descripción

```php
public static function select(string $tableName): GZORM;
```

Crea una nueva instancia de `GZORM` y establece la operación como `SELECT` para
la obtención de datos del servidor.

### Parametros

 * `$tableName` Nombre de la base de datos de donde se obtendrá la información.

### Valores devueltos

Una nueva instancia de `GZORM` con la operación `SELECT`

### Ejemplo

```php
<?php

namespace GZCore\__framework;

$select = GZORM::select('user');
```

## `\GZCore\__framework\GZORM::update`

Obtiene una instancia de `GZORM` como actualizar datos

### Descripción

```php
public static function update(string $tableName): GZORM;
```

Crea una nueva instancia de `GZORM` y establece la operación como `SELECT` para
la obtención de datos del servidor.

### Parametros

### Valores devueltos

### Ejemplo

## `\GZCore\__framework\GZORM::delete`

### Descripción

### Parametros

### Valores devueltos

### Ejemplo

## `\GZCore\__framework\GZORM::insert`

### Descripción

### Parametros

### Valores devueltos

### Ejemplo

## `\GZCore\__framework\GZORM::__toString`

### Descripción

### Parametros

### Valores devueltos

### Ejemplo

## `\GZCore\__framework\GZORM::addParam`

### Descripción

### Parametros

### Valores devueltos

### Ejemplo

## `\GZCore\__framework\GZORM::doQuery`

### Descripción

### Parametros

### Valores devueltos

### Ejemplo

## `\GZCore\__framework\GZORM::doSelect`

### Descripción

### Parametros

### Valores devueltos

### Ejemplo

## `\GZCore\__framework\GZORM::doInsert`

### Descripción

### Parametros

### Valores devueltos

### Ejemplo

## `\GZCore\__framework\GZORM::doUpdate`

### Descripción

### Parametros

### Valores devueltos

### Ejemplo

## `\GZCore\__framework\GZORM::doDelete`

### Descripción

### Parametros

### Valores devueltos

### Ejemplo

## `\GZCore\__framework\GZORM::createConnection`

### Descripción

### Parametros

### Valores devueltos

### Ejemplo



[Regresar](INDEX.md)