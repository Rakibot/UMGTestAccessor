# Helpers

Los helpers son complementos para el framework, estan pensados para realizar
alguna acción especifica o realizar una serie de consultas e inserciones que
sería muy dificil ejecutarlo mediante el uso normal del framework.

## Creación de un helper

Antes de todo, cabe mensionar que un **helper** es solo un script de php, el
cual debe contener ciertas caracteristicas para considerase valido y para
poderse ejecutar, las reglas a tomar en cuenta son las siguientes:

 1. Todo **helper** debe ser creado dentro de la carpeta `__helpers`.
 2. Todo **helper** debe constar de una clase, cuyo nombre debe ser identico al
 del archivo donde reside.
 3. Toda clase **helper** debe residir dentro del namespace `GZCore\__helper`.
 4. Todo **helper** debe heredar de la clase `\GZCore\__helper\GZHelper`.
 5. Ningún **helper**, bajo ninguna circunstancia, deberá imprimir datos durante
 su ejecución.
 6. La unica forma de mostrar información al usuario es devolviendo algún tipo
 de valor en el **helper**.
 7. Todo método publico debe de recibir solo un argumento, el cual debe ser de
 tipo `array`.
 8. En caso de que una clase **helper** necesite usar de otra clase **helper**,
 esta no deberá ser incluida, ya que por esa razón se utilizan los namespaces.

A continuación se muestra un ejemplo practico para generar un helper, en este
caso el helper esta almacenado en el archivo `Foo.php`

```php
<?php

namespace GZCore\__helper;

class Foo extends GZHelper {

    public function example(array $payload) {
        return "Los datos enviados fueron:\n" . print_r($payload, true);
    }
}
```

## Ejecución de un helper

Para ejecutar un **helper** tan solo es necesario enviar una petición al
servidor utilizando cualquier tipo de verbo http (a menos de que se indique lo
contrario en el **helper**) a una URL formada de la siguiente manera. La
dirección sel dervidor seguido por `index.php`, diagonal, guion bajo, nombre de
la clase del helper, diagonal, nombre del método a ejecutar.

> Para el nombre de la clase como para el nombre del método, deben ser
respetadas las mayúsculas y las minúsculas.

En caso de que sea necesario mandar algún tipo de parametro al método, deben
respetarse los estandares del http (se mandan los parametros en el `URL` cuando
el verbo es `GET` o `DELETE` y en el cuerpo del mensaje cuando es `POS` o
`PUT`).

A continuación se muestra el ejemplo de ejecución del helper generado
anteriormente.

```http
POST /index.php/_Foo/example HTTP/1.1
Host: localhost
Accept: application/json
Authorization: eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOl...

{
    "saludo": "Hola Mundo",
    "mensaje": "Mensaje"
}
```

Por lo que el servidor nos regresa esta respuesta.

```http
HTTP/1.1 200 OK
Date: Fri, 13 Oct 2017 20:25:30 GMT
Content-Type: application/json
Content-Length: 148

{
    "status": {
        "info_msg": "ok",
        "code": "1"
    },
    "response": "Los datos enviados fueron:\nArray\n(\n    [saludo] => Hola Mundo\n    [mensaje] => Mensaje\n)\n"
}
```

## Modificadores de ejecución

En algunos casos necesitaremos que el helper solo funcione con un verbo
especifico del http o que el **helper** funcione aunque el usuario no haya
iniciado sesión. Aquí encontramos a los métodos modificadores
`addMethodWithoutAuth` y `onlyFor`. `addMethodWithoutAuth` funciona para
ejecutar un **helper** sin necesidad de haber iniciado sesión previamente, este
método debe ser ejecutado desde el contructor y debe recibir como argumento el
nombre del método que no necesitará la validación. Mientras que `onlyFor`
funciona para solo ejecutar peticiones que hayan sido solicitadas con un verbo
especifico, este método debe ser ejecutado al inicio del método que se le
agregará la reestricción.

Para conocer más sobre los modificadores de ejecución o sobre la clase 
`\GZCore\__helper\GZHelper`, puede consultar su manual de referencía
[aquí](../development/helper/HELPER.md).

## ORMs

El framework esta diseñado para ejecutar la mayoría de las ejecuciones de base
de datos (incluyendo la ejecución de vistas); pero pueden existir casos muy
especificos en los que se requiera escribir un **helper** que ejecute una o
varias operaciones en base de datos, si este fuera el caso usted puede hacer uso
de la clase `\GZCore\__framework\GZORM`, puede consultar su manual de referencía
[aquí](../development/framework/ORM.md)

[Regresar](INDEX.md)
