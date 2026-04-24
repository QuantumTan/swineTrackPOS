<?php

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;

test('authenticated users can create a supplier with an expanded profile', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->post(route('suppliers.store'), [
            'supplier_name' => 'Metro Cuts Trading',
            'supplier_contact_first_name' => 'Ana',
            'supplier_contact_last_name' => 'Ramos',
            'supplier_phone_number' => '09171234567',
            'supplier_email' => 'ana.ramos@metrocuts.test',
            'supplier_address' => 'Barangay San Isidro, Tarlac City, Tarlac',
            'supplier_payment_terms' => 'COD upon delivery',
            'supplier_status' => 'Active',
            'supplier_notes' => 'Reliable for premium belly and loin cuts.',
        ]);

    $response
        ->assertRedirect(route('suppliers.index'))
        ->assertSessionHas('status', 'Supplier added successfully.');

    $this->assertDatabaseHas('supplier', [
        'supplier_name' => 'Metro Cuts Trading',
        'supplier_contact_first_name' => 'Ana',
        'supplier_contact_last_name' => 'Ramos',
        'supplier_phone_number' => '09171234567',
        'supplier_email' => 'ana.ramos@metrocuts.test',
        'supplier_address' => 'Barangay San Isidro, Tarlac City, Tarlac',
        'supplier_payment_terms' => 'COD upon delivery',
        'supplier_status' => 'Active',
        'supplier_notes' => 'Reliable for premium belly and loin cuts.',
    ]);
});

test('updating a supplier normalizes empty optional profile fields', function () {
    $user = User::factory()->create();

    $supplier = Supplier::create([
        'supplier_name' => 'North Ridge Meats',
        'supplier_contact_first_name' => 'Liza',
        'supplier_contact_last_name' => 'Santos',
        'supplier_phone_number' => '09175557777',
        'supplier_email' => 'dispatch@northridge.test',
        'supplier_address' => 'Purok 3, Urdaneta City, Pangasinan',
        'supplier_payment_terms' => '7-day terms',
        'supplier_status' => 'Active',
        'supplier_notes' => 'Existing supplier profile.',
    ]);

    $response = $this
        ->actingAs($user)
        ->put(route('suppliers.update', $supplier), [
            'supplier_name' => 'North Ridge Meats',
            'supplier_contact_first_name' => 'Liza',
            'supplier_contact_last_name' => 'Santos',
            'supplier_phone_number' => '',
            'supplier_email' => '',
            'supplier_address' => 'Purok 3, Urdaneta City, Pangasinan',
            'supplier_payment_terms' => 'Weekly billing',
            'supplier_status' => 'Inactive',
            'supplier_notes' => 'Paused for follow-up after several short-notice cancellations.',
        ]);

    $response
        ->assertRedirect(route('suppliers.index'))
        ->assertSessionHas('status', 'Supplier updated successfully.');

    $supplier->refresh();

    expect($supplier->supplier_status)->toBe('Inactive')
        ->and($supplier->supplier_phone_number)->toBeNull()
        ->and($supplier->supplier_email)->toBeNull()
        ->and($supplier->supplier_payment_terms)->toBe('Weekly billing');
});

test('supplier directory shows expanded profile details and view actions', function () {
    $user = User::factory()->create();

    $supplier = Supplier::create([
        'supplier_name' => 'South Valley Hog Farm',
        'supplier_contact_first_name' => 'Marco',
        'supplier_contact_last_name' => 'Dela Cruz',
        'supplier_phone_number' => '09179876543',
        'supplier_email' => 'purchasing@southvalley.test',
        'supplier_address' => 'Maharlika Highway, Cabanatuan City, Nueva Ecija',
        'supplier_payment_terms' => '7-day terms',
        'supplier_status' => 'Active',
        'supplier_notes' => 'Usually delivers larger mixed-cut batches on Tuesdays and Fridays.',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('suppliers.index'));

    $response->assertOk();
    $response->assertSee('Contact Ready');
    $response->assertSee('South Valley Hog Farm');
    $response->assertSee('Marco');
    $response->assertSee('Dela Cruz');
    $response->assertSee('Maharlika Highway, Cabanatuan City, Nueva Ecija');
    $response->assertSee('7-day terms');
    $response->assertSee('data-bs-target="#supplierView'.$supplier->supplier_id.'"', false);
});

test('expired supplier update submissions redirect back with a helpful message', function () {
    $user = User::factory()->create();

    $supplier = Supplier::create([
        'supplier_name' => 'South Valley Hog Farm',
        'supplier_status' => 'Active',
    ]);

    $response = $this
        ->withMiddleware(VerifyCsrfToken::class)
        ->from(route('suppliers.index'))
        ->actingAs($user)
        ->put(route('suppliers.update', $supplier), [
            'supplier_name' => 'South Valley Hog Farm',
            'supplier_status' => 'Inactive',
        ]);

    $response
        ->assertRedirect(route('suppliers.index'))
        ->assertSessionHas('error', 'Your session expired before the form was submitted. Please try saving again.');
});
