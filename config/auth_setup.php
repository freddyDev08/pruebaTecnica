<?php

return [
    // Solo se usan desde las variables privadas del servidor, jamás en el código fuente.
    'super_root_email' => env('SUPER_ROOT_EMAIL', ''),
    'super_root_password' => env('SUPER_ROOT_PASSWORD', ''),
];
