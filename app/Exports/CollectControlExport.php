<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CollectControlExport implements FromArray, WithHeadings
{
    use Exportable;

    private $collect = null;

    public function __construct($_collect)
    {
        $this->collect = $_collect;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'Número Mak3r',
            'Nombre Mak3r',
            'Mak3r alias',
            'Teléfono',
            'Dirección',
            'Localidad',
            'Provincia',
            'Código postal',
            'Nombre material',
            'Cantidad material a entregar',
            'Nombre pieza',
            'Cantidad a recoger'
        ];
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

            foreach ($item->materials as $material) {
                $array[$count][] = $material->MaterialRequest->Piece->name;
                $array[$count][] = $material->units_delivered;
            }

            foreach ($item->pieces as $piece) {
                $array[$count][] = $piece->Piece->name;
                $array[$count][] = $piece->units;
            }

            $count++;
        }

        return $array;
    }
}
