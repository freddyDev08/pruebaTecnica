# Clientes de la tienda

Los clientes importados de `fakeapi.dev/api/v1/users` se guardan en `clientes`, separados de los usuarios internos que ingresan al panel. El registro original completo se conserva en `raw_data` para no perder los campos adicionales de la fuente.

## Configuración

Agregar en `.env` local y en las variables privadas de Vercel:

```dotenv
FAKEAPI_BASE_URL=https://fakeapi.dev/api/v1
FAKEAPI_API_KEY=
```

La clave de FakeAPI se utiliza solo en el backend. Nunca debe imprimirse en Blade ni exponerse a JavaScript.

Ejecutar una vez:

```bash
php artisan migrate
php artisan config:clear
```

## Rutas

- `GET /admin/clientes`: tabla DataTables (permiso `clients.view`).
- `GET /admin/clientes/datos`: listado JSON desde MySQL (permiso `clients.view`).
- `POST /admin/clientes/sincronizar`: descarga paginada de la API y actualiza MySQL (permiso `sync.run`).

La sincronización es idempotente por `external_id`. Se registra cada ejecución en `fakeapi_sync_runs`. La pantalla muestra los campos principales y el botón «Ver datos» revela la respuesta original completa de cada cliente.

Esta etapa es manual; quedan pendientes el cron diario, los pedidos, pagos, puntos y cuentas del portal.
