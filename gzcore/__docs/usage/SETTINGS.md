# Configuración

Al momento de obtener el proyecto del repositorio, el archivo `config.ini` no
existe, más sin embargo existe un archivo llamado `config.example.ini`, el cual
funge como plantilla para el primer archivo.

Este archivo cuenta con 2 secciones: [`database`](#database) y [`jwt`](#jwt). La
primer sección funciona para establecer la configuración de la base de datos,
mientras que la segunda es para establecer las configuraciones para las
sesionces

## `database`

| Configuración | Descripción                                                                                                                           |
|---------------|---------------------------------------------------------------------------------------------------------------------------------------|
| `engine`      | Motor de base de datos a utilizar (actualmente solo es soportado **MySQL**)                                                           |
| `host`        | Dirección del servidor donde se encuentra alojada la base de datos                                                                    |
| `user`        | Nombre del usuario para iniciar sesión a la base de datos                                                                             |
| `pass`        | Contraseña para iniciar sesión a la base de datos                                                                                     |
| `name`        | Nombre de la base de datos a usar                                                                                                     |
| `port`        | Parametro opcional para especificar que debe ser usado para la conexión, cuando este no es especificado se utiliza el puerto default  |

## `jwt`

| Configuración | Descripción                                                                                                                                   |
|---------------|-----------------------------------------------------------------------------------------------------------------------------------------------|
| `key`         | Clave utilizada para realizar encriptado de la sesión. Se puede utilizar cualquier frase, aunque se recomienda no utilizar palabras conocidas |
| `timeOut`     | Tiempo de vida establecido por segundos para la sesión                                                                                        |

[Regresar](INDEX.md)