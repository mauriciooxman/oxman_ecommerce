<?php

return [
    // Configuración por identificador de comercio; nunca procede del checkout.
    'recipients' => [
        'tecnologia-demo' => env('OWNER_ORDER_DEMO_EMAIL', 'dueno-demo@example.test'),
    ],
];
