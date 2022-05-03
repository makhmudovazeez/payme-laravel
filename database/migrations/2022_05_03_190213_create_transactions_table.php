<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payme_transactions', function (Blueprint $table) {
            $table->id();

            $table->string('paycom_transaction_id');
            $table->string('paycom_time');
            $table->dateTime('paycom_time_datetime');
            $table->dateTime('create_time');
            $table->dateTime('perform_time')->nullable();
            $table->dateTime('cancel_time')->nullable();
            $table->integer('amount');
            $table->tinyInteger('state');
            $table->tinyInteger('reason')->nullable();
            $table->string('receivers')->nullable();
            $table->foreignId('order_id')->constrained('orders');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transactions');
    }
}
