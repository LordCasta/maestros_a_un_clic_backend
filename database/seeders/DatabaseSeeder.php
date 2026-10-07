<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Catálogos siempre; datos demo solo fuera de producción.
     */
    public function run(): void
    {
        $this->call([
            CommuneSeeder::class,
            CategorySeeder::class,
        ]);

        if (! app()->isProduction()) {
            $this->call(DemoSeeder::class);
        }
    }
}
