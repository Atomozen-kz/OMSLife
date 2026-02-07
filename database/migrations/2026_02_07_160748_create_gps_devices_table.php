<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('gps_devices', function (Blueprint $table) {
            $table->id();

            $table->string('device_id')->unique(); // IMEI

            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lon', 10, 7)->nullable();

            $table->unsignedSmallInteger('speed')->nullable();
            $table->unsignedSmallInteger('course')->nullable();
            $table->decimal('altitude', 10, 2)->nullable();
            $table->unsignedSmallInteger('sats')->nullable();

            $table->timestamp('device_time')->nullable();   // время трекера
            $table->timestamp('received_at')->nullable();   // время сервера

            $table->json('sensors')->nullable();            // сенсоры JSON
            $table->text('raw')->nullable();                // сырая строка
            $table->string('protocol')->nullable();         // wialon_ips

            $table->timestamps();
            $table->index('received_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gps_devices');
    }
};
