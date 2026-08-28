<?php

namespace Database\Factories;

use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'body' => $this->faker->sentence(),
        ];
    }

    public function withFile(string $fileName = 'document.txt'): static
    {
        return $this->state(fn (): array => [
            'body' => null,
            'file_path' => 'uploads/'.$this->faker->uuid().'.'.pathinfo($fileName, PATHINFO_EXTENSION),
            'file_name' => $fileName,
            'file_mime' => 'text/plain',
        ]);
    }
}
