<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Room;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    private const FLOORS = ['1st', '2nd', '3rd', '4th', '5th'];

    public function run(): void
    {
        $branches = Branch::all();

        if ($branches->isEmpty()) {
            $this->command->warn('No branches found. Seed branches first.');
            return;
        }

        foreach ($branches as $branch) {
            foreach (self::FLOORS as $index => $floor) {
                $level = $index + 1;

                foreach (['Common', 'VIP'] as $type) {
                    Room::firstOrCreate(
                        [
                            'branch_id' => $branch->branch_id,
                            'room_no'   => strtoupper($type[0]) . $level . '01',
                        ],
                        [
                            'floor'     => $floor,
                            'room_type' => $type,
                            'capacity'  => $type === 'VIP' ? 1 : 4,
                            'status'    => 'Available',
                        ]
                    );
                }
            }
        }
    }
}
