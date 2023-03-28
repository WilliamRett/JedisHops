<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Pantient>
 */
class PantientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $faker = Faker\Factory::create('pt_BR');

        return [
            'name' => $faker->name(),
            'mon' => $faker->name(),
            'birthday' => $faker->date('Y_m_d'),
            'cpf' => $faker->cpf(),
            'cns' => $faker->randomNumber(15, true), // password
            'address_id' => 1,
        ];
    }
}
