# Autenticación del personal interno

Este módulo usa Laravel 11, Blade y los recursos originales de **Adminox (Bootstrap 4, jQuery y SweetAlert2)**. No se incluye Tailwind.

## Registro del personal interno

El registro público en `/register` fue retirado. Solamente el Super Root y las cuentas internas con `users.manage` o `roles.manage` pueden crear, editar, habilitar, deshabilitar o eliminar lógicamente usuarios internos desde `/admin/usuarios`.

Las contraseñas temporales de usuarios nuevos se envían por correo. Su primer acceso exige confirmar correo y actualizar contraseña desde Mi perfil. Consulta `docs/USUARIOS.md`.

El módulo de solicitudes manuales de clientes y su portal provisional fueron retirados. Esta versión autentica exclusivamente al personal interno. Se conservan los registros antiguos en la base de datos, pero las cuentas de tipo cliente no pueden iniciar sesión en el panel. El portal requerido por el examen se implementará posteriormente para los clientes de FakeAPI.

## Super Root

Se crea mediante el seeder si configuras estas variables **solamente en tu `.env` privado**:

```dotenv
SUPER_ROOT_EMAIL=tu_correo_administrador@example.com
SUPER_ROOT_PASSWORD="TU_CONTRASENA_PRIVADA"
```

No incluyas la contraseña en PHP, `.env.example`, GitHub ni en el README. En producción cámbiala por una contraseña fuerte que no se haya compartido por mensajes.

- Es una cuenta interna `staff`, con rol Administrador y bandera protegida `is_super_root`.
- Se crea activa y con el correo previamente verificado por tratarse de una cuenta provisionada por el responsable del servidor.
- El seeder no la duplica ni le cambia la contraseña cuando lo ejecutas de nuevo.
- Si ya existe una cuenta con el correo indicado, no le asigna privilegios automáticamente.
- No se puede crear un Super Root desde el registro público.

## Configuración de MySQL con XAMPP

Crea una base de datos MySQL antes de ejecutar las migraciones:

```sql
CREATE DATABASE prueba_tecnica CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

En tu archivo `.env` (que no se debe subir al repositorio):

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=prueba_tecnica
DB_USERNAME=root
DB_PASSWORD=
SESSION_DRIVER=database
CACHE_STORE=database
DB_COLLATION=utf8mb4_general_ci
MAIL_MAILER=log
```

Después configura `SUPER_ROOT_EMAIL` y `SUPER_ROOT_PASSWORD`, y ejecuta:

```bash
php artisan optimize:clear
php artisan migrate --seed
php artisan serve
```

Si ya habías ejecutado migraciones de versiones anteriores del login, usa `php artisan migrate --seed`; **no necesitas** `migrate:fresh`, que eliminaría todas las tablas y datos.

El archivo `database/database.sqlite` ya no se incluye. No es necesario crear ninguno.

## Correo electrónico

- El perfil se abre desde el menú de usuario del navbar (`/mi-perfil`). Puedes modificar nombre y teléfono.
- El cambio de correo se confirma mediante un enlace firmado de un solo uso enviado a la **nueva** dirección (vence en 60 minutos). Hasta confirmar se mantiene el correo anterior.
- Para cambiar contraseña se solicita la contraseña actual, nueva y confirmación; después se envía un aviso al correo registrado.
- La recuperación por correo sigue funcionando con Laravel Auth.
- Todos los mensajes de validación, éxito y advertencia se muestran mediante SweetAlert2.
- Con `MAIL_MAILER=log`, los mensajes se registran localmente en `storage/logs/laravel.log` pero **no salen al correo del destinatario**.
- Para envío real configura SMTP en el `.env` local y las variables privadas de Vercel:

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=HOST_DEL_SERVICIO
MAIL_PORT=587
MAIL_USERNAME=USUARIO_DEL_SERVICIO
MAIL_PASSWORD="CONTRASENA_PRIVADA"
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=CORREO_VERIFICADO_DEL_SERVICIO
MAIL_FROM_NAME="Programa de Lealtad"
```

Usa el puerto y método de cifrado especificados por tu proveedor. No publiques esas credenciales.

## Rutas

| URL | Propósito |
|---|---|
| `/admin/usuarios` | Gestión de cuentas internas, solo con permisos |
| `/login` | Inicio de sesión |
| `/forgot-password` | Solicitar correo de recuperación |
| `/email/verify` | Verificación de correo tras iniciar sesión |
| `/dashboard` | Panel interno, solo personal autorizado |
| `/mi-perfil` | Editar nombre, teléfono, correo y contraseña (sesión interna activa)
| `/account/password` | Redirige a Mi perfil |

## Vercel

Para producción necesitas **MySQL remoto**, no `DB_HOST=127.0.0.1`. Configura los valores de conexión en Environment Variables de Vercel y ejecuta las migraciones contra esa base de datos. Docker ya instala `pdo_mysql` para conectarse a MySQL.

El archivo `.env` no debe incluirse en Git y el ZIP no contiene ninguna contraseña real.

## Verificación

Prueba manualmente: crear un usuario interno, verificar el correo, entrar al panel, deshabilitar su cuenta y comprobar que no pueda iniciar sesión; comprueba también que las rutas de solicitudes respondan `404` y que los permisos internos respondan `403` cuando corresponda.

Para las pruebas automatizadas, habilita la extensión `pdo_sqlite` en PHP, ya que PHPUnit utiliza SQLite **solo en memoria para las pruebas**, aunque la aplicación real trabaja con MySQL.

```bash
php artisan test --filter=AuthenticationTest
```

## Actualización del perfil

Después de incorporar este cambio ejecuta `php artisan migrate` para crear la tabla `email_change_requests`. No uses `migrate:fresh` porque borra datos. El enlace de confirmación se envía a la dirección nueva y se guarda solo su **hash** en la tabla.

Con `MAIL_MAILER=log` el enlace se registra en `storage/logs/laravel.log` y NO se entrega realmente. Para probar cambios de correo en producción configura un servicio SMTP real y `APP_URL` con el dominio de Vercel.
