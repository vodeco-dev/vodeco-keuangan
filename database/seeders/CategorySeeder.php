<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    public function run(): void
    {

        $categories = [
            ['name' => 'Penjualan Website', 'type' => 'pemasukan'],
            ['name' => 'Penjualan Compro', 'type' => 'pemasukan'],
            ['name' => 'Penjualan SEO', 'type' => 'pemasukan'],
            ['name' => 'Penjualan Iklan', 'type' => 'pemasukan'],
            ['name' => 'Penjualan Lainnya', 'type' => 'pemasukan'],
            ['name' => 'Penjualan Renewal Website', 'type' => 'pemasukan'],
            ['name' => 'Penjualan Renewal SEO', 'type' => 'pemasukan'],
            ['name' => 'Penjualan Renewal Iklan', 'type' => 'pemasukan'],
            ['name' => 'Penjualan Renewal Lainnya', 'type' => 'pemasukan'],

            ['name' => 'Gaji Tim Produksi', 'type' => 'pengeluaran'],
            ['name' => 'Registrasi Domain', 'type' => 'pengeluaran'],
            ['name' => 'Registrasi Hosting', 'type' => 'pengeluaran'],
            ['name' => 'Biaya Domain (vod)', 'type' => 'pengeluaran'],
            ['name' => 'Biaya Hosting (vod)', 'type' => 'pengeluaran'],
            ['name' => 'Saldo Iklan Klien', 'type' => 'pengeluaran'],
            ['name' => 'HPP Lainnya', 'type' => 'pengeluaran'],
            ['name' => 'Perpanjangan Domain', 'type' => 'pengeluaran'],
            ['name' => 'Perpanjangan Hosting', 'type' => 'pengeluaran'],

            ['name' => 'Biaya iklan Vodeco', 'type' => 'pengeluaran'],
            ['name' => 'Biaya listrik kantor', 'type' => 'pengeluaran'],
            ['name' => 'Biaya gaji karyawan lainnya', 'type' => 'pengeluaran'],
            ['name' => 'Biaya kuota internet', 'type' => 'pengeluaran'],
            ['name' => 'Biaya wifi lantai bawah', 'type' => 'pengeluaran'],
            ['name' => 'Biaya wifi lantai atas', 'type' => 'pengeluaran'],
            ['name' => 'Biaya konsumsi', 'type' => 'pengeluaran'],
            ['name' => 'Biaya langganan', 'type' => 'pengeluaran'],
            ['name' => 'Biaya perlengkapan kantor', 'type' => 'pengeluaran'],
            ['name' => 'Biaya lainnya', 'type' => 'pengeluaran'],
            ['name' => 'Biaya pemeliharaan dan perbaikan', 'type' => 'pengeluaran'],
            ['name' => 'Piutang tidak tertagih', 'type' => 'pengeluaran'],

            ['name' => 'Bunga bank', 'type' => 'pemasukan'],
            ['name' => 'Punishment Internal', 'type' => 'pemasukan'],
            ['name' => 'PLU Lainnya', 'type' => 'pemasukan'],

            ['name' => 'PPh pasal 23 (PPh jasa dan barang)', 'type' => 'pengeluaran'],
            ['name' => 'Biaya adm bank', 'type' => 'pengeluaran'],
            ['name' => 'Pajak bank', 'type' => 'pengeluaran'],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(
                ['name' => $category['name']],
                ['type' => $category['type']]
            );
        }
    }
}
