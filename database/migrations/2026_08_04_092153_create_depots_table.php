<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('depots', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        $depots = [
            'Aspindale Depot', 'Banket', 'Bazeley Bridge', 'Beitbridge', 'Bindura',
            'Buhera', 'Bulawayo', 'Centenary', 'Charandura', 'Chegutu', 'Chibi',
            'Chiendambuya', 'Chimanimani', 'Chinhoyi', 'Chinyudze', 'Chipinge',
            'Chiredzi', 'Chitungwiza', 'Chivhu', 'Chiweshe', 'Cleveland', 'Doma',
            'Concession', 'Dewedzo', 'Esigodini', 'Filabusi', 'Glendale', 'Gokwe',
            'Guruve', 'Gutu', 'Gwanda', 'GMB - Lodge', 'Gweru', 'Hwange', 'Jerera',
            'Kachuta', 'Kadoma', 'Kamutsenzere', 'Kariba', 'Karoi', 'Kotwa', 'Kwekwe',
            'Lions Den', 'Lupane', 'Lusulu', 'Magunje', 'Mamina', 'Manoti', 'Maphisa',
            'Marondera', 'Masvingo', 'Mataga', 'Mhangura', 'Mhondoro', 'Middle Sabi',
            'Mt Darwin', 'Mudzimu', 'Mukwichi', 'Murewa', 'Murombedzi', 'Mushumbi',
            'Mutare Grain', 'Mutasa', 'Mutawatawa', 'Mutoko', 'Mvuma', 'Mvurwi',
            'Nembudziya', 'Norton', 'Nkayi', 'Nyanga', 'Nyika', 'Plumtree',
            'Raffingora', 'Rusape', 'Rushinga', 'Rutenga', 'Sadza', 'Sanyati',
            'Shamva', 'Timber Mills', 'Tongogara', 'Tsholotsho', 'Vuti', 'Wedza',
            'Zhombe', 'Zvishavane',
        ];

        $now = now();
        DB::table('depots')->insert(
            array_map(fn($name) => ['name' => $name, 'created_at' => $now, 'updated_at' => $now], $depots)
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('depots');
    }
};
