<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\Room;

class RoomsSeeder extends Seeder {
    public function run(): void {
        Room::create(['name' => 'Room 101', 'capacity' => 30]);
        Room::create(['name' => 'Room 102', 'capacity' => 50]);
        // Add professors, etc., in separate seeders
    }
}

