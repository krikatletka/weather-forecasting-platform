<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Seeder;

class CitySeeder extends Seeder
{
    public function run(): void
    {
        $cities = [
            [
                'name' => 'Київ',
                'latitude' => 50.450100,
                'longitude' => 30.523400,
            ],
            [
                'name' => 'Харків',
                'latitude' => 49.993500,
                'longitude' => 36.230400,
            ],
            [
                'name' => 'Львів',
                'latitude' => 49.839700,
                'longitude' => 24.029700,
            ],
            [
                'name' => 'Одеса',
                'latitude' => 46.482500,
                'longitude' => 30.723300,
            ],
            [
                'name' => 'Дніпро',
                'latitude' => 48.464700,
                'longitude' => 35.046200,
            ],
            [
                'name' => 'Запоріжжя',
                'latitude' => 47.838800,
                'longitude' => 35.139600,
            ],
            [
                'name' => 'Полтава',
                'latitude' => 49.588300,
                'longitude' => 34.551400,
            ],
            [
                'name' => 'Чернігів',
                'latitude' => 51.498200,
                'longitude' => 31.289300,
            ],
            [
                'name' => 'Вінниця',
                'latitude' => 49.233100,
                'longitude' => 28.468200,
            ],
            [
                'name' => 'Івано-Франківськ',
                'latitude' => 48.922600,
                'longitude' => 24.711100,
            ],
        ];

        foreach ($cities as $city) {
            City::updateOrCreate(
                ['name' => $city['name']],
                [
                    'latitude' => $city['latitude'],
                    'longitude' => $city['longitude'],
                    'timezone' => 'Europe/Kyiv',
                    'is_active' => true,
                ]
            );
        }
    }
}
