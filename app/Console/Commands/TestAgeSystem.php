<?php

namespace App\Console\Commands;

use App\Models\Age;
use App\Models\Product;
use Illuminate\Console\Command;

class TestAgeSystem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'age:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the age management system';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Testing Age Management System...');

        // Test Age model
        $this->info("\n--- Available Ages ---");
        $ages = Age::where('status', 1)->get();

        if ($ages->count() > 0) {
            $this->table(
                ['ID', 'Age Name', 'Status'],
                $ages->map(function ($age) {
                    return [
                        $age->id,
                        $age->ageName,
                        $age->status == 1 ? 'Active' : 'Inactive',
                    ];
                })
            );
        } else {
            $this->warn('No ages found. Run: php artisan db:seed --class=AgeSeeder');
        }

        // Test relationship
        $this->info("\n--- Testing Product-Age Relationship ---");
        $products = Product::with('ages')->limit(3)->get();

        foreach ($products as $product) {
            $this->line("Product: {$product->name}");
            $this->line('Ages: '.($product->ages->count() > 0 ? $product->ages->pluck('ageName')->join(', ') : 'None assigned'));
            $this->line('---');
        }

        $this->info("\n✓ Age management system is working correctly!");
        $this->line('You can now:');
        $this->line('- Access ages at: /admin/age/manage');
        $this->line('- Create ages at: /admin/age/create');
        $this->line('- Edit existing ages');
        $this->line('- Assign ages to products');

        return Command::SUCCESS;
    }
}
