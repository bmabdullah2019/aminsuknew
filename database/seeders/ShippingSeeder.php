<?php

namespace Database\Seeders;

use App\Models\ShippingCharge;
use App\Models\ShippingProfile;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\ShippingZoneArea;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ShippingSeeder extends Seeder
{
    public function run(): void
    {
        $profiles = collect([
            ['name' => 'Regular', 'description' => 'Standard parcel shipping', 'is_default' => true],
            ['name' => 'Heavy', 'description' => 'Heavy and bulky products', 'is_default' => false],
            ['name' => 'Fragile', 'description' => 'Products needing careful handling', 'is_default' => false],
            ['name' => 'Frozen', 'description' => 'Temperature-sensitive products', 'is_default' => false],
            ['name' => 'Digital', 'description' => 'Digital products with no shipping', 'is_default' => false],
            ['name' => 'Free Shipping', 'description' => 'Promotional free shipping profile', 'is_default' => false],
        ])->mapWithKeys(function (array $profile) {
            $model = ShippingProfile::updateOrCreate(
                ['slug' => Str::slug($profile['name'])],
                [
                    'name' => $profile['name'],
                    'description' => $profile['description'],
                    'is_default' => $profile['is_default'],
                    'status' => 1,
                ]
            );

            return [$model->slug => $model];
        });

        ShippingProfile::where('slug', '!=', 'regular')->update(['is_default' => false]);

        $zones = collect(['Dhaka City', 'Outside Dhaka', 'Chittagong'])->mapWithKeys(function (string $name) {
            $zone = ShippingZone::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'status' => 1]
            );

            return [$zone->slug => $zone];
        });

        $this->seedRates($zones, $profiles);
        $this->linkLegacyAreas($zones);
        $this->seedPermissions();
    }

    private function seedRates($zones, $profiles): void
    {
        $rows = [
            ['dhaka-city', 'regular', 0, 1, 60],
            ['dhaka-city', 'regular', 1, 5, 100],
            ['dhaka-city', 'heavy', 0, 5, 150],
            ['dhaka-city', 'fragile', 0, 3, 120],
            ['outside-dhaka', 'regular', 0, 1, 120],
            ['outside-dhaka', 'regular', 1, 5, 180],
            ['outside-dhaka', 'heavy', 0, 5, 250],
            ['chittagong', 'regular', 0, 1, 100],
            ['chittagong', 'regular', 1, 5, 160],
        ];

        foreach ($rows as [$zoneSlug, $profileSlug, $minWeight, $maxWeight, $rate]) {
            $zone = $zones->get($zoneSlug);
            $profile = $profiles->get($profileSlug);

            if (! $zone || ! $profile) {
                continue;
            }

            ShippingRate::updateOrCreate(
                [
                    'shipping_zone_id' => $zone->id,
                    'shipping_profile_id' => $profile->id,
                    'min_weight' => $minWeight,
                    'max_weight' => $maxWeight,
                ],
                [
                    'rate' => $rate,
                    'currency' => 'BDT',
                    'status' => 1,
                ]
            );
        }
    }

    private function linkLegacyAreas($zones): void
    {
        ShippingCharge::query()
            ->where('status', 1)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->each(function (ShippingCharge $charge) use ($zones) {
                $name = Str::lower($charge->name);
                $zone = $zones->get('outside-dhaka');

                if (Str::contains($name, 'dhaka')) {
                    $zone = $zones->get('dhaka-city');
                }

                if (Str::contains($name, ['chittagong', 'chattogram'])) {
                    $zone = $zones->get('chittagong');
                }

                if (! $zone) {
                    return;
                }

                ShippingZoneArea::updateOrCreate(
                    [
                        'shipping_zone_id' => $zone->id,
                        'area_name' => $charge->name,
                    ],
                    ['shipping_charge_id' => $charge->id]
                );
            });
    }

    private function seedPermissions(): void
    {
        if (! class_exists(\Spatie\Permission\Models\Permission::class)) {
            return;
        }

        $permissions = [
            'shipping-profile-list',
            'shipping-profile-create',
            'shipping-profile-edit',
            'shipping-profile-delete',
            'shipping-zone-list',
            'shipping-zone-create',
            'shipping-zone-edit',
            'shipping-zone-delete',
            'shipping-rate-list',
            'shipping-rate-create',
            'shipping-rate-edit',
            'shipping-rate-delete',
        ];

        foreach ($permissions as $permission) {
            $this->firstOrCreatePermission($permission);
        }

        if (! class_exists(\Spatie\Permission\Models\Role::class)) {
            return;
        }

        \Spatie\Permission\Models\Role::query()
            ->whereIn('name', ['Admin', 'admin', 'Super Admin', 'super-admin'])
            ->get()
            ->each(function ($role) use ($permissions) {
                $role->givePermissionTo($permissions);
            });
    }

    private function firstOrCreatePermission(string $name): void
    {
        $permissionClass = \Spatie\Permission\Models\Permission::class;

        if ($permissionClass::query()->where('name', $name)->where('guard_name', 'web')->exists()) {
            return;
        }

        $nextId = ((int) DB::table('permissions')->max('id')) + 1;

        $permissionClass::create([
            'id' => $nextId,
            'name' => $name,
            'guard_name' => 'web',
        ]);
    }
}
