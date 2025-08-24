# Product Type Compositions - مكونات المنتجات

## نظرة عامة

تم إضافة ميزة إدارة مكونات المنتجات التي تسمح بتعريف المكونات المختلفة لكل نوع منتج. على سبيل المثال، يمكن تعريف "برجر بالجبن" كواحد من مكونات نوع منتج "برجر"، مع تحديد العناصر المطلوبة مثل اللحم والجبن والخبز.

## الجداول المضافة

### 1. `product_type_compositions`
- `id` - المعرف الفريد
- `product_type_id` - معرف نوع المنتج (مفتاح خارجي)
- `name` - اسم المكون (مثال: "برجر بالجبن")
- `description` - وصف المكون (اختياري)
- `is_active` - حالة التفعيل
- `created_at`, `updated_at` - تواريخ الإنشاء والتحديث

### 2. `composition_items`
- `id` - المعرف الفريد
- `composition_id` - معرف المكون (مفتاح خارجي)
- `item_name` - اسم العنصر (مثال: "برجر"، "جبن")
- `description` - وصف العنصر (اختياري)
- `quantity` - الكمية المطلوبة
- `unit` - وحدة القياس (مثال: "قطعة"، "جرام")
- `is_required` - هل العنصر مطلوب أم اختياري
- `sort_order` - ترتيب العناصر
- `created_at`, `updated_at` - تواريخ الإنشاء والتحديث

## API Endpoints

### الحصول على مكونات نوع منتج
```
GET /api/product-types/{productTypeId}/compositions
```

### الحصول على مكون محدد
```
GET /api/product-types/{productTypeId}/compositions/{compositionId}
```

### إنشاء مكون جديد
```
POST /api/product-types/{productTypeId}/compositions
```

**Body:**
```json
{
  "name": "برجر بالجبن",
  "description": "برجر مع جبن شيدر",
  "is_active": true,
  "items": [
    {
      "item_name": "برجر",
      "description": "لحم البقر",
      "quantity": 1,
      "unit": "قطعة",
      "is_required": true,
      "sort_order": 0
    },
    {
      "item_name": "جبن",
      "description": "جبن شيدر",
      "quantity": 1,
      "unit": "قطعة",
      "is_required": true,
      "sort_order": 1
    },
    {
      "item_name": "طماطم",
      "description": "شرائح طماطم",
      "quantity": 2,
      "unit": "قطعة",
      "is_required": false,
      "sort_order": 2
    }
  ]
}
```

### تحديث مكون
```
PUT /api/product-types/{productTypeId}/compositions/{compositionId}
```

### حذف مكون
```
DELETE /api/product-types/{productTypeId}/compositions/{compositionId}
```

### تبديل حالة المكون
```
PATCH /api/product-types/{productTypeId}/compositions/{compositionId}/toggle-status
```

## النماذج (Models)

### ProductTypeComposition
```php
class ProductTypeComposition extends Model
{
    protected $fillable = [
        'product_type_id',
        'name',
        'description',
        'is_active',
    ];

    public function productType()
    {
        return $this->belongsTo(ProductType::class);
    }

    public function items()
    {
        return $this->hasMany(CompositionItem::class, 'composition_id');
    }
}
```

### CompositionItem
```php
class CompositionItem extends Model
{
    protected $fillable = [
        'composition_id',
        'item_name',
        'description',
        'quantity',
        'unit',
        'is_required',
        'sort_order',
    ];

    public function composition()
    {
        return $this->belongsTo(ProductTypeComposition::class, 'composition_id');
    }
}
```

## الواجهة الأمامية

### الصفحة الرئيسية
- المسار: `/admin/product-types/{productTypeId}/compositions`
- الملف: `ProductTypeCompositionsPage.tsx`

### النموذج
- الملف: `CompositionFormModal.tsx`
- يدعم إضافة/تعديل المكونات مع عناصرها

### الخدمات
- الملف: `productTypeCompositionService.ts`
- يحتوي على جميع عمليات API

## الاستخدام

1. انتقل إلى صفحة أنواع المنتجات
2. اختر نوع المنتج المطلوب
3. انقر على "إدارة المكونات" من القائمة المنسدلة
4. أضف مكونات جديدة أو عدل المكونات الموجودة
5. لكل مكون، أضف العناصر المطلوبة والاختيارية

## مثال عملي

**نوع المنتج:** برجر

**المكونات:**
1. **برجر بالجبن**
   - برجر (1 قطعة) - مطلوب
   - جبن شيدر (1 قطعة) - مطلوب
   - خس (2 ورقة) - اختياري
   - طماطم (2 شرائح) - اختياري

2. **برجر دجاج**
   - صدر دجاج (1 قطعة) - مطلوب
   - خس (1 ورقة) - مطلوب
   - مايونيز (1 ملعقة) - اختياري

## الميزات

- ✅ إدارة مكونات متعددة لكل نوع منتج
- ✅ عناصر مطلوبة واختيارية
- ✅ وحدات قياس مختلفة
- ✅ ترتيب العناصر
- ✅ تفعيل/إلغاء تفعيل المكونات
- ✅ واجهة مستخدم سهلة الاستخدام
- ✅ دعم اللغتين العربية والإنجليزية
- ✅ تحقق من صحة البيانات
- ✅ رسائل خطأ واضحة
