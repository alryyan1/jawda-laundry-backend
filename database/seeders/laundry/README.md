# Laundry Seeders

This folder contains seeders specifically for laundry business data.

## Files

### LaundryCategorySeeder.php
Creates the main laundry categories:
- **Laundry Clothes** - ملابس غسيل (Everyday wear with standard pricing per piece)
- **Laundry Special Items** - قطع غسيل خاصة (Delicate or expensive items requiring special care)
- **Laundry Household Items** - مستلزمات غسيل منزلية (Larger items charged by size or per piece)
- **Laundry Carpets & Rugs** - سجاد وبسط غسيل (Charged by area - length × width)
- **Laundry Accessories** - إكسسوارات غسيل (Small items with lower rates)

### LaundryProductTypeSeeder.php
Creates product types for each category:

#### Laundry Clothes Category
- Shirts - قمصان
- Trousers / Pants - بناطيل
- T-Shirts - تي شيرت
- Dresses - فساتين
- Skirts - تنانير
- Jackets / Coats - جواكت ومعاطف
- Abayas / Thobes - عباءات وثياب
- Suits - بدلات
- Underwear - ملابس داخلية
- Uniforms - زي رسمي
- Scarves - أوشحة

#### Laundry Special Items Category
- Wedding Dresses - فساتين زفاف
- Evening Gowns - فساتين سهرة
- Leather Jackets - جواكت جلد
- Silk Clothing - ملابس حرير
- Blazers - بليزر
- Traditional Attire - ملابس تقليدية

#### Laundry Household Items Category
- Bed Sheets - شراشف سرير
- Comforters / Duvets - لحاف وملاءات
- Pillow Covers - أغطية وسادات
- Curtains - ستائر
- Sofa Covers - أغطية أريكة
- Blankets - بطانيات
- Mattress Covers - أغطية فراش
- Tablecloths - مفارش طاولة
- Towels - مناشف

#### Laundry Carpets & Rugs Category (Dimension-based pricing)
- Small Rugs - سجاد صغير
- Medium Carpets - سجاد متوسط
- Large Area Carpets - سجاد كبير
- Prayer Rugs - سجاد صلاة

#### Laundry Accessories Category
- Socks - جوارب
- Gloves - قفازات
- Hats / Caps - قبعات
- Ties - ربطات عنق

### LaundrySeeder.php
Main seeder that runs both category and product type seeders in the correct order.

## Usage

### Run all laundry seeders:
```bash
php artisan db:seed --class="Database\Seeders\Laundry\LaundrySeeder"
```

### Run individual seeders:
```bash
# Categories only
php artisan db:seed --class="Database\Seeders\Laundry\LaundryCategorySeeder"

# Product types only (requires categories to exist first)
php artisan db:seed --class="Database\Seeders\Laundry\LaundryProductTypeSeeder"
```

### Run with main database seeder:
The laundry seeders are automatically included in the main `DatabaseSeeder.php`, so running:
```bash
php artisan db:seed
```
Will include the laundry data.

## Notes

- All product types in the "Carpets & Rugs" category are marked as `is_dimension_based = true` for area-based pricing
- All other product types are marked as `is_dimension_based = false` for piece-based pricing
- Categories and product types are created with both English and Arabic names
- The seeders use `firstOrCreate` to prevent duplicates if run multiple times 