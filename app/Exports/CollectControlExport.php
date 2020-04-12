<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CollectControlExport implements FromArray, WithHeadings
{
    use Exportable;

    private $collect = null;
    private $header = [
        'Nombre Mak3r',
        'Mak3r alias',
        'Dirección',
        'Localidad',
        'Provincia',
        'Comunidad',
        'País',
        'Código postal'
    ];

    public function __construct($_collect)
    {
        $this->collect = $_collect;
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
            $array[$count][] = $item->user_name;
            $array[$count][] = $item->user_alias;
            $array[$count][] = $item->collect_address;
            $array[$count][] = $item->collect_location;
            $array[$count][] = $item->collect_province;
            $array[$count][] = $item->collect_state;
            $array[$count][] = $item->collect_country;
            $array[$count][] = $item->collect_cp;

            foreach ($item->materials as $material) {
                $array[$count][] = $material->MaterialRequest->Piece->name;
                $array[$count][] = $material->MaterialRequest->units_request;
                $array[$count][] = $material->units_delivered;
            }

            foreach ($item->pieces as $piece) {
                $array[$count][] = $piece->Piece->name;
                $array[$count][] = $piece->units;
            }

            $count++;
        }

        $this->header[] = 'Nombre material';
        $this->header[] = 'Cantidad material pedido';
        $this->header[] = 'Cantidad material a entregar';

        $this->header[] = 'Nombre pieza';
        $this->header[] = 'Cantidad a recoger';

        return $array;
    }
}
