<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Memorial;
use App\Models\Tribute;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class MemorialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create test users
        $user1 = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'password' => Hash::make('password'),
                'role' => 'user',
            ]
        );

        $user2 = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]
        );

        // Memorial 1 - Nikola Tesla
        $memorial1 = Memorial::create([
            'user_id' => $user1->id,
            'first_name' => 'Nikola',
            'last_name' => 'Tesla',
            'slug' => 'nikola-tesla',
            'birth_date' => '1856-07-10',
            'death_date' => '1943-01-07',
            'birth_place' => 'Smiljan, Austro-Ugarska',
            'death_place' => 'New York, SAD',
            'biography' => 'Nikola Tesla bio je srpski i američki pronalazač, fizičar, inženjer elektrotehnike i futurista. Najpoznatiji je po svojim doprinosima u dizajnu modernog sistema naizmjenične struje (AC). Tesla je držao oko 300 patenata širom svijeta za svoje pronalaske.',
            'is_public' => true,
        ]);

        // Add tributes for Tesla
        Tribute::create([
            'memorial_id' => $memorial1->id,
            'author_name' => 'Marko Petrović',
            'author_email' => 'marko@example.com',
            'message' => 'Veliki um koji je promijenio svijet. Hvala za sve što si dao čovječanstvu.',
        ]);

        Tribute::create([
            'memorial_id' => $memorial1->id,
            'author_name' => 'Ana Jovanović',
            'author_email' => 'ana@example.com',
            'message' => 'Tvoji pronalasci i dalje inspirišu generacije naučnika i inženjera.',
        ]);

        // Memorial 2 - Ivo Andrić
        $memorial2 = Memorial::create([
            'user_id' => $user1->id,
            'first_name' => 'Ivo',
            'last_name' => 'Andrić',
            'slug' => 'ivo-andric',
            'birth_date' => '1892-10-09',
            'death_date' => '1975-03-13',
            'birth_place' => 'Dolac, Austro-Ugarska',
            'death_place' => 'Beograd, Jugoslavija',
            'biography' => 'Ivo Andrić bio je jugoslovenski književnik, dobitnik Nobelove nagrade za književnost 1961. godine. Najpoznatiji je po romanima "Na Drini ćuprija", "Travnička hronika" i "Gospođica". Njegova djela istražuju historiju i kulturu Bosne i Hercegovine.',
            'is_public' => true,
        ]);

        Tribute::create([
            'memorial_id' => $memorial2->id,
            'author_name' => 'Jelena Nikolić',
            'author_email' => 'jelena@example.com',
            'message' => 'Tvoja djela su vječna. Na Drini ćuprija ostaje remek-djelo svjetske književnosti.',
        ]);

        // Memorial 3 - Mihajlo Pupin
        $memorial3 = Memorial::create([
            'user_id' => $user2->id,
            'first_name' => 'Mihajlo',
            'last_name' => 'Pupin',
            'slug' => 'mihajlo-pupin',
            'birth_date' => '1854-10-09',
            'death_date' => '1935-03-12',
            'birth_place' => 'Idvor, Austro-Ugarska',
            'death_place' => 'New York, SAD',
            'biography' => 'Mihajlo Idvorski Pupin bio je srpski i američki naučnik, fizičar i fizikalni hemičar. Poznat je po svojim pronalascima u oblasti telefonije i rendgenskog zračenja. Dobitnik je Pulicerove nagrade za autobiografiju "Od pašnjaka do naučenjaka".',
            'is_public' => true,
        ]);

        Tribute::create([
            'memorial_id' => $memorial3->id,
            'author_name' => 'Stefan Đorđević',
            'author_email' => 'stefan@example.com',
            'message' => 'Ponos srpskog naroda. Tvoj put od pastira do profesora na Kolumbiji inspiracija je svima.',
        ]);

        // Memorial 4 - Desanka Maksimović
        $memorial4 = Memorial::create([
            'user_id' => $user2->id,
            'first_name' => 'Desanka',
            'last_name' => 'Maksimović',
            'slug' => 'desanka-maksimovic',
            'birth_date' => '1898-05-16',
            'death_date' => '1993-02-11',
            'birth_place' => 'Rabrovica, Srbija',
            'death_place' => 'Beograd, Srbija',
            'biography' => 'Desanka Maksimović bila je srpska pjesnikinja i profesorka. Jedna od najznačajnijih figura srpske književnosti 20. vijeka. Najpoznatija po pjesmi "Krvava bajka" koja govori o stradanju djece u Drugom svjetskom ratu.',
            'is_public' => true,
        ]);

        Tribute::create([
            'memorial_id' => $memorial4->id,
            'author_name' => 'Milica Stojanović',
            'author_email' => 'milica@example.com',
            'message' => 'Tvoje pjesme su dio naše duše. Hvala ti za sve što si nam ostavila.',
        ]);

        Tribute::create([
            'memorial_id' => $memorial4->id,
            'author_name' => 'Petar Ilić',
            'author_email' => 'petar@example.com',
            'message' => 'Krvava bajka je najdirljivija pjesma koju sam ikada pročitao. Počivaj u miru.',
        ]);

        // Memorial 5 - Mileva Marić
        $memorial5 = Memorial::create([
            'user_id' => $user1->id,
            'first_name' => 'Mileva',
            'last_name' => 'Marić',
            'slug' => 'mileva-maric',
            'birth_date' => '1875-12-19',
            'death_date' => '1948-08-04',
            'birth_place' => 'Titel, Austro-Ugarska',
            'death_place' => 'Zürich, Švicarska',
            'biography' => 'Mileva Marić bila je srpska matematičarka i fizičarka, prva supruga Alberta Einsteina. Studirala je na Politehničkoj školi u Zürichu, gdje je bila jedna od rijetkih žena koje su studirale fiziku i matematiku u to vrijeme.',
            'is_public' => true,
        ]);

        Tribute::create([
            'memorial_id' => $memorial5->id,
            'author_name' => 'Jovana Pavlović',
            'author_email' => 'jovana@example.com',
            'message' => 'Pionirka nauke i inspiracija za sve žene u STEM poljima.',
        ]);

        // Memorial 6 - Vuk Karadžić
        $memorial6 = Memorial::create([
            'user_id' => $user2->id,
            'first_name' => 'Vuk',
            'last_name' => 'Karadžić',
            'slug' => 'vuk-karadzic',
            'birth_date' => '1787-11-06',
            'death_date' => '1864-02-07',
            'birth_place' => 'Tršić, Srbija',
            'death_place' => 'Beč, Austro-Ugarska',
            'biography' => 'Vuk Stefanović Karadžić bio je srpski filolog, reformator srpskog jezika i sakupljač narodnih umotvorina. Reformisao je srpski jezik i pravopis, sakupio i objavio srpske narodne pjesme i priče. Njegov rad je imao ogroman uticaj na razvoj srpske kulture.',
            'is_public' => true,
        ]);

        Tribute::create([
            'memorial_id' => $memorial6->id,
            'author_name' => 'Nemanja Kostić',
            'author_email' => 'nemanja@example.com',
            'message' => 'Otac srpskog jezika. Tvoj doprinos kulturi je nemjerljiv.',
        ]);

        Tribute::create([
            'memorial_id' => $memorial6->id,
            'author_name' => 'Dragana Simić',
            'author_email' => 'dragana@example.com',
            'message' => 'Hvala ti što si sačuvao našu tradiciju i jezik za buduće generacije.',
        ]);

        Tribute::create([
            'memorial_id' => $memorial6->id,
            'author_name' => 'Milan Todorović',
            'author_email' => 'milan@example.com',
            'message' => 'Piši kao što govoriš - tvoja poruka je vječna.',
        ]);

        $this->command->info('Created 6 memorials with tributes!');
        $this->command->info('Test users:');
        $this->command->info('  - test@example.com / password (user)');
        $this->command->info('  - admin@example.com / password (admin)');
    }
}
