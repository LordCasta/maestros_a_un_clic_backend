<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Specialty;

class SpecialtySeeder extends Seeder
{
    public function run(): void
    {
        $specialties = ['Plomería','Electricidad','Carpintería','Pintura','Jardinería','Limpieza'];

        foreach ($specialties as $name) {
            Specialty::firstOrCreate(['slug' => str()->slug($name)], ['name' => $name]);
        }
    }
}

