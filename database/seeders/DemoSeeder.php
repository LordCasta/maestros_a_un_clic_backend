<?php

namespace Database\Seeders;

use App\Enums\BookingStatus;
use App\Enums\PriceType;
use App\Enums\VerificationStatus;
use App\Models\AvailabilityRule;
use App\Models\Booking;
use App\Models\BookingStatusChange;
use App\Models\Category;
use App\Models\Commune;
use App\Models\ProfessionalService;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Datos de demostración para desarrollo local. Contraseña de todas las cuentas: `password`.
 *
 * Cuentas fijas:
 *  - admin@maestros.test        administrador
 *  - cliente@maestros.test      cliente verificado, con reservas en varios estados
 *  - pendiente@maestros.test    cliente sin verificar (no puede reservar)
 *  - profesional@maestros.test  profesional verificado, con servicios y agenda
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $communes = Commune::all()->keyBy('code');
        $subcategories = Category::whereNotNull('parent_id')->with('parent')->get();

        User::factory()->admin()->create([
            'name' => 'Admin Maestros',
            'email' => 'admin@maestros.test',
        ]);

        $client = User::factory()->client()->verified()->create([
            'name' => 'Camila Restrepo',
            'email' => 'cliente@maestros.test',
            'commune_id' => $communes['14']->id,
            'address' => 'Calle 10 # 43-20',
        ]);
        $client->clientProfile()->create();

        User::factory()->client()->create([
            'name' => 'Andrés Gómez',
            'email' => 'pendiente@maestros.test',
            'commune_id' => $communes['11']->id,
        ])->clientProfile()->create();

        $mainProfessional = $this->createProfessional([
            'name' => 'Juan Castaño',
            'email' => 'profesional@maestros.test',
            'commune_id' => $communes['11']->id,
        ], $subcategories->where('parent.slug', 'plomeria'));

        $otherProfessionals = collect(range(1, 12))->map(fn () => $this->createProfessional(
            ['commune_id' => $communes->random()->id],
            $subcategories->random(fake()->numberBetween(1, 3)),
        ));

        User::factory()->professional()->count(2)->create([
            'verification_status' => VerificationStatus::Pending,
            'commune_id' => $communes->random()->id,
        ]);

        $this->createClientBookings($client, $mainProfessional, $otherProfessionals->first());
    }

    /**
     * Profesional verificado con servicios en las subcategorías dadas y horario lunes a sábado.
     */
    private function createProfessional(array $attributes, Collection $subcategories): User
    {
        $professional = User::factory()->professional()->verified()->create($attributes);
        $profile = $professional->professionalProfile;

        $profile->categories()->sync($subcategories->pluck('parent_id')->unique());

        foreach ($subcategories as $subcategory) {
            ProfessionalService::factory()->for($profile)->create([
                'category_id' => $subcategory->id,
                'title' => $subcategory->name,
                'price_type' => fake()->randomElement(PriceType::cases()),
            ]);
        }

        foreach (range(1, 6) as $weekday) {
            AvailabilityRule::create([
                'professional_profile_id' => $profile->id,
                'weekday' => $weekday,
                'start_time' => '08:00',
                'end_time' => $weekday === 6 ? '13:00' : '17:00',
            ]);
        }

        return $professional;
    }

    private function createClientBookings(User $client, User $professional, User $otherProfessional): void
    {
        $service = $professional->professionalProfile->services()->first();
        $otherService = $otherProfessional->professionalProfile->services()->first();

        $this->createBooking($client, $service, Carbon::now()->addDays(2)->setTime(9, 0), BookingStatus::Pending);
        $this->createBooking($client, $otherService, Carbon::now()->addDays(4)->setTime(14, 0), BookingStatus::Confirmed);

        $completed = $this->createBooking($client, $service, Carbon::now()->subDays(7)->setTime(10, 0), BookingStatus::Completed);

        Review::create([
            'booking_id' => $completed->id,
            'reviewer_id' => $client->id,
            'reviewee_id' => $professional->id,
            'rating' => 5,
            'comment' => 'Muy puntual y dejó todo limpio. Lo recomiendo.',
        ]);
        Review::create([
            'booking_id' => $completed->id,
            'reviewer_id' => $professional->id,
            'reviewee_id' => $client->id,
            'rating' => 5,
            'comment' => 'Clienta amable y con el espacio listo para trabajar.',
        ]);

        foreach ([$client, $professional] as $user) {
            $user->forceFill([
                'rating_avg' => $user->reviewsReceived()->avg('rating') ?? 0,
                'rating_count' => $user->reviewsReceived()->count(),
            ])->save();
        }
    }

    private function createBooking(User $client, ProfessionalService $service, Carbon $startsAt, BookingStatus $status): Booking
    {
        $booking = Booking::factory()->create([
            'client_id' => $client->id,
            'professional_service_id' => $service->id,
            'address' => $client->address ?? 'Carrera 70 # 44-10',
            'commune_id' => $client->commune_id,
            'starts_at' => $startsAt,
            'status' => $status,
            'started_at' => $status === BookingStatus::Completed ? $startsAt : null,
            'completed_at' => $status === BookingStatus::Completed ? $startsAt->copy()->addMinutes($service->estimated_duration_minutes) : null,
        ]);

        BookingStatusChange::create([
            'booking_id' => $booking->id,
            'from_status' => null,
            'to_status' => BookingStatus::Pending,
            'changed_by' => $client->id,
        ]);

        return $booking;
    }
}
