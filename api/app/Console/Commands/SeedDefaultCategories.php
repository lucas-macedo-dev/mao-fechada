<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Services\DefaultCategorySeeder;
use Illuminate\Console\Command;

class SeedDefaultCategories extends Command
{
    protected $signature = 'categories:seed-defaults
                            {--dry-run : Report how many users would be seeded without inserting rows}';

    protected $description = 'Seed default categories for all users who have none';

    public function handle(): void
    {
        $isDryRun = $this->option('dry-run');

        $toSeedCount = User::query()->whereDoesntHave('categories')->count();
        $skippedCount = User::query()->whereHas('categories')->count();

        if ($isDryRun) {
            $this->info("Dry run: {$toSeedCount} users would be seeded. {$skippedCount} users would be skipped.");

            return;
        }

        $seeded = 0;
        $seeder = new DefaultCategorySeeder;

        User::query()
            ->whereDoesntHave('categories')
            ->chunk(100, function ($users) use ($seeder, &$seeded) {
                foreach ($users as $user) {
                    $seeder->seedFor($user);
                    $seeded++;
                }
            });

        $this->info("Seeded {$seeded} users. Skipped {$skippedCount} users with existing categories.");
    }
}
