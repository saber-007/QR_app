<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateScansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('scans', function (Blueprint $table) {
    $table->id();
        $table->string('code');
        $table->string('produit');
        $table->integer('quantite');
        $table->string('chauffeur');
        $table->string('status');
        $table->timestamp('date_scan')->nullable();
        $table->timestamps();
$table->unsignedBigInteger('agent_id')->nullable();
$table->foreign('agent_id')->references('id')->on('users');
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('scans');
    }
}
