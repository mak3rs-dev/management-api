<?php

namespace App\Exports;

use App\Models\Piece;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CollectControlExport implements FromArray, WithHeadings
{
    use Exportable;

    private $collect;
    private $community;
    private $header;
    private $headerDates;
    private $headerMaterial = [];
    private $headerPieces = [];
    private $collectsMaterial;
    private $collectsPieces;

    public function __construct($_collect, $_community)
    {
        $this->collect = $_collect;
        $this->community = $_community;

        $this->header = [
            'Número Mak3r',
            'Nombre Mak3r',
            'Mak3r alias',
            'Teléfono',
            'Dirección',
            'Localidad',
            'Provincia',
            'Código postal'
        ];

        $this->headerDates = [
            'Fecha Creación',
            'Fecha Actualización'
        ];

        $this->collectsMaterial = Piece::where('is_material', 1)->where('community_id', $this->community->id)->get();
        $this->collectsPieces = Piece::where('is_piece', 1)->where('community_id', $this->community->id)->get();

        $countHeader = count($this->header);
        foreach ($this->collectsMaterial as $item) {
            array_push($this->header, $item->name);
            $this->headerMaterial[$item->uuid] = $countHeader;
            $countHeader++;
        }

        foreach ($this->collectsPieces as $item) {
            array_push($this->header, $item->name);
            $this->headerPieces[$item->uuid] = $countHeader;
            $countHeader++;
        }

        foreach ($this->headerDates as $item) {
            array_push($this->header, $item);
            $countHeader++;
        }
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return $this->header;
    }

    /**
     * @inheritDoc
     */
    public function array(): array
    {
        $array = [];
        $collecControl = $this->collect->orderBy('collect_cp', 'asc')->get();

        $count = 0;
        foreach ($collecControl as $item) {
            $array[$count][] = $item->mak3r_num;
            $array[$count][] = $item->user_name;
            $array[$count][] = $item->user_alias;
            $array[$count][] = $item->phone;
            $array[$count][] = $item->collect_address;
            $array[$count][] = $item->collect_location;
            $array[$count][] = $item->collect_province;
            $array[$count][] = $item->collect_cp;

            foreach ($this->headerMaterial as $key => $value) {
                $countMaterial = 0;
                foreach ($item->materials as $material) {
                    if ($key == $material->MaterialRequest->Piece->uuid) {
                        $array[$count][$value] = $material->units_delivered;

                    } else {
                        $countMaterial++;
                    }
                }

                if ($countMaterial == count($item->materials)) {
                    $array[$count][$value] = '';
                }
            }

            foreach ($this->headerPieces as $key => $value) {
                $countPieces = 0;
                foreach ($item->pieces as $piece) {
                    if ($key == $piece->Piece->uuid) {
                        $array[$count][$value] = $piece->units;

                    } else {
                        $countPieces++;
                    }
                }

                if ($countPieces == count($item->pieces)) {
                    $array[$count][$value] = '';
                }
            }

            $array[$count][] = Carbon::parse($item->created_at)->format('d-m-Y H:i:s');
            $array[$count][] = Carbon::parse($item->updated_at)->format('d-m-Y H:i:s');

            $count++;
        }

        return $array;
    }
}
