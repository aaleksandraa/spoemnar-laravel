<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Place;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /**
     * Seed countries and places used by memorial forms.
     */
    public function run(): void
    {
        $countries = [
            'BA' => 'Bosna i Hercegovina',
            'RS' => 'Srbija',
            'HR' => 'Hrvatska',
            'IT' => 'Italija',
            'DE' => 'Njemacka',
            'AT' => 'Austrija',
            'ME' => 'Crna Gora',
        ];

        $placesByCountry = [
            'BA' => [
                ['Sarajevo', 'city'], ['Banja Luka', 'city'], ['Mostar', 'city'], ['Tuzla', 'city'],
                ['Zenica', 'city'], ['Bijeljina', 'city'], ['Brcko', 'city'], ['Prijedor', 'city'],
                ['Doboj', 'city'], ['Cazin', 'town'], ['Zvornik', 'town'], ['Zivinice', 'town'],
                ['Bihać', 'city'], ['Gradacac', 'town'], ['Visoko', 'town'], ['Trebinje', 'city'],
                ['Gorazde', 'town'], ['Konjic', 'town'], ['Travnik', 'town'], ['Bugojno', 'town'],
                ['Livno', 'town'], ['Gracanica', 'town'], ['Srebrenik', 'town'], ['Tesanj', 'town'],
                ['Kakanj', 'town'], ['Kalesija', 'town'], ['Sanski Most', 'town'], ['Foca', 'town'],
                ['Jajce', 'town'], ['Nevesinje', 'town'], ['Siroki Brijeg', 'town'], ['Capljina', 'town'],
                ['Lukavac', 'town'], ['Maglaj', 'town'], ['Bosanska Krupa', 'town'], ['Velika Kladusa', 'town'],
                ['Ilidza', 'town'], ['Pale', 'town'], ['Sokolac', 'town'], ['Rogatica', 'town'],
                ['Posusje', 'town'], ['Tomislavgrad', 'town'], ['Kiseljak', 'town'], ['Vitez', 'town'],
                ['Busovaca', 'town'], ['Fojnica', 'town'], ['Jelah', 'village'], ['Kiseljak (Tesanj)', 'village'],
                ['Maoča', 'village'], ['Ahmici', 'village'], ['Pocitelj', 'village'], ['Blagaj', 'village'],
                ['Medjugorje', 'village'], ['Jablanica', 'town'], ['Stolac', 'town'],
            ],
            'RS' => [
                ['Beograd', 'city'], ['Novi Sad', 'city'], ['Nis', 'city'], ['Kragujevac', 'city'],
                ['Subotica', 'city'], ['Zrenjanin', 'city'], ['Pancevo', 'city'], ['Cacak', 'city'],
                ['Novi Pazar', 'city'], ['Kraljevo', 'city'], ['Smederevo', 'city'], ['Leskovac', 'city'],
                ['Uzice', 'city'], ['Valjevo', 'city'], ['Vranje', 'city'], ['Sombor', 'city'],
                ['Pozarevac', 'town'], ['Pirot', 'town'], ['Sabac', 'city'], ['Sremska Mitrovica', 'city'],
                ['Loznica', 'town'], ['Jagodina', 'town'], ['Kikinda', 'town'], ['Ruma', 'town'],
                ['Backa Palanka', 'town'], ['Vrbas', 'town'], ['Gornji Milanovac', 'town'],
                ['Arandjelovac', 'town'], ['Prijepolje', 'town'], ['Bor', 'town'], ['Majdanpek', 'town'],
                ['Topola', 'town'], ['Inđija', 'town'], ['Stara Pazova', 'town'], ['Obrenovac', 'town'],
                ['Mladenovac', 'town'], ['Sopot', 'town'], ['Kovin', 'town'], ['Apatin', 'town'],
                ['Backi Petrovac', 'town'], ['Svilajnac', 'town'], ['Cuprija', 'town'], ['Paracin', 'town'],
                ['Ivanjica', 'town'], ['Lajkovac', 'town'], ['Bajina Basta', 'town'], ['Kosjeric', 'town'],
                ['Guča', 'village'], ['Mokra Gora', 'village'], ['Kustendorf', 'village'], ['Sirig', 'village'],
            ],
            'HR' => [
                ['Zagreb', 'city'], ['Split', 'city'], ['Rijeka', 'city'], ['Osijek', 'city'],
                ['Zadar', 'city'], ['Velika Gorica', 'city'], ['Pula', 'city'], ['Slavonski Brod', 'city'],
                ['Karlovac', 'city'], ['Varazdin', 'city'], ['Sibenik', 'city'], ['Sisak', 'city'],
                ['Dubrovnik', 'city'], ['Bjelovar', 'city'], ['Samobor', 'town'], ['Vinkovci', 'city'],
                ['Koprivnica', 'city'], ['Vukovar', 'city'], ['Cakovec', 'city'], ['Pozega', 'city'],
                ['Nasice', 'town'], ['Makarska', 'town'], ['Sinj', 'town'], ['Knin', 'town'],
                ['Trogir', 'town'], ['Porec', 'town'], ['Rovinj', 'town'], ['Umag', 'town'],
                ['Labin', 'town'], ['Crikvenica', 'town'], ['Opatija', 'town'], ['Delnice', 'town'],
                ['Gospic', 'town'], ['Ogulin', 'town'], ['Krapina', 'town'], ['Zapresic', 'town'],
                ['Dugo Selo', 'town'], ['Kastela', 'town'], ['Metkovic', 'town'], ['Imotski', 'town'],
                ['Korcula', 'town'], ['Hvar', 'town'], ['Pag', 'town'], ['Nin', 'town'],
                ['Vodice', 'town'], ['Biograd na Moru', 'town'], ['Cavtat', 'town'], ['Trilj', 'town'],
                ['Vrbnik', 'village'], ['Kumrovec', 'village'], ['Rastoke', 'village'], ['Murter', 'village'],
            ],
            'IT' => [
                ['Roma', 'city'], ['Milano', 'city'], ['Napoli', 'city'], ['Torino', 'city'],
                ['Palermo', 'city'], ['Genova', 'city'], ['Bologna', 'city'], ['Firenze', 'city'],
                ['Bari', 'city'], ['Catania', 'city'], ['Venezia', 'city'], ['Verona', 'city'],
                ['Messina', 'city'], ['Padova', 'city'], ['Trieste', 'city'], ['Taranto', 'city'],
                ['Brescia', 'city'], ['Parma', 'city'], ['Modena', 'city'], ['Reggio Calabria', 'city'],
                ['Perugia', 'city'], ['Ravenna', 'city'], ['Rimini', 'city'], ['Livorno', 'city'],
                ['Cagliari', 'city'], ['Salerno', 'city'], ['Ferrara', 'city'], ['Siena', 'town'],
                ['Lucca', 'town'], ['Pisa', 'city'], ['Arezzo', 'town'], ['Bergamo', 'city'],
                ['Monza', 'city'], ['Como', 'town'], ['Lecce', 'city'], ['Foggia', 'city'],
                ['Matera', 'town'], ['Trento', 'city'], ['Bolzano', 'city'], ['Udine', 'city'],
                ['Asti', 'town'], ['Alba', 'town'], ['Sorrento', 'town'], ['Positano', 'town'],
                ['Assisi', 'town'], ['Orvieto', 'town'], ['San Gimignano', 'town'], ['Bormio', 'town'],
                ['Cortina d Ampezzo', 'town'], ['Taormina', 'town'], ['Ravello', 'village'], ['Vernazza', 'village'],
            ],
            'DE' => [
                ['Berlin', 'city'], ['Hamburg', 'city'], ['Muenchen', 'city'], ['Koeln', 'city'],
                ['Frankfurt am Main', 'city'], ['Stuttgart', 'city'], ['Duesseldorf', 'city'], ['Dortmund', 'city'],
                ['Essen', 'city'], ['Leipzig', 'city'], ['Bremen', 'city'], ['Dresden', 'city'],
                ['Hannover', 'city'], ['Nuernberg', 'city'], ['Duisburg', 'city'], ['Bochum', 'city'],
                ['Wuppertal', 'city'], ['Bielefeld', 'city'], ['Bonn', 'city'], ['Muenster', 'city'],
                ['Karlsruhe', 'city'], ['Mannheim', 'city'], ['Augsburg', 'city'], ['Wiesbaden', 'city'],
                ['Gelsenkirchen', 'city'], ['Moenchengladbach', 'city'], ['Braunschweig', 'city'], ['Chemnitz', 'city'],
                ['Kiel', 'city'], ['Aachen', 'city'], ['Magdeburg', 'city'], ['Freiburg im Breisgau', 'city'],
                ['Mainz', 'city'], ['Luebeck', 'city'], ['Rostock', 'city'], ['Kassel', 'city'],
                ['Potsdam', 'city'], ['Erfurt', 'city'], ['Regensburg', 'city'], ['Ulm', 'city'],
                ['Heidelberg', 'city'], ['Wuerzburg', 'city'], ['Goettingen', 'city'], ['Trier', 'city'],
                ['Passau', 'town'], ['Bamberg', 'town'], ['Berchtesgaden', 'town'], ['Garmisch-Partenkirchen', 'town'],
                ['Rothenburg ob der Tauber', 'town'], ['Mittenwald', 'village'], ['Binz', 'village'], ['Cochem', 'village'],
            ],
            'AT' => [
                ['Wien', 'city'], ['Graz', 'city'], ['Linz', 'city'], ['Salzburg', 'city'],
                ['Innsbruck', 'city'], ['Klagenfurt', 'city'], ['Villach', 'city'], ['Wels', 'city'],
                ['Sankt Poelten', 'city'], ['Dornbirn', 'city'], ['Wiener Neustadt', 'city'], ['Steyr', 'city'],
                ['Feldkirch', 'city'], ['Bregenz', 'city'], ['Leonding', 'town'], ['Klosterneuburg', 'town'],
                ['Baden', 'town'], ['Wolfsberg', 'town'], ['Leoben', 'town'], ['Krems an der Donau', 'town'],
                ['Traun', 'town'], ['Amstetten', 'town'], ['Lustenau', 'town'], ['Kapfenberg', 'town'],
                ['Mödling', 'town'], ['Hall in Tirol', 'town'], ['Spittal an der Drau', 'town'], ['Kufstein', 'town'],
                ['Schwechat', 'town'], ['Tulln an der Donau', 'town'], ['Bischofshofen', 'town'], ['St. Johann im Pongau', 'town'],
                ['Bad Ischl', 'town'], ['Zell am See', 'town'], ['Saalbach', 'village'], ['Kitzbuehel', 'town'],
                ['Mayrhofen', 'village'], ['Seefeld in Tirol', 'village'], ['Ischgl', 'village'], ['Solden', 'village'],
                ['Eisenstadt', 'city'], ['Neusiedl am See', 'town'], ['Rust', 'town'], ['Melk', 'town'],
                ['Waidhofen an der Ybbs', 'town'], ['Zwettl', 'town'], ['Lienz', 'town'], ['Schladming', 'town'],
                ['Bad Gastein', 'village'], ['Mariazell', 'village'], ['Heiligenblut', 'village'], ['Gmunden', 'town'],
            ],
            'ME' => [
                ['Podgorica', 'city'], ['Niksic', 'city'], ['Herceg Novi', 'city'], ['Budva', 'city'],
                ['Bar', 'city'], ['Bijelo Polje', 'city'], ['Cetinje', 'city'], ['Pljevlja', 'city'],
                ['Ulcinj', 'town'], ['Tivat', 'town'], ['Berane', 'town'], ['Rozaje', 'town'],
                ['Kotor', 'town'], ['Danilovgrad', 'town'], ['Mojkovac', 'town'], ['Kolasin', 'town'],
                ['Plav', 'town'], ['Andrijevica', 'town'], ['Petnjica', 'town'], ['Savnik', 'town'],
                ['Zabljak', 'town'], ['Gusinje', 'town'], ['Tuzi', 'town'], ['Golubovci', 'town'],
                ['Sutomore', 'town'], ['Petrovac', 'town'], ['Igalo', 'town'], ['Risan', 'town'],
                ['Perast', 'town'], ['Dobrota', 'town'], ['Stoliv', 'village'], ['Morinj', 'village'],
                ['Njegusi', 'village'], ['Virpazar', 'village'], ['Rijeka Crnojevica', 'village'], ['Murići', 'village'],
                ['Vranjina', 'village'], ['Kumbor', 'village'], ['Krašići', 'village'], ['Radovići', 'village'],
                ['Grahovo', 'village'], ['Crkvice', 'village'], ['Biogradska Gora', 'settlement'], ['Durmitor', 'settlement'],
                ['Pluzine', 'town'], ['Susanj', 'town'], ['Donja Lastva', 'village'], ['Prcanj', 'village'],
                ['Muo', 'village'], ['Orahovac', 'village'], ['Lepetani', 'village'], ['Zelenika', 'village'],
            ],
        ];

        foreach ($countries as $code => $name) {
            $country = Country::updateOrCreate(
                ['code' => $code],
                ['name' => $name, 'is_active' => true]
            );

            $places = $placesByCountry[$code] ?? [];
            foreach ($places as [$placeName, $type]) {
                Place::updateOrCreate(
                    [
                        'country_id' => $country->id,
                        'name' => $placeName,
                    ],
                    [
                        'type' => $type,
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}

