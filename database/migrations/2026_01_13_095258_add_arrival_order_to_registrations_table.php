<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->integer('arrival_order')->nullable()->after('attended_at');
        });

        // Backfill existing data
        $events = \Illuminate\Support\Facades\DB::table('events')->pluck('id');
        foreach ($events as $eventId) {
            $registrations = \Illuminate\Support\Facades\DB::table('registrations')
                ->where('event_id', $eventId)
                ->where('is_attended', true)
                ->whereNotNull('attended_at')
                ->orderBy('attended_at', 'asc')
                ->get();

            $order = 1;
            foreach ($registrations as $reg) {
                \Illuminate\Support\Facades\DB::table('registrations')
                    ->where('id', $reg->id)
                    ->update(['arrival_order' => $order++]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropColumn('arrival_order');
        });
    }
};
