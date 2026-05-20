<?php

namespace App\Services;

use App\Models\User;
use App\Models\ClientProfile;
use App\Models\ProfessionalProfile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    protected UploadService $uploader;

    public function __construct(UploadService $uploader)
    {
        $this->uploader = $uploader;
    }

    public function registerClient(array $data): User
    {
        // create user
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'phone' => $data['phone'] ?? null,
            'role' => 'client',
            'commune' => $data['commune'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
        ]);

        // store files
        $clientProfile = new ClientProfile();
        $clientProfile->user_id = $user->id;

        if (!empty($data['selfie'])) {
            $clientProfile->selfie_path = $this->uploader->store($data['selfie'], 'selfies');
        }

        if (!empty($data['document'])) {
            $clientProfile->document_path = $this->uploader->store($data['document'], 'documents');
        }

        $clientProfile->address = $data['address'] ?? null;
        $clientProfile->birth_date = $data['birth_date'] ?? null;
        $clientProfile->save();

        return $user;
    }

    public function registerProfessional(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'phone' => $data['phone'] ?? null,
            'role' => 'professional',
            'commune' => $data['commune'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
        ]);

        $profile = ProfessionalProfile::create([
            'user_id' => $user->id,
            'description' => $data['description'] ?? null,
            'experience_years' => $data['experience_years'] ?? 0,
            'hourly_rate' => $data['hourly_rate'] ?? null,
            'commune' => $data['commune'] ?? null,
        ]);

        // profile photo
        if (!empty($data['profile_photo'])) {
            $user->avatar = $this->uploader->store($data['profile_photo'], 'profiles');
            $user->save();
        }

        // attach specialties
        if (!empty($data['specialties']) && is_array($data['specialties'])) {
            $profile->specialties()->sync($data['specialties']);
        }

        // certificates
        if (!empty($data['certificates']) && is_array($data['certificates'])) {
            foreach ($data['certificates'] as $cert) {
                $profile->certificates()->create([
                    'file_path' => $this->uploader->store($cert, 'certificates'),
                    'title' => null,
                ]);
            }
        }

        // portfolio
        if (!empty($data['portfolio_images']) && is_array($data['portfolio_images'])) {
            foreach ($data['portfolio_images'] as $img) {
                $profile->portfolioItems()->create([
                    'image_path' => $this->uploader->store($img, 'portfolio'),
                ]);
            }
        }

        return $user;
    }
}


