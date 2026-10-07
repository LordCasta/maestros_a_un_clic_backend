<?php

// Solo lo que cambia respecto a la configuración por defecto de Laravel Boost.
// El backend es solo API: la skill de Tailwind (detectada por el package.json
// del esqueleto de Laravel) confunde a los agentes con el frontend.

return [
    'skills' => [
        'exclude' => ['tailwindcss-development'],
    ],
];
