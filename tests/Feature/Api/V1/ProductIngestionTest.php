<?php

namespace Tests\Feature\Api\V1;

use App\Enums\ProductStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductIngestionTest extends TestCase
{
    use RefreshDatabase; // This trait resets the database after each test.

    private User $crawlerUser;

    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create a dedicated "crawler" user for authentication in our tests
        $this->crawlerUser = User::factory()->create([
            'name' => 'Test Crawler',
        ]);
    }

        /** @test */
    public function it_ingests_products_successfully_with_valid_data_and_token(): void
    {
        // 1. Arrange: Prepare the data to be sent
        $productData = [
            'products' => [
                [
                    'sku' => 'TEST-SKU-123',
                    'name' => 'A Test Product',
                    'price' => '19.99',
                    'description' => 'A great test product.',
                ],
            ],
        ];

        // 2. Act: Make the API call, authenticated as our crawler user
        $response = $this->actingAs($this->crawlerUser, 'sanctum')
                         ->postJson('/api/v1/products/ingest', $productData);

        // 3. Assert: Check the outcome
        $response->assertStatus(201); // Check for "201 Created" status

        // Assert that the product was actually created in the database
        $this->assertDatabaseHas('products', [
            'sku' => 'TEST-SKU-123',
            'name' => 'A Test Product',
            'price' => 19.99,
            'status' => ProductStatus::PendingReview->value, // Check it was set correctly
        ]);
    }

}
