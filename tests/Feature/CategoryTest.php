<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Scopes\IsActiveScope;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CustomerSeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\ReviewSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use function PHPUnit\Framework\assertEquals;

class CategoryTest extends TestCase
{
    public function testInsert()
    {
        $category = new Category();
        $category->id = "GADGET";
        $category->name = "Gadget";
        $result = $category->save();

        self::assertTrue($result);
    }

    public function testInsertMany()
    {
        $categories = [];
        for ($i=0; $i < 10; $i++) { 
            $categories[] = [
                "id" => "ID $i",
                "name" => "Name $i",
                "is_active" => true
            ];
        }

        // $result = Category::query()->insert($categories);
        $result = Category::insert($categories);
        self::assertTrue($result);

        // $total = Category::query()->count();
        $total = Category::count();
        self::assertEquals(10, $total);        
    }

    public function testFind()
    {
        $this->seed(CategorySeeder::class);

        // $category = Category::query()->find('FOOD');
        $category = Category::find('FOOD');

        self::assertNotNull($category);

        self::assertEquals("FOOD", $category->id);
        self::assertEquals("Food", $category->name);
        self::assertEquals("Food Category", $category->description);
    }

    public function testUpdate()
    {
        $this->seed(CategorySeeder::class);

        $category = Category::find('FOOD');

        $category->name = 'Food Updated';
        $result = $category->update();

        self::assertTrue($result);
    }

    public function testSelect()
    {
        for ($i=0; $i < 5; $i++) { 
            $category = new Category();
            $category->id = "ID $i";
            $category->name = "Category $i";
            $category->is_active = true;
            $category->save();
        }

        $categories = Category::whereNull("description")->get();
        self::assertEquals(5, $categories->count());
        $categories->each(function($category) {
            self::assertNull($category->description);

            $category->description = "updated";
            $category->update();
        });
    }

    public function testUpdateMany()
    {
        $categories = [];
        for ($i=0; $i < 10; $i++) { 
            $categories[] = [
                "id" => "ID $i",
                "name" => "Name $i",
                "is_active" => true
            ];
        }

        $result = Category::insert($categories);
        self::assertTrue($result);

        Category::whereNull("description")->update([
            "description" => "updated"
        ]);
        $total = Category::where("description", "=", "updated")->count();
        self::assertEquals(10, $total);
    }

    public function testDelete()
    {
        $this->seed(CategorySeeder::class);

        $category = Category::find('FOOD');
        $result = $category->delete();

        self::assertTrue($result);

        $total = Category::count();
        self::assertEquals(0, $total);
    }

    public function testDeleteMany()
    {
        $categories = [];
        for ($i=0; $i < 10; $i++) { 
            $categories[] = [
                "id" => "ID $i",
                "name" => "Name $i",
                "is_active" => true
            ];
        }

        $result = Category::insert($categories);
        self::assertTrue($result);

        $total = Category::count();
        assertEquals(10, $total);

        Category::whereNull("description")->delete();

        $total = Category::count();
        assertEquals(0, $total);
    }

    public function testCreate()
    {
        $request = [
            "id" => "FOOD",
            "name" => "Food",
            "description" => "Food Category"
        ];

        $category = new Category($request);
        $category->save();

        self::assertNotNull($category->id);
    }

    public function testCreateUsingQueryBuilder()
    {
        $request = [
            "id" => "FOOD",
            "name" => "Food",
            "description" => "Food Category"
        ];

        $category = Category::create($request);

        self::assertNotNull($category->id);
    }

    public function testUpdatedMass()
    {
        $this->seed(CategorySeeder::class);

        $request = [
            "name" => "Food Updated",
            "description" => "Food Category Updated"
        ];

        $category = Category::find("FOOD");
        $category->fill($request);
        $category->save();

        self::assertNotNull($category->id);
    }

    public function testGlobalScope()
    {
        $category = new Category();
        $category->id = "FOOD";
        $category->name = "Food";
        $category->description = "Food Category";
        $category->is_active = false;
        $category->save();

        $category = Category::find("FOOD");
        self::assertNull($category);

        $category = Category::withoutGlobalScopes([IsActiveScope::class])->find("FOOD");
        self::assertNotNull($category);
    }

    public function testOneToManyCategory()
    {
        $this->seed([CategorySeeder::class, ProductSeeder::class]);

        $category = Category::find("FOOD");
        self::assertNotNull($category);

        $products = $category->products;

        self::assertNotNull($products);
        self::assertCount(2, $products);
    }

    public function testOneToOneQuery()
    {
        $category = new Category();
        $category->id = "FOOD";
        $category->name = "Food";
        $category->description = "Food Category";
        $category->is_active = true;
        $category->save();

        $products = new Product();
        $products->id = "id";
        $products->name = "Product 1";
        $products->description = "Description 1";
        $category->products()->save($products);

        self::assertNotNull($products->category_id);
    }

    public function testRelationshipQuery()
    {
        $this->seed([CategorySeeder::class, ProductSeeder::class]);

        $category = Category::find('FOOD');
        $products = $category->products;

        self::assertCount(2, $products);

        $outOfStockProducts = $category->products()->where("stock", "<=", "0")->get();
        self::assertCount(2, $outOfStockProducts);
    }

    public function testHasManyThrough()
    {
        $this->seed([CategorySeeder::class, ProductSeeder::class, CustomerSeeder::class, ReviewSeeder::class]);

        $category = Category::find("FOOD");
        self::assertNotNull($category);

        $reviews = $category->reviews;
        self::assertNotNull($reviews);

        self::assertCount(2, $reviews);
    }

    public function testQueryingRelations()
    {
        $this->seed([CategorySeeder::class, ProductSeeder::class]);

        $category = Category::find("FOOD");
        $product = $category->products()->where("price", "=", 200)->get();

        self::assertNotNull($product);
        self::assertCount(1, $product);
        self::assertEquals("2", $product[0]->id);
    }

    public function testAggregatingRelations()
    {
        $this->seed([CategorySeeder::class, ProductSeeder::class]);

        $category = Category::find("FOOD");
        $total = $category->products()->count();

        self::assertEquals(2, $total);

        $total = $category->products()->where("price", 200)->count();

        self::assertEquals(1, $total);
    }

    public function testEloquentCollection()
    {
        $this->seed([CategorySeeder::class, ProductSeeder::class]);

        // 2 products, 1 2
        $products = Product::query()->get();

        // WHERE id IN (1,2)
        $products = $products->toQuery()->where("price", 200)->get();

        self::assertNotNull($products);
        self::assertEquals("2", $products[0]->id);
    }
}
