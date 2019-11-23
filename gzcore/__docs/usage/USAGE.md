# Uso del framework

Una vez configurada correctamente la base de datos, el framework permite
realizar las 4 operaciones **CRUD** sobre esta misma a travez de peticiones
**RESTful**. Tan solo es necesario agregar a la URL el valor `index.php` seguido
una diagonal y despues del nombre de la tabla a realizar la operación.

Para un correcto funcionamiento del framework, es altamente recomendado el uso
de llaves primarias y foraneas en la base de datos, ya que de esta manera el
framework construirá los arboles de consultas necesarias antes de cada obtención
de datos.

## Respuestas

Toda respuesta del servidor es un documento `JSON`, formado por 2 atributos
padres los cuales son: `status` y `response`. `status` contiene el mensaje (`info_msg`) y el codigo (`code`) de la respuesta. Mientras que `response`
contiene la información solicitada por el cliente.

### Codigos de respuestas

| Codigo numérico   | Estado de la respuesta                                |
|-------------------|-------------------------------------------------------|
| 1                 | La petición fue ejecutada correctamente               |
| 403               | Se intentó realizar una petición sin iniciar sesión   |
| 404               | La tabla especificada en la URL no existe             |
| >1000             | Diversos codigos de error del framework               |

## Token de sesión

Antes de realizar cada consulta al servidor es necesario contar con un token de
autenticación, este token se optiene realizando una petición `POST` al servidor
a travez de la siguiente dirección:
`http://<YourHostName>/index.php/_Auth/login`. Donde `<YourHostName>` hace
referencia la dirección donde se encuentra alojado el servidor. Esta consulta devolverá una respuesta similar a la siguiente.

```JSON
{
    "status": {
        "info_msg": "ok",
        "code": "1"
    },
    "response": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOlwvXC9nem
    NvcmUuZGV2IiwiYXVkIjoiaHR0cDpcL1wvZ3pjb3JlLmRldiIsImlhdCI6MTUwNzY1MjIyMywiZX
    hwIjoxNTA3NzM4NjIzLCJ1c3VhcmlvIjp7InVzZXIiOiJzb21ldGhpbmciLCJwYXNzIjoiMTIzNC
    J9fQ.sRhRVuhZMcYEFCIYzXD1pFTK2OwZS74QxZpu12q1Ze0"
}
```

La respuesta dentro del atributo `response` es la token de sesión necesario para
las futuras peticiones al servidor.

> Cabe mencionar que el `login` encontrado en el framework contiene unicamente
el codigo escencial para generar un token de autenticación, por lo que queda en
manos del programador el agregar el codigo restante para validar al usuario,
bloquear a este por intentos fallidos, etc.

>> Por ultimo, cabe recordar que cada petición enviada al servidor requiere que
se se agregue la cabecera `Authorization`, a excepción de aquellos helpers que
no requieran de un token de autenticación como por ejemplo login; esta cabecera
debe contener como valor el token de inicio de sesión, ejemplo.

```http
Authorization: eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOl...
```

Ademas de que cada petición que solicite el token de autenticación, devolverá en
la respuesta la cabecera `Authorization`, la cual contendrá un nuevo token para
una futura petición al servidor.

## Obtención de datos

Para la obtención de datos, es necesario realizar una petición con el verbo http
`GET`. Esta petición obtendrá los primeros 100 registros de la tabla
seleccionada junto todos los elementos relacionados a las filas obtenidas,
generando así un objeto `JSON` que contenga los datos de todas las tablas
relacionadas a la tabla que solicitamos la obtención de datos. La manera para
realizar la petición de datos es similar a la siguiente.

```http
GET /index.php/<TableName> HTTP/1.1
Host: localhost
Accept: application/json
Authorization: eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOl...
```

Mientras que la respuesta puede ser similar a la siguiente.

```http
HTTP/1.1 200 OK
Date: Wed, 11 Oct 2017 17:08:26 GMT
Content-Type: application/json
Content-Length: 218

{
    "status": {
        "info_msg": "ok",
        "code": "1"
    },
    "response": [
        {
            "id": 1,
            "name": "test",
            "example": [
                {
                    "id": 1,
                    "foo_id": 1,
                    "anyData": "Example",
                    "deepExample": [
                        {
                            "id": 1,
                            "name": "FOO",
                            "example_id": 1
                        },
                        {
                            "id": 3,
                            "name": "Test",
                            "example_id": 1
                        }
                    ]
                }
            ]
        }
    ]
}
```

En caso de que se requiera aplicar algún tipo de filtro en la obtención de
datos, es necesario agregar estos como parametros del `URL`. Cabe mensionar que
estos parametros no están limitados solo a la tabla a consultar datos, sino que
pueden ser aplicados a cualquier elemento dentro del documento `JSON` que
devuelve como respuesta. Para acceder a cualquier sub-elemento se debe de
utilizar la nomenclatura ! (en lugar de utilizar punto, se debe utilizar ! para
acceder a los sub-elementos). La manera de aplicar los filtros es la siguiente.

```http
GET /index.php/<TableName>?id=1&example!deepExample!id=1 HTTP/1.1
Host: localhost
Accept: application/json
Authorization: eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOl...
```

Por lo que la respuesta puede ser similar a la siguiente.

```http
HTTP/1.1 200 OK
Date: Wed, 11 Oct 2017 17:12:30 GMT
Content-Type: application/json
Content-Length: 180

{
    "status": {
        "info_msg": "ok",
        "code": "1"
    },
    "response": [
        {
            "id": 1,
            "name": "test",
            "example": [
                {
                    "id": 1,
                    "foo_id": 1,
                    "anyData": "Example",
                    "deepExample": [
                        {
                            "id": 1,
                            "name": "FOO",
                            "example_id": 1
                        }
                    ]
                }
            ]
        }
    ]
}
```

