<?php

namespace App\Exports;

use App\Models\InCommunity;
use App\Models\StockControl;
use Illuminate\Database\Query\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class RankingExport implements FromArray, WithHeadings, WithMapping
{
    use Exportable;

    private $community;
    private $ranking;

    public function __construct($_community, $_ranking)
    {
        $this->community = $_community;
        $this->ranking = $_ranking;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'Número Mak3r',
            'Mak3r alias',
            'Nombre Mak3r',
            'Piezas Fabricadas',
            'Piezas Entregadas',
            'Stock',
            'Dirección',
            'Localidad',
            'Provincia',
            'Comunidad',
            'País',
            'Código postal'
        ];
    }

    /**
     * @inheritDoc
     */
    public function map($row): array
    {
        return  [
            $row->mak3r_num,
            $row->user_alias,
            $row->user_name,
            $row->units_manufactured,
            $row->units_collected,
            $row->stock,
            $row->user_address,
            $row->user_location,
            $row->user_province,
            $row->user_state,
            $row->user_country,
            $row->user_cp
        ];
    }

    /**
     * @inheritDoc
     */
    public function array(): array
    {
        return $this->ranking->get()->toArray();
    }
}
