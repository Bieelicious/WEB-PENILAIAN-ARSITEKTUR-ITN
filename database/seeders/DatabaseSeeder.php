<?php

namespace Database\Seeders;

use App\Models\Room;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;
use Database\Seeders\ClassRoomSeeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            ShieldSeeder::class,
        ]);

        $admin = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@gmail.com',
        ]);

        $dosen1 = User::factory()->create([
            'name' => 'dosen 1',
            'email' => 'dosen1@gmail.com',
        ]);

        $dosen1->assignRole('lecturer');

        Artisan::call('shield:super-admin', ['--user' => $admin->getKey()]);
        Artisan::call('shield:generate', ['--all' => true, '--panel' => 'admin']);


        if (App::environment('demo')) {
            $demoUser = User::factory()->create([
                'name' => 'Demo User',
                'email' => 'demo@example.com',
            ]);

            Artisan::call('shield:generate', ['--all' => true, '--panel' => 'admin']);
            $demoUser->assignRole('demo');
        }

//        Room::get()->each(function (Room $classRoom) use ($admin, $dosen1, $dosen2, $dosen3, $dosen4) {
//            $classRoom->users()->attach([$admin->getKey(), $dosen1->getKey(), $dosen2->getKey(), $dosen3->getKey(), $dosen4->getKey()]);
//        });
    }
}
