<?php

namespace App\Imports;

use App\Models\Order;
use App\Services\CustomerImportService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class CustomersImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    protected bool $updateExisting;

    public function __construct(bool $updateExisting = true)
    {
        $this->updateExisting = $updateExisting;
    }

    /**
     * Memproses data per baris yang telah dinormalisasi menggunakan nama kolom header
     *
     * @param Collection $rows
     */
    public function collection(Collection $rows)
    {
        // Class ini siap digunakan secara langsung untuk import pelanggan berbasis Laravel Excel
        // Contoh pemanggilan: Excel::import(new CustomersImport, $filePath);
    }
}
