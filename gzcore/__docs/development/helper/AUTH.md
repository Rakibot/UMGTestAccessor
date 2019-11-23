# La clase `GZCore\__helper\Auth`

Clase con lo minimo requerido para realizar un inicio de sesión. Esta clase no
realiza la busqueda de usuario en alguna tabla especifica, para esto es
necesario agregar el codigo para consultar al usuario y validar su password en
esta clase, para esto se recomienda utilizar la clase
`\GZCore\__framework\GZORM`, puede consultar su manual de referencia
[aquí](../framework/ORM.md).

## Sinopsis de la clase

```php
<?php

namespace GZCore\__helper;

class Auth extends GZHelper {
    /* Métodos */
    public __construct();
    public function login(array $payload): string;
    public function logout(array $payload): string;
}
```

| Índice    |
|-----------|
| [__construct]()
| [login]()
| [logout]()

## `\GZCore\__helper\Auth::__construct`

Crea un token de sesión.

### Descripción

```php
public function Auth::__construct()
```

Crea una instancia para poder iniciar sesión y establece que el método `login`
no requiere de un token de autenticación para ejecutarse.

### Ejemplo

```php
<?php

namespace GZCore\__helper;

$session = new Auth();
```

## `\GZCore\__helper\Auth::login`

Genera un token de sesión según los argumentos enviados

### Descripción

```php
public function Auth::login(array $payload): string;
```

Esta funcion genera un token de inicio de sesión a partir de los parametros
recibidos.

### Parametros

 * `$payload` Parametro requerido por ser un helper, en este parametro se
 encuentran los datos enviados desde el cliente.

### Valores devueltos

Un string el cual contiene el token de inicio de sesión generado a partir de los
estandares de la **JWT**

### Ejemplo

```php
<?php

namespace GZCore\__helper;

$session = new Auth();
$payload = [
    'user' => 'user',
    'pass' => '1234'
];
$token = $session->login($payload);
echo $token;
```

El resultado de la prueba sería.

```
eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOlwvXC9nemNvcmUuZ...
```

## `\GZCore\__helper\Auth::logout`

Cierra la sesión

### Descripción

```php
public function Auth::logout(array $payload): string;
```

Codigo minimo para destruir la sesión en caso de que sea necesaria manejar esta
desde el backend.

### Parametros

 * `$payload` Parametro requerido por ser un helper, en este parametro se
 encuentran los datos enviados desde el cliente.

### Valores devueltos

Devuelve un mensaje en caso de que la sesión haya sido cerrada de forma 
satisfactoria.

### Ejemplo

```php
<?php

namespace GZCore\__helper;

$session = new Auth();
$payload = [];
$response = $session->logout($payload);
echo $response;
```
El resultado de la prueba sería.

```
Cerraste la sesión
```

[Regresar](INDEX.md)