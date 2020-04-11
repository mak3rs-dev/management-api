<?php

namespace App\Imports;

use App\Models\CollectControl;
use App\Models\CollectMaterial;
use App\Models\Community;
use App\Models\MaterialRequest;
use App\Models\Piece;
use App\Models\Status;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UsersFixMaterialsImport implements ToCollection, WithHeadingRow
{
    /**
    * @param Collection $collection
    */
    public function collection(Collection $collection)
    {
        // Obtains Community
        $community = Community::where('alias', '@Cv19CordobaMAK3RS')->first();

        foreach ($collection as $row) {
            if (trim($row['mak3r_id']) != '') {
                $inCommunity = $community->InCommunities->where('mak3r_num', trim($row['mak3r_id']))->first();

                if ($inCommunity != null) {
                    $material = Piece::where('name', 'PLA')->where('is_material', 1)->first();

                    if ($material != null) {
                        $collect = $inCommunity->CollectControl->first();

                        if ($collect == null) {
                            $collect = CollectControl::create([
                                'in_community_id' => $inCommunity->id,
                                'status_id' => Status::where('code', 'COLLECT:RECEIVED')->first()->id
                            ]);
                        }

                        if (intval($row['units_delivered']) > 0) {
                            $materialsRequest = $inCommunity->MaterialsRequest->where('piece_id', $material->id)->first();

                            if ($materialsRequest == null) {
                                $materialsRequest = MaterialRequest::create([
                                    'in_community_id' => $inCommunity->id,
                                    'piece_id' => $material->id,
                                    'units_request' => intval($row['units_delivered'])
                                ]);

                            } else {
                                $materialsRequest->units_request = intval($row['units_delivered']);
                                $materialsRequest->save();
                            }

                           if ($materialsRequest != null) {
                               // Import Collect Pieces
                               $collectMaterial = CollectMaterial::create([
                                   'material_requests_id' => $materialsRequest->id,
                                   'collect_control_id' => $collect->id,
                                   'piece_id' => $material->id,
                                   'units_delivered' => intval($row['units_delivered'])
                               ]);
                           }
                        }
                    }
                }
            }
        }
    }
}
