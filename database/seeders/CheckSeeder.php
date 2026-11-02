<?php

namespace Database\Seeders;

use App\Models\Check;
use Illuminate\Database\Seeder;

class CheckSeeder extends Seeder
{
    public function run(): void
    {
        $schema = config('checks');

        foreach ($schema as $name => $definition) {
            $slug = strtolower(trim(preg_replace('/[^\w]+/', '_', $name)));

            Check::updateOrCreate(
                ['slug' => $slug],
                [
                    'name'   => $name,
                    'slug'   => $slug,
                    'schema' => $definition,
                    'active' => true,
                ]
            );
        }
    }
}
