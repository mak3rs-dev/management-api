<?php

namespace App\Exports;

use App\Models\CollectControl;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CollectControlExport implements FromCollection, WithHeadings
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
            'Mak3r alias',
            'Nombre pieza',
            'Cantidad',
            'Dirección',
            'Localidad',
            'Provincia',
            'Comunidad',
            'País',
            'Código postal'
        ];
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $select = ['u.name as name_user', 'u.alias as  alias_user', 'p.name as piece_name',
            'pc.units as cantidad', 'cc.address as address', 'cc.location as location',
            'cc.province as province', 'cc.state as state', 'cc.country as country',
            'cc.cp as cp'];

        $CollectControl = CollectControl::from('collect_control as cc')
            ->join('collect_pieces as pc', 'pc.collect_control_id', '=', 'cc.id')
            ->join('pieces as p', 'p.id', '=', 'pc.piece_id')
            ->join('status as st', 'st.id', '=', 'cc.status_id')
            ->join('users as u', 'u.id', '=', 'cc.user_id')
            ->select($select)
            ->when(!$this->community->hasRole('MAKER:ADMIN'), function ($query) {
                return $query->where('user_id', auth()->user()->id);
            })
            ->where('cc.community_id', $this->community->community_id)
            ->get();

        return $CollectControl;
    }
}
