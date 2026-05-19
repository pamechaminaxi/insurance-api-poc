<?php

namespace Tests\Feature;

use App\Models\Claim;
use App\Models\Quote;
use App\Models\Role;
use App\Models\User;
use App\Services\QuoteService;
use App\Services\ClaimService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuoteValidationTest extends TestCase
{
    use RefreshDatabase;

    private $admin;
    private $agent;
    private $customer;
    private $quoteService;
    private $claimService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        $adminRole = Role::create(['name' => 'Admin']);
        $agentRole = Role::create(['name' => 'Agent']);
        $customerRole = Role::create(['name' => 'Customer']);

        // Create users
        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role_id' => $adminRole->id,
        ]);

        $this->agent = User::create([
            'name' => 'Agent User',
            'email' => 'agent@test.com',
            'password' => bcrypt('password'),
            'role_id' => $agentRole->id,
        ]);

        $this->customer = User::create([
            'name' => 'Customer User',
            'email' => 'customer@test.com',
            'password' => bcrypt('password'),
            'role_id' => $customerRole->id,
        ]);

        $this->quoteService = new QuoteService();
        $this->claimService = new ClaimService();
    }

    public function test_cannot_create_duplicate_quote_for_same_customer_and_insurance_type_if_rejected()
    {
        $this->actingAs($this->agent);

        // Create a quote that will be rejected
        $quote = $this->quoteService->createQuote([
            'customer_name' => 'Customer User',
            'customer_user_id' => $this->customer->id,
            'insurance_type' => 'health',
            'premium_amount' => 500.00,
            'coverage_amount' => 10000.00,
        ]);

        // Submit the quote
        $quote = $this->quoteService->updateQuote($quote->id, ['status' => 'submitted']);

        // Reject the quote
        $this->actingAs($this->admin);
        $quote = $this->quoteService->updateQuote($quote->id, ['status' => 'rejected']);

        // Try to create another health quote for the same customer
        $this->actingAs($this->agent);
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('A quote for this customer with the same insurance type has been previously rejected, and cannot be re-created.');

        $this->quoteService->createQuote([
            'customer_name' => 'Customer User',
            'customer_user_id' => $this->customer->id,
            'insurance_type' => 'health',
            'premium_amount' => 600.00,
            'coverage_amount' => 12000.00,
        ]);
    }

    public function test_cannot_create_duplicate_quote_for_same_customer_and_insurance_type_in_any_status()
    {
        $this->actingAs($this->agent);

        // Create an initial draft quote
        $this->quoteService->createQuote([
            'customer_name' => 'Customer User',
            'customer_user_id' => $this->customer->id,
            'insurance_type' => 'health',
            'premium_amount' => 500.00,
            'coverage_amount' => 10000.00,
        ]);

        // Try to create another quote of the same type for Meera (the customer)
        try {
            $this->quoteService->createQuote([
                'customer_name' => 'Customer User',
                'customer_user_id' => $this->customer->id,
                'insurance_type' => 'health',
                'premium_amount' => 600.00,
                'coverage_amount' => 12000.00,
            ]);
            $this->fail('Created duplicate quote when one already exists in draft.');
        } catch (\Exception $e) {
            $this->assertStringContainsString('A quote for this customer with the same insurance type already exists.', $e->getMessage());
        }
    }

    public function test_forward_only_status_transitions()
    {
        $this->actingAs($this->agent);

        $quote = $this->quoteService->createQuote([
            'customer_name' => 'Customer User',
            'customer_user_id' => $this->customer->id,
            'insurance_type' => 'life',
            'premium_amount' => 500.00,
            'coverage_amount' => 10000.00,
        ]);

        // 1. Cannot transition draft directly to approved
        try {
            $this->actingAs($this->admin);
            $this->quoteService->updateQuote($quote->id, ['status' => 'approved']);
            $this->fail('Draft quote was directly approved.');
        } catch (\Exception $e) {
            $this->assertStringContainsString('A draft quote cannot be approved or rejected. It must be submitted first.', $e->getMessage());
        }

        // 2. Cannot transition draft directly to rejected
        try {
            $this->actingAs($this->admin);
            $this->quoteService->updateQuote($quote->id, ['status' => 'rejected']);
            $this->fail('Draft quote was directly rejected.');
        } catch (\Exception $e) {
            $this->assertStringContainsString('A draft quote cannot be approved or rejected. It must be submitted first.', $e->getMessage());
        }

        // 3. Transition draft to submitted
        $this->actingAs($this->agent);
        $quote = $this->quoteService->updateQuote($quote->id, ['status' => 'submitted']);
        $this->assertEquals('submitted', $quote->status);

        // 4. Cannot transition submitted back to draft
        try {
            $this->actingAs($this->admin);
            $this->quoteService->updateQuote($quote->id, ['status' => 'draft']);
            $this->fail('Submitted quote transitioned back to draft.');
        } catch (\Exception $e) {
            $this->assertStringContainsString('A submitted quote cannot be changed back to draft status.', $e->getMessage());
        }

        // 5. Transition submitted to approved
        $this->actingAs($this->admin);
        $quote = $this->quoteService->updateQuote($quote->id, ['status' => 'approved']);
        $this->assertEquals('approved', $quote->status);

        // 6. Cannot transition approved to rejected
        try {
            $this->quoteService->updateQuote($quote->id, ['status' => 'rejected']);
            $this->fail('Approved quote transitioned to rejected.');
        } catch (\Exception $e) {
            $this->assertStringContainsString('An approved quote status cannot be changed.', $e->getMessage());
        }
    }

    public function test_soft_delete_and_is_delete_flag()
    {
        $this->actingAs($this->admin);

        $quote = $this->quoteService->createQuote([
            'customer_name' => 'Customer User',
            'customer_user_id' => $this->customer->id,
            'insurance_type' => 'motor',
            'premium_amount' => 500.00,
            'coverage_amount' => 10000.00,
        ]);

        $quoteId = $quote->id;

        // Perform delete
        $this->quoteService->deleteQuote($quoteId);

        // Assert quote is soft deleted
        $quoteFromDb = Quote::withTrashed()->find($quoteId);
        $this->assertNotNull($quoteFromDb);
        $this->assertNotNull($quoteFromDb->deleted_at);
        $this->assertEquals(1, $quoteFromDb->is_delete);

        // Assert standard query does not return it
        $this->assertNull(Quote::find($quoteId));
    }

    public function test_claim_creation_validation_rules()
    {
        $this->actingAs($this->admin);

        // 1. Create a draft quote
        $quote = $this->quoteService->createQuote([
            'customer_name' => 'Customer User',
            'customer_user_id' => $this->customer->id,
            'insurance_type' => 'motor',
            'premium_amount' => 500.00,
            'coverage_amount' => 10000.00,
        ]);

        // Try to create claim for draft quote
        try {
            $this->actingAs($this->customer);
            $this->claimService->createClaim([
                'quote_id' => $quote->id,
                'claim_amount' => 2000.00,
                'description' => 'Test Claim',
            ]);
            $this->fail('Claim created for draft quote.');
        } catch (\Exception $e) {
            $this->assertStringContainsString('A claim can only be created for an APPROVED quote.', $e->getMessage());
        }

        // 2. Submit and Approve the quote
        $this->actingAs($this->agent);
        $quote = $this->quoteService->updateQuote($quote->id, ['status' => 'submitted']);
        $this->actingAs($this->admin);
        $quote = $this->quoteService->updateQuote($quote->id, ['status' => 'approved']);

        // Soft delete the quote
        $this->quoteService->deleteQuote($quote->id);

        // Try to create claim for soft-deleted approved quote
        try {
            $this->actingAs($this->customer);
            $this->claimService->createClaim([
                'quote_id' => $quote->id,
                'claim_amount' => 2000.00,
                'description' => 'Test Claim',
            ]);
            $this->fail('Claim created for deleted quote.');
        } catch (\Exception $e) {
            $this->assertStringContainsString('A claim cannot be created for a deleted quote.', $e->getMessage());
        }

        // Restore quote
        $quote = Quote::withTrashed()->findOrFail($quote->id);
        $quote->restore();
        $this->assertEquals(0, $quote->is_delete);
        $this->assertNull($quote->deleted_at);

        // Now creating the claim should succeed
        $claim = $this->claimService->createClaim([
            'quote_id' => $quote->id,
            'claim_amount' => 2000.00,
            'description' => 'Test Claim',
        ]);

        $this->assertNotNull($claim);
        $this->assertEquals('Pending', $claim->fresh()->status);
    }
}
