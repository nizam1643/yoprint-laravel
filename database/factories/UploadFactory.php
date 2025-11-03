<?php

namespace Database\Factories;

use App\Models\Upload;
use Illuminate\Database\Eloquent\Factories\Factory;

class UploadFactory extends Factory
{
    protected $model = Upload::class;

    public function definition(): array
    {
        return [
            'original_name' => $this->faker->word().'.csv',
            'disk' => 'public',
            'path' => 'uploads/'.$this->faker->uuid().'.csv',
            'size' => $this->faker->numberBetween(100, 10000),
            'checksum_sha256' => hash('sha256', $this->faker->uuid()),
            'status' => 'queued',
        ];
    }
}
