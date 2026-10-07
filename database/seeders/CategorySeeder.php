<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Catálogo de categorías y subcategorías (HU010, HU033).
 * `icon` es el nombre de un ícono de lucide (https://lucide.dev/icons).
 */
class CategorySeeder extends Seeder
{
    private const CATALOG = [
        ['Plomería', 'droplets', ['Reparación de fugas', 'Instalación de grifería', 'Destape de tuberías']],
        ['Electricidad', 'zap', ['Instalaciones eléctricas', 'Tomas e interruptores', 'Iluminación']],
        ['Carpintería', 'hammer', ['Muebles a medida', 'Cocinas y cajones', 'Reparación de puertas']],
        ['Pintura', 'paint-roller', ['Pintura de interiores', 'Pintura de fachadas', 'Estuco y resanes']],
        ['Cerrajería', 'key-round', ['Apertura de puertas', 'Cambio de guardas']],
        ['Diseño de interiores', 'sofa', ['Asesoría de diseño', 'Remodelación de espacios']],
        ['Mantenimiento general', 'wrench', ['Reparaciones locativas', 'Instalación de electrodomésticos']],
        ['Jardinería', 'flower-2', ['Mantenimiento de jardines', 'Poda']],
        ['Limpieza', 'sparkles', ['Limpieza de hogar', 'Muebles y tapetes']],
    ];

    public function run(): void
    {
        foreach (self::CATALOG as [$name, $icon, $children]) {
            $parent = Category::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'icon' => $icon, 'parent_id' => null],
            );

            foreach ($children as $childName) {
                Category::updateOrCreate(
                    ['slug' => Str::slug($childName)],
                    ['name' => $childName, 'icon' => $icon, 'parent_id' => $parent->id],
                );
            }
        }
    }
}
