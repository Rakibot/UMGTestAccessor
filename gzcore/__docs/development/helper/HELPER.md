# La clase `GZCore\__helper\GZHelper`

Clase base de la cual todo **helper** debe heredar.

## Sinopsis de la Clase

```php
<?php

namespace GZCore\__helper;

class GZHelper {
    /* Propiedades */
    private array $avoidAuth;

    /* Métodos */
    protected __construct();
    public function requireAuth(string $methodName): bool;
    protected function addMethodWithoutAuth(string $methodName): void;
    protected function onlyFor(string $httpVerb): void;
}
```

| Índice    |
|-----------|
| [__construct](#\GZCore\__helper\GZHelper::__construct)
| [requireAuth](#\GZCore\__helper\GZHelper::requireAuth)
| [addMethodWithoutAuth]()
| [onlyFor]()

## `\GZCore\__helper\GZHelper::__construct`

Establece una clase como helper e inicializa el arreglo `$avoidAuth`

### Descripción

```php
protected function GZHelper::__construct()
```

El constructor fue declarado como protegido para que solo se pueda utilizar esta
clase mediante herencias desde otras clases.

### Ejemplo

```php
<?php

namespace GZCore\__helper;

class Example extends GZHelper {
    
    public function __construct() {
        parent::__construct();
    }
}
```

## `\GZCore\__helper\GZHelper::requireAuth`

Verifica si un método especifico de un helper requiere de autenticación para
poder ejecutarse.

### Descripción

```php
public function GZHelper::requireAuth(string $methodName): bool;
```

Este método es llamado comunmente por el framework para validar que un metodo 
especifico de una clase que herede de `\GZCore\__helper\GZHelper` requiera o no
autenticación para poder funcionar

### Parametros

 * `$methodName` Nombre del metodo a validar si requiere autenticación.

### Valores devueltos

Devuelve falso en caso de que se haya especificado que no requiera autenticación
dicho método, en caso opuesto devuelve verdadero.

### Ejemplo

```php
<?php

namespace GZCore\__helper;

class Example extends GZHelper {

    public function test(array $payload) {

    }
}

$example = new Example();
echo $example->requireAuth('test');
```

El resultado de la prueba sería.

```
true
```

## `\GZCore\__helper\GZHelper::addMethodWithoutAuth`

Metodo protegido, el cual debe ser llamado desde la clase que herede de
`\GZCore\__helper\GZHelper`

### Descripción

```php
protected function GZHelper::addMethodWithoutAuth(string $methodName): void;
```

Este método establece cuales métodos de la clase que herede de helper pueden ser
accedidos sin token de autenticación; cabe mensionar que todos los métodos que
no hayan sido agregados a esté método requerirán de un logueo previo para poder
ejecutarse.

> Es recomendable ejecutar este método en el contructor de la clase que herede
de helper, para que así desde un inicio se indiquen cuales métodos no requieren
de un token de autenticación.

Por cada método que se desee agregar, se debe ejecutar este metodo de la misma
manera.

### Parametros

 * `$methodName` Nombre del método del cual no requerirá autenticación.

### Ejemplo

```php
<?php

namespace GZCore\__helper;

class Example extends GZHelper {
    
    public function __construct() {
        parent::__construct();
        $this->addMethodWithoutAuth('test');
        $this->addMethodWithoutAuth('test2');
    }

    public function test(array $payload) {

    }

    public function test2(array $payload) {

    }

    public function test3(array $payload) {

    }
}
```

## `\GZCore\__helper\GZHelper::onlyFor`

Método protegido, el cual debe ser llamado desde la clase que herede de
`\GZCore\__helper\GZHelper`

### Descripción

```php
protected function GZHelper::onlyFor(string $httpVerb): void;
```

Establece que el método que herede de la clase `\GZCore\__helper\GZHelper` donde
ha sido ejecutado solo pueda responder solicitudes de un unico verbo http, si
este método no es ejecutado, el método que herede de la clase
`\GZCore\__helper\GZHelper` aceptará peticiones de todos los verbos http
soportados por el servidor.

> El nombre del verbo http no es sencible a mayúsculas y minúsculas.

### Parametros

 * `$httpVerb` Nombre del verbo http por el cual será aceptada la petición.

### Ejemplo

```php
<?php

namespace GZCore\__helper;

class Example extends GZHelper {
    
    public function __construct() {
        parent::__construct();
        $this->addMethodWithoutAuth('test');
        $this->addMethodWithoutAuth('test2');
    }

    public function test(array $payload) {
        $this->onlyFor('GET');
    }

    public function test2(array $payload) {
        $this->onlyFor('put');
    }

    public function test3(array $payload) {
        $this->onlyFor('DELETE');
    }
}
```

[Regresar](INDEX.md)
