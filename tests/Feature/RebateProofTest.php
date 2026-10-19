<?php

use App\Enums\RebateStatus;
use App\Models\Attachment;
use App\Models\Invoice;
use App\Models\Rebate;
use App\Models\RebateRule;
use App\Models\User;
use App\Services\RebateService;
use Database\Seeders\RbacSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RbacSeeder::class);
});

function rebateAdmin(): User
{
    return User::query()->where('email', 'admin@sekolah.test')->firstOrFail();
}

function rebateProofImage(string $name): UploadedFile
{
    $path = sys_get_temp_dir().'/'.$name;
    copy(__DIR__.'/../Fixtures/proof.png', $path);

    return new UploadedFile($path, $name, 'image/png', null, true);
}

function rebateProofTextFile(string $name): UploadedFile
{
    $path = sys_get_temp_dir().'/'.$name;
    file_put_contents($path, 'bukan gambar');

    return new UploadedFile($path, $name, 'text/plain', null, true);
}

function approvedRebate(): Rebate
{
    $invoice = Invoice::factory()->create([
        'total_amount' => '100000.00',
        'paid_amount' => '100000.00',
    ]);
    $rule = RebateRule::factory()->create(['type' => 'percentage', 'value' => '2.00']);

    $user = User::factory()->create();

    $rebate = app(RebateService::class)->request([
        'invoice_id' => $invoice->id,
        'rebate_rule_id' => $rule->id,
    ], $user);

    app(RebateService::class)->approve($rebate, $user);

    return $rebate->refresh();
}

it('requires a proof photo when paying a rebate', function () {
    $rebate = approvedRebate();

    $this->actingAs(rebateAdmin())
        ->post('/rebates/'.$rebate->id.'/pay', [
            'reference' => 'TRF-REBATE-1',
        ])
        ->assertSessionHasErrors('proof_photo');

    expect($rebate->refresh()->status)->toBe(RebateStatus::Approved);
});

it('stores the proof photo when paying a rebate', function () {
    Storage::fake('public');

    $rebate = approvedRebate();

    $this->actingAs(rebateAdmin())
        ->post('/rebates/'.$rebate->id.'/pay', [
            'reference' => 'TRF-REBATE-1',
            'proof_photo' => rebateProofImage('bukti-rebate.png'),
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('attachments', [
        'attachable_type' => Rebate::class,
        'attachable_id' => $rebate->id,
        'original_name' => 'bukti-rebate.png',
    ]);

    $attachment = Attachment::query()->firstOrFail();
    Storage::disk('public')->assertExists($attachment->path);

    expect($rebate->refresh()->status)->toBe(RebateStatus::Paid)
        ->and($rebate->payment_reference)->toBe('TRF-REBATE-1');
});

it('rejects a non-image proof photo when paying a rebate', function () {
    $rebate = approvedRebate();

    $this->actingAs(rebateAdmin())
        ->post('/rebates/'.$rebate->id.'/pay', [
            'reference' => 'TRF-REBATE-1',
            'proof_photo' => rebateProofTextFile('bukan-gambar.txt'),
        ])
        ->assertSessionHasErrors('proof_photo');

    expect($rebate->refresh()->status)->toBe(RebateStatus::Approved)
        ->and(Attachment::query()->count())->toBe(0);
});

it('deletes a rebate proof photo', function () {
    Storage::fake('public');

    $rebate = approvedRebate();

    $this->actingAs(rebateAdmin())
        ->post('/rebates/'.$rebate->id.'/pay', [
            'reference' => 'TRF-REBATE-1',
            'proof_photo' => rebateProofImage('bukti-rebate.png'),
        ])
        ->assertRedirect();

    $attachment = Attachment::query()->firstOrFail();

    $this->actingAs(rebateAdmin())
        ->delete('/attachments/'.$attachment->id)
        ->assertRedirect();

    $this->assertDatabaseMissing('attachments', ['id' => $attachment->id]);
    Storage::disk('public')->assertMissing($attachment->path);
});
