<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE room_types ENGINE=InnoDB');
        DB::statement('ALTER TABLE rooms ENGINE=InnoDB');
        DB::statement('ALTER TABLE guests ENGINE=InnoDB');
        DB::statement('ALTER TABLE bookings ENGINE=InnoDB');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE room_types ENGINE=MyISAM');
        DB::statement('ALTER TABLE rooms ENGINE=MyISAM');
        DB::statement('ALTER TABLE guests ENGINE=MyISAM');
        DB::statement('ALTER TABLE bookings ENGINE=MyISAM');
    }
};
