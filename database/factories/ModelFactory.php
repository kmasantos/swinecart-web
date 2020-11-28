<?php
use Carbon\Carbon;

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

$factory->define(App\Models\User::class, function (Faker\Generator $faker) {
    return [
        'name' => $faker->name,
        'email' => $faker->email,
        'password' => bcrypt('secret12'),
        'remember_token' => str_random(10),
    ];
});

$factory->define(App\Models\Admin::class, function (Faker\Generator $faker){
    return [];
});

$factory->define(App\Models\Customer::class, function (Faker\Generator $faker) {
    $provinces = [
        // Negros Island Rregion
        'Negros Occidental',
        'Negros Oriental',
        // Cordillera Administrative Region
        'Mountain Province',
        'Ifugao',
        'Benguet',
        'Abra',
        'Apayao',
        'Kalinga',
        // Region I
        'La Union',
        'Ilocos Norte',
        'Ilocos Sur',
        'Pangasinan',
        // Region II
        'Nueva Vizcaya',
        'Cagayan',
        'Isabela',
        'Quirino',
        'Batanes',
        // Region III
        'Bataan',
        'Zambales',
        'Tarlac',
        'Pampanga',
        'Bulacan',
        'Nueva Ecija',
        'Aurora',
        // Region IV-A
        'Rizal',
        'Cavite',
        'Laguna',
        'Batangas',
        'Quezon',
        // Region IV-B
        'Occidental Mindoro',
        'Oriental Mindoro',
        'Romblon',
        'Palawan',
        'Marinduque',
        // Region V
        'Catanduanes',
        'Camarines Norte',
        'Sorsogon',
        'Albay',
        'Masbate',
        'Camarines Sur',
        // Region VI
        'Capiz',
        'Aklan',
        'Antique',
        'Iloilo',
        'Guimaras',
        // Region VII
        'Cebu',
        'Bohol',
        'Siquijor',
        // Region VIII
        'Southern Leyte',
        'Eastern Samar',
        'Northern Samar',
        'Western Samar',
        'Leyte',
        'Biliran',
        // Region IX
        'Zamboanga Sibugay',
        'Zamboanga del Norte',
        'Zamboanga del Sur',
        // Region X
        'Misamis Occidental',
        'Bukidnon',
        'Lanao del Norte',
        'Misamis Oriental',
        'Camiguin',
        // Region XI
        'Davao Oriental',
        'Compostela Valley',
        'Davao del Sur',
        'Davao Occidental',
        'Davao del Norte',
        // Region XII
        'South Cotabato',
        'Sultan Kudarat',
        'North Cotabato',
        'Sarangani',
        // Region XIII
        'Agusan del Norte',
        'Agusan del Sur',
        'Surigao del Sur',
        'Surigao del Norte',
        'Dinagat Islands',
        // ARMM
        'Tawi-tawi',
        'Basilan',
        'Sulu',
        'Maguindanao',
        'Lanao del Sur',
        // NCR
        'Caloocan City',
        'Las Piñas',
        'Makati',
        'Malabon',
        'Mandaluyong',
        'Manila',
        'Marikina',
        'Muntinlupa',
        'Navotas',
        'Parañaque',
        'Pasay City',
        'Pasig',
        'Pateros',
        'Quezon City',
        'San Juan',
        'Taguig',
        'Valenzuela'
    ];

    // Generate random integer for choosing province
    $rand = random_int(0,sizeof($provinces)-1);

    return [
        'address_addressLine1' => $faker->word,
        'address_addressLine2' => $faker->address,
        'address_province' => $provinces[$rand],
        'address_zipCode' => $faker->regexify('[0-9]{4}'),
        'landline' => $faker->regexify('[1-9]{8}'),
        // 'mobile' => $faker->regexify('09[0-9]{9}'),
        'mobile' => '09776749666'
    ];
});