Como se puede observar la respuesta es más pequeña ya que se agregaron las
condiciones `id=1` y `example!deepExample!id=1`; la primer condición hace
referencia a que solo buscará las filas donde `id` sean igual a uno en la tabla
seleccionada, mientras que la segunda condición mensiona que existe una tabla
llamada `example` relacionada a la tabla seleccionada, la cual contiene una
relación con la tabla `deepExample` y esta ultima posee un campo llamado `id` y
a dicho campo se le agrega la condición que sea iguan a 1.

> Solamente los argumentos que hagan referencia a una tabla y columna existente
en la base de datos seran tomados en cuenta, todos los demas seran ignorados en
tiempo de ejecución.

En caso de que sea necesario paginar los resultados obtenidos por el servidor,
pueden utilizarse los argumentos `__page` y `__page_size`. `__page_size` permite
cambiar el numero de resultados que obtendrá por pagina, mientras que `__page`
permite obtener resultados de una pagina siguiente. El comportamiento de ambos
parametros es igual al comportamiento de los 
[`limit`](https://www.w3schools.com/php/php_mysql_select_limit.asp) de **MySQL**

## Creación de datos

Para la creación de datos es necesario enviar una petición al servidor con el
verbo http `POST`, el cual debe contener en el cuerpo del mensaje los datos a
insertar en la tabla en formato `JSON`. En caso de que sea satisfactoria la
creación de datos, se  devolverá una respuesta sencilla (sin las tablas que
estén relacionadas a la tabla seleccionada) con los datos insertados en la base
de datos. La manera para realizar la inserción de datos es similar a la
siguiente.

```http
POST /index.php/<TableName> HTTP/1.1
Host: localhost
Accept: application/json
Authorization: eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOl...

{
    "name": "Juan"
}
```

Por lo que la respuesta puede ser similar a la siguiente.

```http
HTTP/1.1 200 OK
Date: Wed, 11 Oct 2017 17:15:42 GMT
Content-Type: application/json
Content-Length: 76

{
    "status": {
        "info_msg": "ok",
        "code": "1"
    },
    "response": {
        "id": "15",
        "name": "Juan"
    }
}
```

## Actualización de datos

Para actualizar datos, tan solo es necesario enviar una petición al servidor con
el verbo http `PUT`; de manera similar a la creación de datos, se deben mandar
los datos a modificar en el cuerpo del mensaje, ademas de que se debe incluir el
campo de la llave primaria junto con su valor. Si la tabla no posee una llave
primaria se puede hacer uso de los atributos especiales `__where` y
`__whereAnd`, los cuales deben contener como valor el nombre de un campo en la
base de datos, ademas de que el valor de dichos campos debe ser agregado como atributo en el cuerpo del mensaje.

> La llave primaria se tomará solo como condicional y no se actualizará este
valor valor.

> Los atributos especiales `__where` y `__whereAnd` al igual que las llaves
primarias, solo son usados como condicionales.

>> El atributo especial `__where` puede ser utilizado sin necesidad de agregar
al atributo `__whereAnd`; mientras que el atributo `__whereAnd` solo puede ser
usado en compañía del atributo `__where`.

La manera para actualizar datos es similar a la siguiente.

```http
PUT /index.php/<TableName> HTTP/1.1
Host: localhost
Accept: application/json
Authorization: eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOl...

{
	"id": 15,
	"name": "Juan Solo"
}
```

De manera similar a la creación de datos, la respuesta es sencilla (sin las
tablas que estén relacionadas a la tabla seleccionada) con los datos
actualizados, por lo que una respuesta es similar a la siguiente.

```http
HTTP/1.1 200 OK
Date: Wed, 11 Oct 2017 17:20:18 GMT
Content-Type: application/json
Content-Length: 81

{
    "status": {
        "info_msg": "ok",
        "code": "1"
    },
    "response": [
        {
            "id": 15,
            "name": "Juan Solo"
        }
    ]
}
```

## Eliminación de datos

Para eliminar los datos tan solo es necesario enviar una petición al servidor
con el verbo http `DELETE`. Para establecer las condiciones para eliminar datos
es necesario agregar estos como argumentos en la `URL`, con la limitación de que
solo se pueden agregar condiciones como cuando se actualizan datos (mediante la
llave primaria o mediante el uso de los argumentos especiales `__where` y 
`__whereAnd`)

> ¡Advertencia! La eliminación de datos se realiza de manera física, por lo que
si requieres una eliminación logica, es recomendable utilizar una actualización
de datos estableciendo el bit de eliminado como verdadero.

A continuación se muestra un ejemplo para eliminar datos.

```http
DELETE /index.php/<TableName> HTTP/1.1
Host: localhost
Accept: application/json
Authorization: eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOl...
```

A difefrencia de las peticiones anteriores, al eliminar datos solo hay 2
posibles respuestas, se devolverá verdadero en caso de que se haya eliminado al
menos un registro en base de datos y falso en caso contrario. Un ejemplo de una
respuesta sería lo siguiente.

```http
HTTP/1.1 200 OK
Date: Wed, 11 Oct 2017 17:23:02 GMT
Content-Type: application/json
Content-Length: 57

{
    "status": {
        "info_msg": "ok",
        "code": "1"
    },
    "response": [
        true
    ]
}
```
[Regresar](INDEX.md)
