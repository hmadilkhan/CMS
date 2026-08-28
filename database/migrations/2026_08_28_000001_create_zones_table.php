<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The Zones module's own lanes. They run alongside the department pipeline and
 * never touch it: a project's zone says where the funding side has it, a
 * project's department says where the operations side has it.
 *
 * `show_in_list` marks a lane the Zones board draws. Archived is the one lane
 * kept out of the board - it stays reachable as a move destination and is read
 * back through its own screen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zones', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->unsignedInteger('order')->default(0);
            $table->boolean('show_in_list')->default(true);
            $table->timestamps();
        });

        $now = now();

        DB::table('zones')->insert([
            ['slug' => 'pre_ntp', 'name' => 'Pre NTP', 'order' => 1, 'show_in_list' => true, 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'ntp', 'name' => 'NTP', 'order' => 2, 'show_in_list' => true, 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'm1', 'name' => 'M1', 'order' => 3, 'show_in_list' => true, 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'm2', 'name' => 'M2', 'order' => 4, 'show_in_list' => true, 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'archived', 'name' => 'Archived', 'order' => 5, 'show_in_list' => false, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('zones');
    }
};
