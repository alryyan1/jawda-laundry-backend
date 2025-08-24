# Product Compositions Management Guide

## Overview
Product compositions are the basic ingredients or components that can be used in product types. This system allows you to manage these compositions and assign them to different product types.

## Database Structure

### Tables
1. **`product_compositions`** - Stores the basic compositions/ingredients
   - `id` (Primary Key)
   - `name` (String) - The name of the composition
   - `created_at` (Timestamp)
   - `updated_at` (Timestamp)

2. **`product_type_compositions`** - Junction table linking product types to compositions
   - `id` (Primary Key)
   - `product_type_id` (Foreign Key to product_types)
   - `product_composition_id` (Foreign Key to product_compositions)
   - `description` (Text, nullable)
   - `is_active` (Boolean)
   - `created_at` (Timestamp)
   - `updated_at` (Timestamp)

## API Endpoints

### Product Compositions
- `GET /api/product-compositions` - Get all compositions
- `POST /api/product-compositions` - Create new composition
- `GET /api/product-compositions/{id}` - Get specific composition
- `PUT /api/product-compositions/{id}` - Update composition
- `DELETE /api/product-compositions/{id}` - Delete composition

### Product Type Compositions
- `GET /api/product-types/{id}/compositions` - Get compositions for a product type
- `POST /api/product-types/{id}/compositions` - Assign composition to product type
- `DELETE /api/product-types/{id}/compositions/{composition_id}` - Remove composition from product type

## Frontend Components

### 1. AddCompositionDialog
A reusable dialog component for adding new compositions:
```tsx
<AddCompositionDialog
  isOpen={isOpen}
  onOpenChange={setIsOpen}
  onCompositionAdded={(composition) => {
    // Handle newly added composition
  }}
/>
```

### 2. ManageCompositionsDialog
A dialog for managing which compositions are assigned to a product type:
```tsx
<ManageCompositionsDialog
  isOpen={isOpen}
  onOpenChange={setIsOpen}
  productType={productType}
/>
```

### 3. ProductCompositionsListPage
A full page for managing all product compositions with CRUD operations.

## Usage Examples

### Adding a New Composition
1. **Via API:**
```bash
curl -X POST http://localhost:8000/api/product-compositions \
  -H "Content-Type: application/json" \
  -d '{"name": "New Ingredient"}'
```

2. **Via Frontend:**
   - Navigate to the Product Compositions page
   - Click "Add Composition"
   - Enter the composition name
   - Click "Create"

### Assigning Compositions to Product Types
1. **Via API:**
```bash
curl -X POST http://localhost:8000/api/product-types/1/compositions \
  -H "Content-Type: application/json" \
  -d '{"product_composition_id": 1, "description": "Optional description", "is_active": true}'
```

2. **Via Frontend:**
   - Go to Product Types management
   - Click "Manage Compositions" for a specific product type
   - Check/uncheck compositions to assign/unassign them
   - Click "Save"

## Seeded Data
The system comes with 81 pre-seeded compositions organized into categories:

### Proteins (10 items)
- لحم بقري, دجاج, لحم غنم, سمك, جمبري, برجر, كباب, كبدة, تكة, فلافل

### Dairy (7 items)
- جبن, جبنة بيضاء, جبنة شيدر, جبنة موزاريلا, زبدة, كريمة, لبن

### Vegetables (11 items)
- طماطم, خس, بصل, خيار, جزر, فلفل, فلفل حار, زيتون, مخلل, بطاطس, بطاطس مقلية

### Sauces & Condiments (11 items)
- مايونيز, كاتشب, خردل, صلصة ثوم, صلصة حارة, صلصة تارتار, صلصة رانش, صلصة باربيكيو, صلصة سيزر, صلصة ألفريدو, صلصة بيستو

### Grains & Breads (8 items)
- أرز, خبز, خبز صاج, خبز برجر, خبز ساندويتش, خبز بيتا, خبز توست, معكرونة

### Salads & Sides (7 items)
- سلطة, سلطة خضراء, سلطة سيزر, سلطة يونانية, سلطة كول سلو, سلطة بطاطس, سلطة أرز

### Drinks & Beverages (11 items)
- ماء, عصير برتقال, عصير تفاح, عصير مانجو, عصير فراولة, ليمونادة, شاي, قهوة, كولا, سبرايت, فانتا

### Desserts (6 items)
- آيس كريم, كيك, بسكويت, كنافة, بقلاوة, حلاوة

### Spices & Herbs (10 items)
- ملح, فلفل أسود, كمون, كركم, زعتر, بقدونس, كزبرة, نعناع, ريحان, أوريغانو

## Best Practices

1. **Naming Conventions:**
   - Use descriptive names for compositions
   - Be consistent with naming patterns
   - Consider using both Arabic and English names if needed

2. **Organization:**
   - Group related compositions logically
   - Use the category system for better organization
   - Keep compositions reusable across different product types

3. **Data Integrity:**
   - Don't delete compositions that are currently assigned to product types
   - Use soft deletes if needed for historical data
   - Validate composition names for uniqueness

4. **Performance:**
   - The system uses React Query for efficient caching
   - Compositions are loaded once and cached
   - Use search functionality for large lists

## Troubleshooting

### Common Issues

1. **Composition not appearing in list:**
   - Check if the composition was created successfully
   - Verify the API response
   - Refresh the page or invalidate the query cache

2. **Cannot delete composition:**
   - Check if the composition is assigned to any product types
   - Remove all assignments before deletion
   - Check for foreign key constraints

3. **API errors:**
   - Verify the request format
   - Check authentication and permissions
   - Review server logs for detailed error messages

### Debugging
- Use browser developer tools to inspect API calls
- Check Laravel logs in `storage/logs/laravel.log`
- Use React Query DevTools for frontend debugging