$factory->define(App\Models\Breeder::class, function (Faker\Generator $faker) {
    $provinces = [
        // Negros Island Rregion
        'Negros Occidental',
        'Negros Oriental',
        // Cordillera Administrative Region
        'Mountain Province',
        'Ifugao',
        'Benguet',
        'Abra',
        'Apayao',
        'Kalinga',
        // Region I
        'La Union',
        'Ilocos Norte',
        'Ilocos Sur',
        'Pangasinan',
        // Region II
        'Nueva Vizcaya',
        'Cagayan',
        'Isabela',
        'Quirino',
        'Batanes',
        // Region III
        'Bataan',
        'Zambales',
        'Tarlac',
        'Pampanga',
        'Bulacan',
        'Nueva Ecija',
        'Aurora',
        // Region IV-A
        'Rizal',
        'Cavite',
        'Laguna',
        'Batangas',
        'Quezon',
        // Region IV-B
        'Occidental Mindoro',
        'Oriental Mindoro',
        'Romblon',
        'Palawan',
        'Marinduque',
        // Region V
        'Catanduanes',
        'Camarines Norte',
        'Sorsogon',
        'Albay',
        'Masbate',
        'Camarines Sur',
        // Region VI
        'Capiz',
        'Aklan',
        'Antique',
        'Iloilo',
        'Guimaras',
        // Region VII
        'Cebu',
        'Bohol',
        'Siquijor',
        // Region VIII
        'Southern Leyte',
        'Eastern Samar',
        'Northern Samar',
        'Western Samar',
        'Leyte',
        'Biliran',
        // Region IX
        'Zamboanga Sibugay',
        'Zamboanga del Norte',
        'Zamboanga del Sur',
        // Region X
        'Misamis Occidental',
        'Bukidnon',
        'Lanao del Norte',
        'Misamis Oriental',
        'Camiguin',
        // Region XI
        'Davao Oriental',
        'Compostela Valley',
        'Davao del Sur',
        'Davao Occidental',
        'Davao del Norte',
        // Region XII
        'South Cotabato',
        'Sultan Kudarat',
        'North Cotabato',
        'Sarangani',
        // Region XIII
        'Agusan del Norte',
        'Agusan del Sur',
        'Surigao del Sur',
        'Surigao del Norte',
        'Dinagat Islands',
        // ARMM
        'Tawi-tawi',
        'Basilan',
        'Sulu',
        'Maguindanao',
        'Lanao del Sur',
        // NCR
        'Caloocan City',
        'Las Piñas',
        'Makati',
        'Malabon',
        'Mandaluyong',
        'Manila',
        'Marikina',
        'Muntinlupa',
        'Navotas',
        'Parañaque',
        'Pasay City',
        'Pasig',
        'Pateros',
        'Quezon City',
        'San Juan',
        'Taguig',
        'Valenzuela'
    ];

    // Generate random integer for choosing province
    $rand = random_int(0,sizeof($provinces)-1);

    return [
        'officeAddress_addressLine1' => $faker->streetAddress,
        'officeAddress_addressLine2' => $faker->streetAddress,
        'officeAddress_province' => $provinces[$rand],
        'officeAddress_zipCode' => $faker->regexify('[0-9]{4}'),
        'office_landline' => $faker->regexify('[1-9]{8}'),
        // 'office_mobile' => $faker->regexify('09[0-9]{9}'),
        'office_mobile' => '09776749666',
        'website' => $faker->word.'.com',
        'produce' => $faker->word,
        'breeder_handle' => str_random(7),
        'contactPerson_name' => $faker->name,
        'contactPerson_mobile' => $faker->regexify('09[0-9]{9}')
    ];
});

$factory->define(App\Models\FarmAddress::class, function (Faker\Generator $faker) {

    $provinces = [
        // Negros Island Rregion
        'Negros Occidental',
        'Negros Oriental',
        // Cordillera Administrative Region
        'Mountain Province',
        'Ifugao',
        'Benguet',
        'Abra',
        'Apayao',
        'Kalinga',
        // Region I
        'La Union',
        'Ilocos Norte',
        'Ilocos Sur',
        'Pangasinan',
        // Region II
        'Nueva Vizcaya',
        'Cagayan',
        'Isabela',
        'Quirino',
        'Batanes',
        // Region III
        'Bataan',
        'Zambales',
        'Tarlac',
        'Pampanga',
        'Bulacan',
        'Nueva Ecija',
        'Aurora',
        // Region IV-A
        'Rizal',
        'Cavite',
        'Laguna',
        'Batangas',
        'Quezon',
        // Region IV-B
        'Occidental Mindoro',
        'Oriental Mindoro',
        'Romblon',
        'Palawan',
        'Marinduque',
        // Region V
        'Catanduanes',
        'Camarines Norte',
        'Sorsogon',
        'Albay',
        'Masbate',
        'Camarines Sur',
        // Region VI
        'Capiz',
        'Aklan',
        'Antique',
        'Iloilo',
        'Guimaras',
        // Region VII
        'Cebu',
        'Bohol',
        'Siquijor',
        // Region VIII
        'Southern Leyte',
        'Eastern Samar',
        'Northern Samar',
        'Western Samar',
        'Leyte',
        'Biliran',
        // Region IX
        'Zamboanga Sibugay',
        'Zamboanga del Norte',
        'Zamboanga del Sur',
        // Region X
        'Misamis Occidental',
        'Bukidnon',
        'Lanao del Norte',
        'Misamis Oriental',
        'Camiguin',
        // Region XI
        'Davao Oriental',
        'Compostela Valley',
        'Davao del Sur',
        'Davao Occidental',
        'Davao del Norte',
        // Region XII
        'South Cotabato',
        'Sultan Kudarat',
        'North Cotabato',
        'Sarangani',
        // Region XIII
        'Agusan del Norte',
        'Agusan del Sur',
        'Surigao del Sur',
        'Surigao del Norte',
        'Dinagat Islands',
        // ARMM
        'Tawi-tawi',
        'Basilan',
        'Sulu',
        'Maguindanao',
        'Lanao del Sur'
    ];

    // Generate random integer for choosing province
    $rand = random_int(0,sizeof($provinces)-1);

    return [
        'name' => $faker->word,
        'addressLine1' => $faker->word,
        'addressLine2' => $faker->address,
        'province' => $provinces[$rand],
        'zipCode' => $faker->regexify('[0-9]{4}'),
        'farmType' => $faker->word,
        'landline' => $faker->regexify('[0-9]{8}'),
        'mobile' => $faker->regexify('09[0-9]{9}'),
        'accreditation_no' => null,
        'accreditation_status' => 'not_applicable',
        'accreditation_date' => null,
        'accreditation_expiry' => null
    ];
});

$factory->define(App\Models\Spectator::class, function (Faker\Generator $faker){
    return [];
});
