<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

class PopulateProductSkus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:populate-skus';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populate SKU for existing products that do not have one';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $products = Product::whereNull('sku')->get();

        $this->info('Found '.$products->count().' products without SKU');

        foreach ($products as $product) {
            $sku = 'SKU-'.$product->id;
            $product->update(['sku' => $sku]);
            $this->line('Updated product '.$product->id.' with SKU: '.$sku);
        }

        $this->info('SKU population completed');

        return Command::SUCCESS;
    }
}
