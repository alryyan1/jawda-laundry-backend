<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProductType;
use App\Models\ServiceAction;
use App\Models\ServiceOffering;

class SekkaShawarmaServiceOfferingSeeder extends Seeder
{
	/**
	 * Run the database seeds to create Sekka Shawarma service offerings.
	 * All offerings use the "Normal" service action (id = 4).
	 */
	public function run(): void
	{
		$this->command->info('Seeding Sekka Shawarma service offerings (Normal action id=4)...');


		$normalServiceAction = ServiceAction::create([
			'name' => 'Normal',
			'description' => 'Normal service action',
			'base_duration_minutes' => 10,
		]);
		$normalServiceActionId = $normalServiceAction->id; // Using Medium as "Normal" service action

		// Mapping of product type name => default price (OMR)
		$prices = [
			// بوكسات (Boxes)
			'بوكس سكة لحم' => 1.800,
			'بوكس سكة دجاج' => 1.600,
			'عراقي بوكس' => 2.800,

			// سندويشات (Sandwiches)
			'تكة' => 0.800,
			'كبدة' => 0.600,
			'كباب' => 0.900,
			'فلافل' => 0.500,

			// فرايز (Fries)
			'فرايز لحم' => 1.500,
			'فرايز دجاج' => 1.400,

			// عراقي (Iraqi Style)
			'عراقي دجاج' => 0.600,
			'عراقي لحم' => 0.700,

			// صاروق (Sarouq)
			'صاروق دجاج' => 0.900,
			'صاروق لحم' => 1.000,

			// شبس (Chips)
			'شبس سكة الخاص' => 0.800,

			// مشروبات (Drinks)
			'لبن بالنعناع' => 0.400,
			'ليمون نعاع' => 0.700,
			'ليمون فراولة' => 1.000,
			'مانجو باشن' => 1.200,
			'كينزا' => 0.300,

			// وجبات التوفير (Value Meals)
			'فلافل (وجبة توفير)' => 1.100,
			'كبدة (وجبة توفير)' => 1.200,
			'تكة (وجبة توفير)' => 1.400,
			'كباب (وجبة توفير)' => 1.500,
			'عراقي دجاج (وجبة توفير)' => 1.200,
			'عراقي لحم (وجبة توفير)' => 1.300,
		];

		$created = 0;
		$missingTypes = [];

		foreach ($prices as $typeName => $price) {
			$type = ProductType::where('name', $typeName)->first();
			if (!$type) {
				$missingTypes[] = $typeName;
				continue;
			}

			$offering = ServiceOffering::updateOrCreate(
				[
					'product_type_id' => $type->id,
					'service_action_id' => $normalServiceActionId,
				],
				[
					'name_override' => null,
					'description_override' => null,
					'default_price' => $price,
					'default_price_per_sq_meter' => null,
					'applicable_unit' => 'piece',
					'is_active' => true,
				]
			);

			if ($offering->wasRecentlyCreated) {
				$created++;
			}
		}

		$this->command->info("Sekka Shawarma offerings seeded/updated. Created {$created} offerings.");
		if (!empty($missingTypes)) {
			$this->command->warn('Missing product types (ensure SekkaShawarmaMenuSeeder ran first): ' . implode(', ', $missingTypes));
		}
	}
}


