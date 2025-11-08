<?php

namespace Tests\Unit;

use App\Models\Setting;
use App\Models\Transaction;
use App\Services\TransactionProofService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TransactionProofServiceTest extends TestCase
{
    use RefreshDatabase;

    private TransactionProofService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TransactionProofService();
        Storage::fake('local');
    }

    public function test_prepare_for_store_returns_empty_array_when_no_file(): void
    {
        $result = $this->service->prepareForStore(null, null, now(), 'pemasukan');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_prepare_for_store_stores_file_with_custom_name(): void
    {
        $file = UploadedFile::fake()->image('proof.jpg', 100, 100);
        $date = Carbon::parse('2024-01-15');
        $customName = 'custom-proof-name';

        $result = $this->service->prepareForStore($file, $customName, $date, 'pemasukan');

        $this->assertArrayHasKey('proof_path', $result);
        $this->assertArrayHasKey('proof_filename', $result);
        $this->assertArrayHasKey('proof_token', $result);
        $this->assertStringContainsString('custom-proof-name', $result['proof_filename']);
        $this->assertStringContainsString('2024/01/pemasukan', $result['proof_path']);
    }

    public function test_prepare_for_store_stores_file_without_custom_name(): void
    {
        $file = UploadedFile::fake()->image('original-proof.jpg', 100, 100);
        $date = Carbon::parse('2024-02-20');

        $result = $this->service->prepareForStore($file, null, $date, 'pengeluaran');

        $this->assertArrayHasKey('proof_path', $result);
        $this->assertArrayHasKey('proof_filename', $result);
        $this->assertStringContainsString('2024/02/pengeluaran', $result['proof_path']);
    }

    public function test_handle_update_deletes_existing_when_new_file_provided(): void
    {
        $transaction = Transaction::factory()->create([
            'proof_path' => 'old/path/proof.jpg',
            'proof_filename' => 'old-proof.jpg',
        ]);
        $newFile = UploadedFile::fake()->image('new-proof.jpg', 100, 100);
        $date = Carbon::parse('2024-03-10');

        $result = $this->service->handleUpdate($transaction, $newFile, null, $date, 'pemasukan');

        $this->assertArrayHasKey('proof_path', $result);
        $this->assertNotEquals('old/path/proof.jpg', $result['proof_path']);
    }

    public function test_handle_update_returns_empty_when_no_file_and_no_existing_proof(): void
    {
        $transaction = Transaction::factory()->create([
            'proof_path' => null,
        ]);
        $date = Carbon::parse('2024-04-05');

        $result = $this->service->handleUpdate($transaction, null, null, $date, 'pemasukan');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_handle_update_moves_existing_file_when_custom_name_changes(): void
    {
        // Create a file first
        $file = UploadedFile::fake()->image('old-proof.jpg', 100, 100);
        $date = Carbon::parse('2024-01-15');
        
        $initialResult = $this->service->prepareForStore($file, 'old-proof', $date, 'pemasukan');
        
        $transaction = Transaction::factory()->create([
            'proof_path' => $initialResult['proof_path'],
            'proof_filename' => $initialResult['proof_filename'],
            'proof_directory' => $initialResult['proof_directory'] ?? null,
            'proof_disk' => $initialResult['proof_disk'],
        ]);
        
        // Ensure file exists
        Storage::disk('local')->put($initialResult['proof_path'], $file->getContent());
        
        $newDate = Carbon::parse('2024-05-15');
        $newCustomName = 'new-custom-name';

        $result = $this->service->handleUpdate($transaction, null, $newCustomName, $newDate, 'pengeluaran');

        $this->assertArrayHasKey('proof_path', $result);
        $this->assertStringContainsString('new-custom-name', $result['proof_filename']);
        $this->assertStringContainsString('2024/05/pengeluaran', $result['proof_path']);
    }
}

