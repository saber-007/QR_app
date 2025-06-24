<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQrcodesTable extends Migration
{
    public function up()
    {
        Schema::create('qrcodes', function (Blueprint $table) {
            $table->id(); // Colonne auto-incrémentée pour l'identifiant
            $table->string('code')->unique();  // Code QR unique
            $table->boolean('is_fraud')->default(false); // Marque si c'est une fraude
            $table->integer('scan_count')->default(0);  // Compteur de scans
            $table->timestamp('last_scanned_at')->nullable();  // Dernier scan
            $table->boolean('sortie')->default(false);  // Statut de sortie
            $table->timestamp('date_sortie')->nullable();  // Date de sortie
            $table->timestamps(); // Colonnes created_at et updated_at
        });
    }

    public function down()
    {
        Schema::dropIfExists('qrcodes');
    }
}
