<?php

namespace App\Exports;

use App\Models\InCommunity;
use App\Models\StockControl;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use DB;

class RankingExport implements FromCollection, WithHeadings
{
    use Exportable;

    private $community;

    public function __construct($_community)
    {
        $this->community = $_community;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'Nombre Mak3r',
            'Piezas fabricadas',
            'Piezas Entregadas',
            'Stock',
            'Mak3r alias',
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
    public function collection()
    {
        $select = ['u.name as user_name', DB::raw('IFNULL(SUM(sc.units_manufactured), 0) as units_manufactured'),
            DB::raw('IFNULL(SUM(cp.units), 0) as units_collected'),
            DB::raw('(units_manufactured - IFNULL(units, 0)) as stock'), 'u.alias as user_alias'];

        array_push($select, 'u.alias as user_alias');

        $inCommunity = null;
        $inCommunity = $this->community->InCommunitiesUser();

        if ($inCommunity != null && ( $inCommunity->hasRole('MAKER:ADMIN') || auth()->user()->hasRole('USER:ADMIN') )) {
            array_push($select, 'u.address as user_address');
            array_push($select, 'u.location as user_location');
            array_push($select, 'u.province as user_province');
            array_push($select, 'u.state as user_state');
            array_push($select, 'u.country as user_country');
            array_push($select, 'u.cp as user_cp');
        }

        $ranking = StockControl::from('stock_control as sc')
            ->join('in_community as ic', 'sc.in_community_id', '=', 'ic.id')
            ->join('users as u', 'u.id', '=', 'ic.user_id')
            ->leftJoin('collect_control as cc', 'cc.in_community_id', '=', 'ic.id')
            ->leftJoin('collect_pieces as cp', 'cp.collect_control_id', '=', 'cc.id')
            ->select($select)
            ->where('ic.community_id', $this->community->id)
            ->groupBy('ic.user_id')
            ->orderBy('stock', 'desc')
            ->get();

        return $ranking;
    }
}
