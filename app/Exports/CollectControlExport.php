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

        foreach ($collecControl as $item) {
            $array[0][] = $item->user_name;
            $array[1][] = $item->user_alias;
            $array[2][] = $item->collect_address;
            $array[3][] = $item->collect_location;
            $array[4][] = $item->collect_province;
            $array[5][] = $item->collect_state;
            $array[6][] = $item->collect_country;
            $array[7][] = $item->collect_cp;

            foreach ($item->materials as $material) {
                $array[8][] = $material->MaterialRequest->Piece->name;
                $array[10][] = $material->MaterialRequest->units_request;
                $array[11][] = $material->units_delivered;
            }

            foreach ($item->pieces as $piece) {
                $array[12][] = $piece->Piece->name;
                $array[13][] = $piece->units;
            }
        }

        $this->header[] = 'Nombre material';
        $this->header[] = 'Cantidad material pedido';
        $this->header[] = 'Cantidad material a entregar';

        $this->header[] = 'Nombre pieza';
        $this->header[] = 'Cantidad a recoger';

        return $array;
    }
}
