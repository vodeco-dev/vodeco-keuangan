<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class VodecoWebsiteService
{
    public function getCompanyInfo(): array
    {
        return Cache::remember('vodeco_company_info', 3600, function () {
            try {
                $response = Http::timeout(10)
                    ->withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    ])
                    ->get('https://vodeco.co.id');
                
                if ($response->successful()) {
                    $html = $response->body();
                    
                    $companyInfo = [
                        'name' => $this->extractCompanyName($html),
                        'tagline' => $this->extractCompanyTagline($html),
                        'address' => $this->extractAddress($html),
                        'email' => $this->extractEmail($html),
                        'phone' => $this->extractPhone($html),
                        'website' => 'https://vodeco.co.id',
                    ];
                    
                    if ($companyInfo['name'] !== 'CV. Vodeco Digital Mediatama' || 
                        $companyInfo['email'] !== 'hello@vodeco.co.id') {
                        return $companyInfo;
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Failed to fetch company info from vodeco.co.id: ' . $e->getMessage());
            }
            
            return $this->getDefaultCompanyInfo();
        });
    }
    
    public function getPaymentMethods(): array
    {
        return Cache::remember('vodeco_payment_methods', 3600, function () {
            try {
                $response = Http::timeout(10)
                    ->withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    ])
                    ->get('https://vodeco.co.id/cara-pembayaran');
                
                if ($response->successful()) {
                    $html = $response->body();
                    
                    $bcaInfo = $this->extractBCAInfo($html);
                    $mandiriInfo = $this->extractMandiriInfo($html);
                    $instructions = $this->extractPaymentInstructions($html);
                    
                    $paymentMethods = [
                        'bca' => $bcaInfo,
                        'mandiri' => $mandiriInfo,
                        'instructions' => $instructions,
                    ];
                    
                    if ($bcaInfo['account_number'] !== '3624500500' || 
                        $mandiriInfo['account_number'] !== '1390001188113' ||
                        $instructions !== 'Payment should be made within 30 days by Cheque. Paypal, WesternUnion, Payoneer, MasterCard') {
                        return $paymentMethods;
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Failed to fetch payment methods from vodeco.co.id/cara-pembayaran: ' . $e->getMessage());
            }
            
            return $this->getDefaultPaymentMethods();
        });
    }
    
    private function extractCompanyName(string $html): string
    {
        if (preg_match('/<title[^>]*>([^<]+)<\/title>/i', $html, $matches)) {
            $title = strip_tags($matches[1]);
            if (stripos($title, 'Vodeco') !== false) {
                return 'CV. Vodeco Digital Mediatama';
            }
        }
        
        if (preg_match('/<h1[^>]*>([^<]+)<\/h1>/i', $html, $matches)) {
            return strip_tags($matches[1]);
        }
        
        return 'CV. Vodeco Digital Mediatama';
    }
    
    private function extractCompanyTagline(string $html): string
    {
        if (preg_match('/<meta[^>]*name=["\']description["\'][^>]*content=["\']([^"\']+)["\']/i', $html, $matches)) {
            $tagline = strip_tags($matches[1]);
            if (stripos($tagline, 'Jasa pembuatan website') !== false || 
                stripos($tagline, 'digital marketing') !== false) {
                return "Let's Up Your Brand";
            }
            return $tagline;
        }
        
        return "Let's Up Your Brand";
    }
    
    private function extractAddress(string $html): string
    {
        if (preg_match('/Bandung[^<]*/i', $html, $matches)) {
            return 'Bandung, Indonesia';
        }
        
        return 'Bandung, Indonesia';
    }
    
    private function extractEmail(string $html): string
    {
        if (preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $html, $matches)) {
            return $matches[0];
        }
        
        return 'hello@vodeco.co.id';
    }
    
    private function extractPhone(string $html): string
    {
        if (preg_match('/(\+62|0)[\d\s-]{10,}/', $html, $matches)) {
            return trim($matches[0]);
        }
        
        return '+62 878-7046-1427';
    }
    
    private function extractBCAInfo(string $html): array
    {
        $bcaInfo = [
            'name' => 'BCA',
            'account_number' => '3624500500',
            'account_name' => 'CV. Vodeco Digital Mediatama',
        ];
        
        if (preg_match('/BCA[^<]*(\d{10,})/i', $html, $matches)) {
            $bcaInfo['account_number'] = preg_replace('/\D/', '', $matches[1]);
        }
        
        return $bcaInfo;
    }
    
    private function extractMandiriInfo(string $html): array
    {
        $mandiriInfo = [
            'name' => 'MANDIRI',
            'account_number' => '1390001188113',
            'account_name' => 'CV. Vodeco Digital Mediatama',
        ];
        
        if (preg_match('/Mandiri[^<]*(\d{10,})/i', $html, $matches)) {
            $mandiriInfo['account_number'] = preg_replace('/\D/', '', $matches[1]);
        }
        
        return $mandiriInfo;
    }
    
    private function extractPaymentInstructions(string $html): string
    {
        if (preg_match('/<p[^>]*class=["\'][^"\']*payment[^"\']*["\'][^>]*>([^<]+)<\/p>/i', $html, $matches)) {
            return strip_tags($matches[1]);
        }
        
        return 'Payment should be made within 30 days by Cheque. Paypal, WesternUnion, Payoneer, MasterCard';
    }
    
    private function getDefaultCompanyInfo(): array
    {
        return [
            'name' => 'CV. Vodeco Digital Mediatama',
            'tagline' => "Let's Up Your Brand",
            'address' => 'Bandung, Indonesia',
            'email' => 'hello@vodeco.co.id',
            'phone' => '+62 878-7046-1427',
            'website' => 'https://vodeco.co.id',
        ];
    }
    
    private function getDefaultPaymentMethods(): array
    {
        return [
            'bca' => [
                'name' => 'BCA',
                'account_number' => '3624500500',
                'account_name' => 'CV. Vodeco Digital Mediatama',
            ],
            'mandiri' => [
                'name' => 'MANDIRI',
                'account_number' => '1390001188113',
                'account_name' => 'CV. Vodeco Digital Mediatama',
            ],
            'instructions' => 'Payment should be made within 30 days by Cheque. Paypal, WesternUnion, Payoneer, MasterCard',
        ];
    }
}

