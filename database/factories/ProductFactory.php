<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    private const CATEGORIES = [
        'Electronics',
        'Clothing',
        'Home & Garden',
        'Sports & Outdoors',
        'Books',
        'Toys & Games',
        'Beauty & Health',
        'Automotive',
        'Food & Drink',
        'Office Supplies',
    ];

    private const BRANDS = [
        'TechPro', 'NovaCorp', 'EcoLife', 'UrbanStyle', 'FitGear',
        'HomeNest', 'BookWorm', 'PlayZone', 'GlowUp', 'AutoMax',
        'FreshBite', 'DeskMate', 'SportElite', 'GreenLeaf', 'LuxeLine',
        'SmartHome', 'ActiveWear', 'PageTurner', 'GameOn', 'PureBeauty',
    ];

    private const TAGS_BY_CATEGORY = [
        'Electronics' => ['wireless', 'bluetooth', 'smart', 'portable', 'rechargeable', 'HD', '4K', 'USB-C', 'noise-cancelling', 'waterproof'],
        'Clothing' => ['cotton', 'organic', 'slim-fit', 'casual', 'formal', 'unisex', 'breathable', 'recycled', 'stretchy', 'machine-washable'],
        'Home & Garden' => ['eco-friendly', 'compact', 'modern', 'rustic', 'handmade', 'durable', 'indoor', 'outdoor', 'minimalist', 'decorative'],
        'Sports & Outdoors' => ['lightweight', 'waterproof', 'UV-protection', 'ergonomic', 'foldable', 'professional', 'beginner', 'all-terrain', 'quick-dry', 'anti-slip'],
        'Books' => ['bestseller', 'hardcover', 'paperback', 'illustrated', 'award-winning', 'signed', 'limited-edition', 'audiobook', 'ebook', 'series'],
        'Toys & Games' => ['educational', 'ages-3+', 'ages-6+', 'ages-12+', 'multiplayer', 'solo', 'battery-powered', 'wooden', 'collectible', 'STEM'],
        'Beauty & Health' => ['organic', 'vegan', 'cruelty-free', 'hypoallergenic', 'SPF', 'fragrance-free', 'paraben-free', 'travel-size', 'dermatologist-tested', 'anti-aging'],
        'Automotive' => ['universal-fit', 'heavy-duty', 'OEM', 'aftermarket', 'chrome', 'LED', 'weatherproof', 'easy-install', 'premium', 'performance'],
        'Food & Drink' => ['organic', 'gluten-free', 'vegan', 'sugar-free', 'non-GMO', 'fair-trade', 'locally-sourced', 'artisan', 'keto-friendly', 'high-protein'],
        'Office Supplies' => ['ergonomic', 'recycled', 'refillable', 'professional', 'compact', 'adjustable', 'wireless', 'magnetic', 'stackable', 'archival'],
    ];

    private const PRODUCT_TEMPLATES = [
        'Electronics' => [
            '{brand} Wireless Bluetooth Headphones',
            '{brand} Smart Watch Series {num}',
            '{brand} Portable Speaker Pro',
            '{brand} 4K Ultra HD Monitor {num}"',
            '{brand} USB-C Hub Adapter',
            '{brand} Noise Cancelling Earbuds',
            '{brand} Mechanical Keyboard RGB',
            '{brand} Wireless Mouse Ergonomic',
            '{brand} Laptop Stand Adjustable',
            '{brand} Fast Charger {num}W',
            '{brand} Smart Home Hub',
            '{brand} Webcam HD {num}p',
            '{brand} External SSD {num}GB',
            '{brand} Tablet {num}" Display',
            '{brand} Phone Case Premium',
        ],
        'Clothing' => [
            '{brand} Classic Fit Cotton T-Shirt',
            '{brand} Slim Fit Denim Jeans',
            '{brand} Organic Cotton Hoodie',
            '{brand} Performance Running Shorts',
            '{brand} Formal Button-Down Shirt',
            '{brand} Casual Linen Pants',
            '{brand} Winter Puffer Jacket',
            '{brand} Wool Blend Sweater',
            '{brand} Athletic Compression Leggings',
            '{brand} Breathable Polo Shirt',
        ],
        'Home & Garden' => [
            '{brand} Modern Table Lamp',
            '{brand} Indoor Herb Garden Kit',
            '{brand} Scented Soy Candle Set',
            '{brand} Minimalist Wall Clock',
            '{brand} Bamboo Cutting Board',
            '{brand} Ceramic Plant Pot Set',
            '{brand} Cotton Throw Blanket',
            '{brand} Stainless Steel Water Bottle',
            '{brand} Eco-Friendly Cleaning Kit',
            '{brand} Nordic Style Vase',
        ],
        'Sports & Outdoors' => [
            '{brand} Yoga Mat Premium',
            '{brand} Camping Tent {num}-Person',
            '{brand} Running Shoes Ultra',
            '{brand} Hiking Backpack {num}L',
            '{brand} Resistance Bands Set',
            '{brand} Insulated Water Flask',
            '{brand} Cycling Helmet Pro',
            '{brand} Swimming Goggles Anti-Fog',
            '{brand} Jump Rope Speed Pro',
            '{brand} Foam Roller Recovery',
        ],
        'Books' => [
            'The Art of {brand} Living',
            '{brand} Guide to Modern Design',
            'Mastering {brand} Techniques',
            'The {brand} Cookbook Collection',
            '{brand} Science Encyclopedia',
            'Tales of the {brand} World',
            '{brand} Photography Masterclass',
            'The Complete {brand} History',
            '{brand} Mindfulness Journal',
            '{brand} Adventure Stories',
        ],
        'Toys & Games' => [
            '{brand} Building Blocks {num} Pieces',
            '{brand} Board Game Strategy Edition',
            '{brand} RC Car Off-Road',
            '{brand} Puzzle {num} Pieces',
            '{brand} STEM Robot Kit',
            '{brand} Card Game Family Pack',
            '{brand} Plush Toy Collection',
            '{brand} Art & Craft Set',
            '{brand} Science Experiment Kit',
            '{brand} Magnetic Tiles Set',
        ],
        'Beauty & Health' => [
            '{brand} Vitamin C Serum',
            '{brand} Organic Face Moisturizer',
            '{brand} Hair Care Bundle',
            '{brand} Essential Oil Diffuser',
            '{brand} SPF 50 Sunscreen',
            '{brand} Retinol Night Cream',
            '{brand} Bamboo Toothbrush Set',
            '{brand} Lip Balm Collection',
            '{brand} Body Lotion Hydrating',
            '{brand} Facial Cleansing Brush',
        ],
        'Automotive' => [
            '{brand} LED Headlight Kit',
            '{brand} Car Phone Mount',
            '{brand} Tire Pressure Gauge',
            '{brand} Dash Cam {num}K',
            '{brand} Seat Cover Universal',
            '{brand} Car Vacuum Portable',
            '{brand} Air Freshener Set',
            '{brand} Floor Mat All-Weather',
            '{brand} Jump Starter Portable',
            '{brand} Car Wash Kit Premium',
        ],
        'Food & Drink' => [
            '{brand} Organic Coffee Beans',
            '{brand} Artisan Chocolate Box',
            '{brand} Matcha Green Tea Powder',
            '{brand} Protein Bar Variety Pack',
            '{brand} Raw Honey Collection',
            '{brand} Trail Mix Premium',
            '{brand} Olive Oil Extra Virgin',
            '{brand} Hot Sauce Gift Set',
            '{brand} Dried Fruit Mix Organic',
            '{brand} Herbal Tea Sampler',
        ],
        'Office Supplies' => [
            '{brand} Ergonomic Office Chair',
            '{brand} Standing Desk Converter',
            '{brand} Notebook Set Premium',
            '{brand} Desk Organizer Bamboo',
            '{brand} Fountain Pen Gift Set',
            '{brand} Whiteboard Magnetic',
            '{brand} File Cabinet Compact',
            '{brand} LED Desk Lamp',
            '{brand} Paper Shredder Cross-Cut',
            '{brand} Planner Weekly {num}',
        ],
    ];

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $category = $this->faker->randomElement(self::CATEGORIES);
        $brand = $this->faker->randomElement(self::BRANDS);
        $tags = $this->faker->randomElements(
            self::TAGS_BY_CATEGORY[$category],
            $this->faker->numberBetween(1, 4)
        );

        $template = $this->faker->randomElement(self::PRODUCT_TEMPLATES[$category]);
        $title = str_replace(
            ['{brand}', '{num}'],
            [$brand, $this->faker->randomElement([2, 3, 4, 5, 8, 10, 12, 15, 24, 27, 32, 50, 64, 128, 256, 500, 1000, 1080, 2024, 2025])],
            $template
        );

        $priceRanges = [
            'Electronics' => [29.99, 999.99],
            'Clothing' => [14.99, 199.99],
            'Home & Garden' => [9.99, 149.99],
            'Sports & Outdoors' => [12.99, 299.99],
            'Books' => [7.99, 59.99],
            'Toys & Games' => [9.99, 89.99],
            'Beauty & Health' => [5.99, 79.99],
            'Automotive' => [8.99, 199.99],
            'Food & Drink' => [4.99, 49.99],
            'Office Supplies' => [6.99, 499.99],
        ];

        [$minPrice, $maxPrice] = $priceRanges[$category];

        return [
            'title' => $title,
            'description' => $this->faker->paragraph(3),
            'category' => $category,
            'brand' => $brand,
            'tags' => $tags,
            'price' => round($this->faker->randomFloat(2, $minPrice, $maxPrice), 2),
            'rating' => round($this->faker->randomFloat(2, 1.0, 5.0), 2),
            'in_stock' => $this->faker->boolean(85), // 85% chance in stock
            'image_url' => 'https://picsum.photos/seed/' . substr(md5($title . $this->faker->uuid()), 0, 10) . '/400/300',
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
