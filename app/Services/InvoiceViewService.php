<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Invoice as InvoiceModel;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class InvoiceViewService
{
    public function prepareInvoiceData(Invoice $invoice, array $companyInfo = [], array $paymentMethods = []): array
    {
        $invoice->loadMissing('referenceInvoice', 'items');
        $settings = $this->getSettings();
        $companyData = $this->prepareCompanyData($companyInfo, $settings);
        $paymentData = $this->preparePaymentData($paymentMethods, $settings);
        $imageData = $this->prepareImageData($settings);
        $calculations = $this->calculateInvoiceAmounts($invoice);
        
        $labels = $this->prepareLabels($invoice);
        
        return array_merge(
            $companyData,
            $paymentData,
            $imageData,
            $calculations,
            $labels,
            [
                'invoice' => $invoice,
                'settings' => $settings,
            ]
        );
    }
    
    protected function getSettings(): array
    {
        return Cache::remember('invoice_settings', 3600, function () {
            return Setting::pluck('value', 'key')->all();
        });
    }
    
    protected function prepareCompanyData(array $companyInfo, array $settings): array
    {
        return [
            'companyName' => $companyInfo['name'] ?? $settings['company_name'] ?? 'CV. Vodeco Digital Mediatama',
            'companyTagline' => $companyInfo['tagline'] ?? $settings['company_tagline'] ?? "Let's Up Your Brand",
            'companyAddress' => $companyInfo['address'] ?? $settings['company_address'] ?? 'Bandung, Indonesia',
            'companyEmail' => $companyInfo['email'] ?? $settings['company_email'] ?? 'hello@vodeco.co.id',
            'companyPhone' => $companyInfo['phone'] ?? $settings['company_phone'] ?? '+62 878-7046-1427',
            'companyWebsite' => $companyInfo['website'] ?? $settings['company_website'] ?? 'https://vodeco.co.id',
        ];
    }
    
    protected function preparePaymentData(array $paymentMethods, array $settings): array
    {
        return [
            'bcaInfo' => $paymentMethods['bca'] ?? [
                'name' => 'BCA',
                'account_number' => $settings['bank_1_account_number'] ?? '3624500500',
                'account_name' => $settings['bank_1_account_name'] ?? 'CV. Vodeco Digital Mediatama',
            ],
            'mandiriInfo' => $paymentMethods['mandiri'] ?? [
                'name' => 'MANDIRI',
                'account_number' => $settings['bank_2_account_number'] ?? '1390001188113',
                'account_name' => $settings['bank_2_account_name'] ?? 'CV. Vodeco Digital Mediatama',
            ],
            'paymentInstructions' => $paymentMethods['instructions'] ?? 'Payment should be made within 30 days by Cheque. Paypal, WesternUnion, Payoneer, MasterCard',
        ];
    }
    
    protected function prepareImageData(array $settings): array
    {
        $logoData = $this->getImageBase64($settings['company_logo'] ?? 'vodeco.webp');
        $signatureData = $this->getImageBase64($settings['signature_image'] ?? 'image3.png');
        
        $bank1LogoPath = $settings['bank_1_logo'] ?? 'logo-bank-bca.png';
        $bank1LogoData = $this->getImageBase64($bank1LogoPath);
        $bank1LogoMime = $bank1LogoData ? $this->determineMimeType($bank1LogoPath) : null;
        
        $bank2LogoPath = $settings['bank_2_logo'] ?? 'logo bank mandiri.png';
        $bank2LogoData = $this->getImageBase64($bank2LogoPath);
        $bank2LogoMime = $bank2LogoData ? $this->determineMimeType($bank2LogoPath) : null;
        
        return [
            'logoData' => $logoData,
            'signatureData' => $signatureData,
            'bank1LogoData' => $bank1LogoData,
            'bank1LogoMime' => $bank1LogoMime,
            'bank2LogoData' => $bank2LogoData,
            'bank2LogoMime' => $bank2LogoMime,
        ];
    }
    
    protected function getImageBase64(string $relativePath): ?string
    {
        $cacheKey = 'invoice_image_' . md5($relativePath);
        
        return Cache::remember($cacheKey, 3600, function () use ($relativePath) {
            $fullPath = public_path($relativePath);
            
            if (!file_exists($fullPath) || !is_readable($fullPath)) {
                return null;
            }
            
            return base64_encode(file_get_contents($fullPath));
        });
    }

    protected function determineMimeType(string $path): string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        
        if ($extension === 'jpg') {
            $extension = 'jpeg';
        }
        
        return $extension ?: 'png';
    }
    
    protected function calculateInvoiceAmounts(Invoice $invoice): array
    {
        $subtotal = $invoice->items->sum(fn($item) => $item->price * $item->quantity);
        $tax = 0;
        $discount = 0;
        $downPaymentDue = $invoice->down_payment_due ?? 0;
        $totalDue = $invoice->total;
        
        return [
            'subtotal' => $subtotal,
            'tax' => $tax,
            'discount' => $discount,
            'downPaymentDue' => $downPaymentDue,
            'totalDue' => $totalDue,
        ];
    }
    
    protected function prepareLabels(Invoice $invoice): array
    {
        $transactionLabel = match (true) {
            $invoice->type === InvoiceModel::TYPE_SETTLEMENT => 'Pelunasan',
            !is_null($invoice->down_payment_due) => 'Down Payment',
            default => 'Menunggu Pembayaran',
        };
        
        $paymentStatusLabel = $invoice->status ? ucwords($invoice->status) : 'Menunggu Pembayaran';
        
        return [
            'transactionLabel' => $transactionLabel,
            'paymentStatusLabel' => $paymentStatusLabel,
        ];
    }
}
