<?php

namespace Database\Seeders;

use App\Models\Bed;
use App\Models\Room;
use Database\Seeders\Concerns\SeedsBranches;
use Illuminate\Database\Seeder;

class BedSeeder extends Seeder
{
    use SeedsBranches;

    public function run(): void
    {
        $rooms = Room::query()
            ->when($this->onlyBranch, fn($query) => $query->where('branch_id', $this->onlyBranch->branch_id))
            ->get();

        if ($rooms->isEmpty()) {
            $this->command?->warn('No rooms found. Seed rooms first.');
            return;
        }

        foreach ($rooms as $room) {
            foreach (range(1, $room->capacity) as $i) {
                Bed::firstOrCreate(
                    [
                        'room_id' => $room->room_id,
                        'bed_no'  => 'B' . $i,
                    ],
                    ['status' => Bed::STATUS_AVAILABLE]
                );
            }
        }
    }
}
