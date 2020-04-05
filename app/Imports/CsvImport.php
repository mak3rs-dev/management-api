<?php

namespace App\Imports;

use App\Models\CollectControl;
use App\Models\CollectPieces;
use App\Models\Community;
use App\Models\InCommunity;
use App\Models\Piece;
use App\Models\Role;
use App\Models\Status;
use App\Models\StockControl;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class CsvImport implements ToCollection, WithHeadingRow
{
    /**
    * @param Collection $collection
    */
    public function collection(Collection $collection)
    {
        // Create Community
        $community = Community::create([
            'uuid' => Str::uuid(),
            'name' => 'Mak3rs Córdoba',
            'alias' => '@Mak3rs'
        ]);

        if ($community != null) {
            // Create Piece
            $piece = Piece::create([
                'uuid' => Str::uuid(),
                'name' => 'Visera',
                'community_id' => $community->id
            ]);

            foreach ($collection as $row)
            {
                // Import User
                $user = User::create([
                    'uuid' => Str::uuid(),
                    'alias' => trim($row['alias']) == '' ? null : trim($row['alias']),
                    'name' => trim($row['name']),
                    'email' => trim($row['email']),
                    'phone' => trim($row['phone']) == '' ? null : trim($row['phone']),
                    'address' => trim($row['address']) == '' ? null : trim($row['address']),
                    'cp' => trim($row['cp']) == '' ? null : trim($row['cp']),
                    'location' => trim($row['location']) == '' ? null : trim($row['location']),
                    'province' => trim($row['province']) == '' ? null : trim($row['province']),
                    'state' => trim($row['state']) == '' ? null : trim($row['state']),
                    'country' => trim($row['country']) == '' ? null : trim($row['country']),
                    'role_id' => Role::where('name', 'USER:COMMON')->first()->id
                ]);

                if ($user != null) {
                    // Join User Community
                    $inCommunity = InCommunity::create([
                        'community_id' => $community->id,
                        'user_id' => $user->id,
                        'role_id' => Role::where('name', 'MAKER:USER')->first()->id,
                        'mak3r_num' => intval($row['mak3r_id'])
                    ]);

                    if ($inCommunity != null && $piece != null) {
                        // Import Stock
                        $stock = StockControl::create([
                            'in_community_id' => $inCommunity->id,
                            'piece_id' => $piece->id,
                            'units_manufactured' => intval($row['units_manufactured'])
                        ]);

                        // Import Collected
                        $collect = CollectControl::create([
                            'in_community_id' => $inCommunity->id,
                            'status_id' => Status::where('code', 'COLLECT:RECEIVED')->first()->id
                        ]);

                        if ($collect != null) {
                            // Import Collect Pieces
                            $pieces = CollectPieces::create([
                                'collect_control_id' => $collect->id,
                                'piece_id' => $piece->id,
                                'units' => intval($row['units_collected'])
                            ]);
                        }
                    }
                }
            }
        }

    }
}
