<?php

namespace Database\Seeders;

use App\Models\Bed;
use App\Models\Room;
use Illuminate\Database\Seeder;

class BedSeeder extends Seeder
{
    public function run(): void
    {
        $rooms = Room::all();

        if ($rooms->isEmpty()) {
            $this->command->warn('No rooms found. Seed rooms first.');
            return;
        }

        foreach ($rooms as $room) {
            foreach (range(1, $room->capacity) as $i) {
                Bed::create([
                    'room_id' => $room->room_id,
                    'bed_no'  => $room->room_no . '-B' . $i,
                    'status'  => Bed::STATUS_AVAILABLE,
                ]);
            }
        }
    }
}
