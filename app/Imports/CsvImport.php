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
            'name' => 'Cv19CórdobaMAK3RS',
            'alias' => '@Mak3rs',
            'description' => '!!!¡¡¡Leer Anclado!!! https://t.me/Cv19CordobaMAK3RS

                            No se han suministrado más bobinas para envíos rápidos, así que reflejarlo en excel y ya se irá entregando por los medios de logística habilitados hasta ahora. 
                            
                            POR FAVOR COMUNICAR LAS BOBINAS RECIBIDAS, excepto enviadas por correos , DEBEN CUADRAR LAS UNIDADES ENVIADAS.
                            
                            TENEMOS CERTIFICACIÓN DEL IMIBIC PARA NUESTRO MODELO stl V2.
                            
                            CÓMO Y CUANDO SE HARÁN LAS RECOGIDAS? Mira esto: https://t.me/c/1444234935/7883
                            ¡Pon tu número Maker en la bolsa de entrega!
                            La actualización diaria de stock se realizará a las 15:00h por lo que habrá que tenerlas actualizadas antes de esa hora, para así proceder el volcado de datos en el fichero que se distribuirá a los encargados de las recogidas.
                            Por favor no modifiquéis este stock a menos cantidad, si hay más producidas mejor, piensa que personas están desplazándose por la ciudad, haciendo un esfuerzo para llegar a todos sitios, cargados con material para entregar. Una vez que recojan las viseras actualizar, las entregadas. Así mismo, enviar a @Eugeniatellez o @mparral las bobinas entregadas para así poder actualizar.
                            
                            Primero darte de alta:
                            Nuevo Obetivo: 12.000 viseras para el Lunes.
                            OBJETIVO Conseguido: 9000 viseras el Jueves. 
                            Debes estar apuntado en el excel https://docs.google.com/spreadsheets/d/1hZNsV1F_8cggEicenIwRnsf-JmTB2x4fYbAISAOGlVY/edit?usp=sharing).
                            
                            Segundo apúntate en Recogidas(apúntate aunque no hayas producido): https://forms.gle/vu1S2WhBxY9X8FJh6
                            
                            Revisión de impresiones: mensaje privado a @evoprint3D @Moebius3d @j_habas @antoniomoreno @garmanapp
                            
                            Ver video explicativo https://t.me/c/1444234935/8081
                            
                            Para pedidos ponerse en contacto con @eugeniatellez o @mparral
                            
                            https://docs.google.com/spreadsheets/d/1lRQKVe7iaL83IpE462rkdYP8viZfxP2h8aN13n_VJs0/edit#gid=0
                            
                            Postprocesado: vamos a intentar ayudar un poco antes de enviar las viseras. Aquí algunas técnicas: https://t.me/c/1444234935/7754
                            
                            Donaciones exclusivamente aquí: https://www.smartmaterials3d.com/smartfil-pla-covid-19#/111-donar_al_proyecto_-imibic'
        ]);

        if ($community != null) {
            // Create Piece
            $piece = Piece::create([
                'uuid' => Str::uuid(),
                'name' => 'Visera',
                'description' => 'A continuación te dejo unos enlaces de este mismo grupo para que descargues los GCODE para solo ponerte a imprimir (si tu impresora hace cosas raras, baja la velocidad al 70%)

                                IMPRESORAS BOWDEN DOS VISERAS:
                                https://t.me/c/1444234935/9180
                                
                                Ender 3 tipo Bowden NUEVA VISERA:
                                https://t.me/c/1444234935/14501
                                
                                Ender 3 Directo NUEVA VISERA:
                                https://t.me/c/1444234935/14502
                                
                                Cr10 tipo Bowden:
                                https://t.me/c/1444234935/162
                                
                                Cr10 Directo:
                                https://t.me/c/1444234935/163
                                
                                Anycubic I3M V2
                                https://t.me/c/1444234935/13383
                                
                                Si lo deseas, aquí tienes el STL para que tu mismo puedas editarlo e imprimirlo:
                                https://t.me/c/1444234935/13248   version2 reforzada
                                refuerzo en patillas, borde superior acabado recto, se anula hueco que no se le da uso y debilita la zona
                                
                                PERO recuerda que debes imprimir mínimo con:
                                Boquilla 0,4
                                Altura de capa 0,2
                                Relleno 50%
                                Sin Soportes
                                4 paredes exteriores 
                                5 capas superiores 
                                5 inferiores
                                Relleno de tipo REJILLA
                                Material exclusivamente PLA o PETG (no nocivos).',
                'community_id' => $community->id
            ]);

            foreach ($collection as $row)
            {
                // Import User
                $user = User::create([
                    'uuid' => Str::uuid(),
                    'alias' => trim($row['alias']) == '' ? null : trim($row['alias']),
                    'name' => trim($row['name']),
                    'email' => Str::lower(trim($row['email'])),
                    'phone' => trim($row['phone']) == '' ? null : trim($row['phone']),
                    'address' => trim($row['address']) == '' ? null : trim($row['address']),
                    'cp' => trim($row['cp']) == '' ? null : trim($row['cp']),
                    'password' => bcrypt(Str::uuid()),
                    'location' => trim($row['location']) == '' ? null : Str::ucfirst(trim($row['location'])), // UPPER First string
                    'province' => trim($row['province']) == '' ? null : Str::ucfirst(trim($row['province'])),
                    'state' => trim($row['state']) == '' ? null : Str::ucfirst(trim($row['state'])),
                    'country' => trim($row['country']) == '' ? null : Str::ucfirst(trim($row['country'])),
                    'address_description' => trim($row['address_comments']) == '' ? null : Str::ucfirst(trim($row['address_comments'])),
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
