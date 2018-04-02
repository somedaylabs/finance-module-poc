<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBillingLineItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('billing_line_items', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('billing_id');
            $table->integer('line_number');
            $table->string('description');
            $table->integer('amount');
            $table->timestamps();

            $table->index('billing_id');
            $table->unique(['billing_id', 'line_number']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('billing_line_items');
    }
}
