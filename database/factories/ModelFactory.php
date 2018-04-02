<?php

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|
*/

$factory->define(App\User::class, function (Faker\Generator $faker) {
    return [
        'name' => $faker->name,
        'email' => $faker->email,
    ];
});
$factory->define(App\Customer::class, function (Faker\Generator $faker) {
    return [
        'name' => $faker->company,
        'email' => $faker->companyEmail
    ];
});
$factory->define(App\Billing::class, function (Faker\Generator $faker) {
    $total = $faker->numberBetween(100, 700000);
    return [
        "customer_id" => factory(App\Customer::class)->create(),
        "status" => "new",
        "billing_date" => $faker->dateTimeThisMonth,
        "total" => $total
    ];
});
