# Retiro de solicitudes de clientes

Se retiraron la pantalla `/admin/clientes/solicitudes`, sus rutas de aprobación y rechazo, su controlador, la notificación de aprobación y el portal de cliente provisional `/portal`.

El login `/login` permanece y se limita a cuentas internas `staff`, manteniendo Super Root, usuarios, roles, permisos, perfil, recuperación de contraseña y verificación de correo.

No se eliminaron tablas ni migraciones ejecutadas previamente; permanecen algunas columnas históricas (`account_type`, `approval_status`, etc.) porque siguen vinculadas a la autenticación y a registros preexistentes. Ningún usuario existente se borra de MySQL.

Para aplicar los cambios, conserva tu `.env` y `vendor/`, reemplaza el código y ejecuta `php artisan optimize:clear` y `php artisan route:list`. No se requiere migración nueva.
