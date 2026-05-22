<?php

namespace Tests\Feature;

use App\Models\Quote;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ClaimRequestValidationTest extends TestCase
{
    use RefreshDatabase;

    private $customer;
    private $quote;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::create(['name' => 'Admin']);
        $agentRole = Role::create(['name' => 'Agent']);
        $customerRole = Role::create(['name' => 'Customer']);

        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role_id' => $adminRole->id,
        ]);

        $this->customer = User::create([
            'name' => 'Customer User',
            'email' => 'customer@test.com',
            'password' => bcrypt('password'),
            'role_id' => $customerRole->id,
        ]);

        // Create an approved quote
        $this->quote = Quote::forceCreate([
            'quote_number' => 'QT-TEST1234',
            'created_by' => $admin->id,
            'customer_name' => 'Customer User',
            'customer_user_id' => $this->customer->id,
            'insurance_type' => 'motor',
            'premium_amount' => 500.00,
            'coverage_amount' => 10000.00,
            'status' => 'approved',
        ]);
    }

    public function test_claim_request_accepts_pdf_with_odt_mime_type_when_using_extensions_validation()
    {
        $this->actingAs($this->customer);

        // Create a fake file with ODT mime type but named sample.pdf
        $file = UploadedFile::fake()->create('sample.pdf', 10, 'application/vnd.oasis.opendocument.text');

        $response = $this->postJson('/api/claims', [
            'quote_id' => $this->quote->id,
            'claim_amount' => 2000.00,
            'description' => 'Test Claim',
            'documents' => [$file],
        ]);

        $response->assertStatus(201);
    }

    public function test_claim_request_fails_with_proper_message_when_post_data_too_large()
    {
        $this->actingAs($this->customer);

        // Create a 15MB file (15000 KB) which exceeds post_max_size (10M)
        $file = UploadedFile::fake()->create('large_document.pdf', 15000);

        $response = $this->call(
            'POST',
            '/api/claims',
            [
                'quote_id' => $this->quote->id,
                'claim_amount' => 2000.00,
                'description' => 'Test Claim',
            ],
            [], // cookies
            ['documents' => [$file]], // files
            ['CONTENT_LENGTH' => 16000000] // server parameters
        );

        $response->assertStatus(413);
        $response->assertJson([
            'success' => false,
            'message' => 'The uploaded file(s) exceed the maximum allowed size of 10MB.',
            'errors' => null,
        ]);
    }
}


