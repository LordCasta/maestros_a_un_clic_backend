<?php

namespace Database\Seeders;

use App\Enums\CommuneType;
use App\Models\Commune;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * 16 comunas y 5 corregimientos de Medellín.
 *
 * Las coordenadas son centroides aproximados y las vecindades son aproximadas:
 * sirven para ordenar por cercanía y sugerir comunas aledañas (HU002), no para
 * cartografía. Si se necesita precisión, reemplazar con datos de MEData.
 */
class CommuneSeeder extends Seeder
{
    /** @var array<string, array{0: string, 1: CommuneType, 2: float, 3: float}> */
    private const COMMUNES = [
        '1' => ['Popular', CommuneType::Comuna, 6.2980, -75.5470],
        '2' => ['Santa Cruz', CommuneType::Comuna, 6.2960, -75.5580],
        '3' => ['Manrique', CommuneType::Comuna, 6.2780, -75.5480],
        '4' => ['Aranjuez', CommuneType::Comuna, 6.2800, -75.5580],
        '5' => ['Castilla', CommuneType::Comuna, 6.2930, -75.5720],
        '6' => ['Doce de Octubre', CommuneType::Comuna, 6.3040, -75.5850],
        '7' => ['Robledo', CommuneType::Comuna, 6.2780, -75.5950],
        '8' => ['Villa Hermosa', CommuneType::Comuna, 6.2530, -75.5480],
        '9' => ['Buenos Aires', CommuneType::Comuna, 6.2350, -75.5510],
        '10' => ['La Candelaria', CommuneType::Comuna, 6.2480, -75.5680],
        '11' => ['Laureles-Estadio', CommuneType::Comuna, 6.2500, -75.5900],
        '12' => ['La América', CommuneType::Comuna, 6.2540, -75.6060],
        '13' => ['San Javier', CommuneType::Comuna, 6.2560, -75.6180],
        '14' => ['El Poblado', CommuneType::Comuna, 6.2080, -75.5680],
        '15' => ['Guayabal', CommuneType::Comuna, 6.2130, -75.5880],
        '16' => ['Belén', CommuneType::Comuna, 6.2300, -75.6000],
        '50' => ['San Sebastián de Palmitas', CommuneType::Corregimiento, 6.3430, -75.6900],
        '60' => ['San Cristóbal', CommuneType::Corregimiento, 6.2800, -75.6350],
        '70' => ['Altavista', CommuneType::Corregimiento, 6.2230, -75.6280],
        '80' => ['San Antonio de Prado', CommuneType::Corregimiento, 6.1850, -75.6550],
        '90' => ['Santa Elena', CommuneType::Corregimiento, 6.2100, -75.5000],
    ];

    /** Pares de comunas que limitan entre sí (se guardan en ambos sentidos). */
    private const BORDERS = [
        ['1', '2'], ['1', '3'], ['1', '90'],
        ['2', '3'], ['2', '4'], ['2', '5'],
        ['3', '4'], ['3', '8'], ['3', '90'],
        ['4', '5'], ['4', '8'], ['4', '10'],
        ['5', '6'], ['5', '7'], ['5', '10'], ['5', '11'],
        ['6', '7'],
        ['7', '11'], ['7', '12'], ['7', '13'], ['7', '60'],
        ['8', '9'], ['8', '10'], ['8', '90'],
        ['9', '10'], ['9', '14'], ['9', '90'],
        ['10', '11'], ['10', '14'], ['10', '15'],
        ['11', '12'], ['11', '16'],
        ['12', '13'], ['12', '16'],
        ['13', '16'], ['13', '60'], ['13', '70'],
        ['14', '15'], ['14', '90'],
        ['15', '16'],
        ['16', '70'],
        ['50', '60'],
        ['60', '70'],
        ['70', '80'],
    ];

    public function run(): void
    {
        $ids = [];

        foreach (self::COMMUNES as $code => [$name, $type, $latitude, $longitude]) {
            $ids[$code] = Commune::updateOrCreate(
                ['code' => $code],
                ['name' => $name, 'type' => $type, 'latitude' => $latitude, 'longitude' => $longitude],
            )->id;
        }

        $rows = [];
        foreach (self::BORDERS as [$a, $b]) {
            $rows[] = ['commune_id' => $ids[$a], 'neighbor_id' => $ids[$b]];
            $rows[] = ['commune_id' => $ids[$b], 'neighbor_id' => $ids[$a]];
        }

        DB::table('commune_neighbors')->insertOrIgnore($rows);
    }
}
