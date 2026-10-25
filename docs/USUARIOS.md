# Gestión de usuarios internos

La inscripción pública quedó deshabilitada: `GET /register` y `POST /register` responden `404`.

En `/admin/usuarios` se pueden ver y administrar las cuentas de personal interno mediante DataTables. El acceso requiere una cuenta interna con el permiso `users.manage` **o** `roles.manage`, o la cuenta Super Root.

- `Crear usuario`: abre el formulario para nombre, teléfono, correo y rol.
- `Editar`: reutiliza el mismo formulario. Si se cambia el correo, se envía una confirmación a la nueva dirección; el correo anterior permanece vigente hasta aceptar el enlace.
- `Habilitar / Deshabilitar`: cambia el estado desde SweetAlert2 y AJAX. Una cuenta deshabilitada no puede iniciar sesión.
- `Eliminar`: usa `deleted_at`, no borra el registro físicamente. La cuenta eliminada tampoco puede iniciar sesión.

El sistema no permite modificar ni eliminar al Super Root desde esta sección. Tampoco permite deshabilitar, eliminar o cambiar de rol al último administrador activo. La validación se ejecuta en el servidor.

## Contraseña inicial

Al crear un usuario se genera una contraseña temporal aleatoria, se almacena únicamente su hash y se envía por correo junto con un enlace para verificar el email. Tras iniciar sesión, el usuario deberá cambiar la contraseña temporal desde Mi perfil antes de abrir el panel.

**Importante:** `MAIL_MAILER=log` no envía el correo al destinatario, sino que deja el contenido en `storage/logs/laravel.log` para desarrollo. Antes de probar el envío real, configura SMTP y un remitente válido en las variables privadas de entorno.

La dirección del dominio de producción debe estar correctamente configurada en `APP_URL` para generar enlaces de verificación válidos. Los correos temporales contienen información sensible; protege los logs y evita guardar esa contraseña fuera del mensaje enviado.

## Actualizar proyecto

```bash
php artisan config:clear
php artisan migrate
php artisan serve
```

La migración `2026_10_24_000006_add_staff_user_management_to_users.php` agrega `deleted_at` y `must_change_password`. No se necesita `migrate:fresh`.

Los usuarios antiguos no se eliminan y quedan con `must_change_password = false`. No se usa registro público para los clientes importados de FakeAPI; ese acceso será un proceso independiente.
