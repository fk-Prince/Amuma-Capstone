<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Room;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        $branches = Branch::all();

        if ($branches->isEmpty()) {
            $this->command->warn('No branches found. Seed branches first.');
            return;
        }

        foreach ($branches as $branch) {
            foreach (range(1, 5) as $floor) {
                foreach (['Common', 'VIP'] as $type) {
                    Room::create([
                        'branch_id'  => $branch->branch_id,
                        'room_no'    => strtoupper($type[0]) . $floor . '0' . rand(1, 9),
                        'floor'      => $floor,
                        'room_type'  => $type,
                        'capacity'   => $type === 'VIP' ? 1 : 4,
                        'status'     => 'Available',
                    ]);
                }
            }
        }
    }
}
