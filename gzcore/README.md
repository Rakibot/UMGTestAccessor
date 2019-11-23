# GZCore

GZCore es un framework escrito el PHP, el cual está diseñado para generar
servicios **RESTful** de manera rápida y sencilla. Ya que GZCore se encarga de
realizar las operaciones **CRUD** de base de datos, ademas de realizar manejos
de sesión utilizando la tecnología **JWT**.

Para comenzar, tan solo es necesario agregar las conexiones a la base de datos
en el archivo de configuración para comenzar a utilizar el framework.

Por el momento el framework solo funciona correctamente con **MySQL**, pero en
el futuro esta pensado para trabajar con **SQL Server**, **PostgreSQL** y
**Oracle**.

## Instalación

Antes de comenzar es necesario contar con **PHP 7** o una versión posterior.
Ademas de esto, es necesario contar las extensiones **APCu** y  **PDO** para
**MySQL**.

Una vez hecho esto, es necesario realizar una copia o renombrar el archivo 
`config.example.ini` y manejarlo como `config.ini`; ya que en este archivo se
deben agregar las conexiones a base de datos, la llave para el **JWT** y el
tiempo limite para la sesión.

Ademas de esto, tambien puede usted modificar el archivo `__helper/Auth.php`,
ya que este contiene el codigo minimo necesario para generar un token de inicio
de sesión.

## Uso básico del framework

### Inicio de sesión

Antes de realziar cualquier operación en base de datos, es necesario iniciar
sesión; esto se logra realizando una petición `POST` a la siguiente dirección:
`http://<YourHostName>/index.php/_Auth/login`. Esta petición, devolverá una
respuesta similar a la siguiente:

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

El valor de `response` dentro da la respuesta del servidor, contiene el token de
la sesión para las posteriores solicitudes al servidor.

### Operaciones CRUD

Para el manejo de cualquiera de las operaciones **CRUD** con el framework, tan
solo es necesario agregar a la URL el valor `index.php` seguido una diagonal y
despues del nombre de la tabla a realizar la operación. Ademas de esto, es
necesario agregar la cabecera Authorization a la petición, la cual tiene de
valor el token obtenido al iniciar sesión.

## Documentación avanzada

 * [Guia de usuario](__docs/usage/INDEX.md)
 * [Manual de referencia](__docs/development/INDEX.md)

## Referencias

 * [Servicios Rest](https://en.wikipedia.org/wiki/Representational_state_transfer)
 * [CRUD](https://en.wikipedia.org/wiki/Create,_read,_update_and_delete)
 * [JWT](https://en.wikipedia.org/wiki/JSON_Web_Token)
