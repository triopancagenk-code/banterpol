<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\MaterialMutation;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MaterialController extends Controller
{
    /**
     * Halaman Utama Stok Material (POV Admin & Direktur)
     */
    public function index(Request $request)
    {
        $search = trim($request->input('q', ''));
        $categoryFilter = $request->input('category', 'all');
        $statusFilter = $request->input('status', 'all'); // all, aman, menipis, habis
        $sort = $request->input('sort', 'latest');

        $query = Material::with(['mutations' => function ($q) {
            $q->latest()->take(5);
        }]);

        // Pencarian Teks
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter Kategori
        if ($categoryFilter !== 'all') {
            $query->where('category', $categoryFilter);
        }

        // Filter Status Stok
        if ($statusFilter === 'aman') {
            $query->whereColumn('stock', '>', 'min_stock');
        } elseif ($statusFilter === 'menipis') {
            $query->whereColumn('stock', '<=', 'min_stock')->where('stock', '>', 0);
        } elseif ($statusFilter === 'habis') {
            $query->where('stock', '<=', 0);
        }

        // Pengurutan
        match ($sort) {
            'name_asc' => $query->orderBy('name', 'asc'),
            'name_desc' => $query->orderBy('name', 'desc'),
            'stock_asc' => $query->orderBy('stock', 'asc'),
            'stock_desc' => $query->orderBy('stock', 'desc'),
            'price_asc' => $query->orderBy('unit_price', 'asc'),
            'price_desc' => $query->orderBy('unit_price', 'desc'),
            default => $query->latest('id'),
        };

        $materials = $query->paginate(20)->withQueryString();

        // Statistik KPI Utama (Real-time)
        $stats = [
            'total_items' => Material::count(),
            'total_units' => (int) Material::sum('stock'),
            'low_stock_count' => Material::whereColumn('stock', '<=', 'min_stock')->where('stock', '>', 0)->count(),
            'out_of_stock_count' => Material::where('stock', '<=', 0)->count(),
            'safe_stock_count' => Material::whereColumn('stock', '>', 'min_stock')->count(),
            'total_asset_value' => (float) (Material::selectRaw('SUM(stock * unit_price) as val')->value('val') ?? 0),
            'mutations_today' => MaterialMutation::whereDate('created_at', today())->count(),
        ];

        // Daftar Semua Kategori Unik untuk Dropdown Filter
        $categories = Material::select('category')->distinct()->pluck('category')->filter()->values();

        // 10 Riwayat Mutasi Terkini untuk Quick Feed
        $recentMutations = MaterialMutation::with(['material', 'user'])->latest()->take(15)->get();

        return view('admin.material.index', compact(
            'materials',
            'stats',
            'categories',
            'recentMutations',
            'search',
            'categoryFilter',
            'statusFilter',
            'sort'
        ));
    }

    /**
     * Tambah Master Data Material Baru (Input Manual)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'nullable|string|max:50|unique:materials,code',
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'unit' => 'required|string|max:50',
            'stock' => 'required|integer|min:0',
            'min_stock' => 'required|integer|min:0',
            'unit_price' => 'nullable|numeric|min:0',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ], [
            'code.unique' => 'Kode material tersebut sudah digunakan.',
            'name.required' => 'Nama material wajib diisi.',
            'category.required' => 'Kategori material wajib dipilih.',
            'unit.required' => 'Satuan material wajib diisi.',
            'stock.required' => 'Jumlah stok awal wajib diisi.',
            'min_stock.required' => 'Batas minimum stok wajib diisi.',
        ]);

        if (empty($validated['code'])) {
            $prefix = match (strtoupper(substr($validated['category'], 0, 3))) {
                'MOD', 'ONT' => 'MAT-ONT-',
                'KAB', 'FIB' => 'MAT-KBL-',
                'KON', 'AKS' => 'MAT-FC-',
                'PER' => 'MAT-NET-',
                'TOO' => 'MAT-TLS-',
                default => 'MAT-' . date('Ym') . '-',
            };
            $validated['code'] = $prefix . strtoupper(Str::random(4));
        }

        $validated['unit_price'] = $validated['unit_price'] ?? 0;

        $material = Material::create($validated);

        // Catat mutasi stok awal jika stok > 0
        if ($material->stock > 0) {
            $material->mutations()->create([
                'user_id' => auth()->id(),
                'type' => 'in',
                'amount' => $material->stock,
                'stock_before' => 0,
                'stock_after' => $material->stock,
                'notes' => 'Stok awal pendaftaran material baru',
            ]);
        }

        return redirect()->back()->with('success', "Barang \"{$material->name}\" ({$material->code}) berhasil ditambahkan ke inventaris.");
    }

    /**
     * Perbarui Rincian Data Material
     */
    public function update(Request $request, $id)
    {
        $material = Material::findOrFail($id);

        $validated = $request->validate([
            'code' => "required|string|max:50|unique:materials,code,{$material->id}",
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'unit' => 'required|string|max:50',
            'min_stock' => 'required|integer|min:0',
            'unit_price' => 'nullable|numeric|min:0',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ], [
            'code.unique' => 'Kode barang sudah digunakan oleh barang lain.',
            'name.required' => 'Nama barang wajib diisi.',
            'unit.required' => 'Satuan barang wajib diisi.',
        ]);

        $validated['unit_price'] = $validated['unit_price'] ?? 0;
        $material->update($validated);

        return redirect()->back()->with('success', "Informasi barang \"{$material->name}\" berhasil diperbarui.");
    }

    /**
     * Fitur Tambah Stok Manual (Restock / Barang Masuk)
     */
    public function addStock(Request $request, $id)
    {
        $material = Material::findOrFail($id);

        $request->validate([
            'amount' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:500',
        ], [
            'amount.required' => 'Jumlah penambahan stok wajib diisi.',
            'amount.min' => 'Jumlah penambahan minimal 1.',
        ]);

        $amount = (int) $request->input('amount');
        $notes = $request->input('notes') ?: 'Penambahan stok manual oleh ' . (auth()->user()->name ?? 'Admin/Direktur');

        $mutation = $material->addStock($amount, $notes, auth()->id());

        return redirect()->back()->with('success', "Berhasil menambahkan +{$amount} {$material->unit} ke stok \"{$material->name}\". Total stok saat ini: {$material->stock} {$material->unit}.");
    }

    /**
     * Fitur Kurang Stok Manual (Pengeluaran Barang / Lapangan / Afkir)
     */
    public function reduceStock(Request $request, $id)
    {
        $material = Material::findOrFail($id);

        $request->validate([
            'amount' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:500',
        ], [
            'amount.required' => 'Jumlah pengurangan stok wajib diisi.',
            'amount.min' => 'Jumlah pengurangan minimal 1.',
        ]);

        $amount = (int) $request->input('amount');

        if ($amount > $material->stock) {
            return redirect()->back()->withErrors([
                'amount' => "Jumlah pengurangan ({$amount} {$material->unit}) melebihi stok yang tersedia saat ini ({$material->stock} {$material->unit})."
            ])->with('error', "Gagal mengurangi stok: Jumlah pengurangan ({$amount}) melebihi stok fisik tersedia ({$material->stock}).");
        }

        $notes = $request->input('notes') ?: 'Pengurangan stok manual oleh ' . (auth()->user()->name ?? 'Admin/Direktur');

        $mutation = $material->reduceStock($amount, $notes, auth()->id());

        return redirect()->back()->with('success', "Berhasil mengurangi -{$amount} {$material->unit} dari stok \"{$material->name}\". Sisa stok saat ini: {$material->stock} {$material->unit}.");
    }

    /**
     * Fitur Penyesuaian Stok Opname Manual (Direct Adjustment)
     */
    public function adjustStock(Request $request, $id)
    {
        $material = Material::findOrFail($id);

        $request->validate([
            'new_stock' => 'required|integer|min:0',
            'notes' => 'required|string|max:500',
        ], [
            'new_stock.required' => 'Jumlah stok fisik baru wajib diisi.',
            'new_stock.min' => 'Jumlah stok tidak boleh kurang dari 0.',
            'notes.required' => 'Alasan / keterangan penyesuaian opname wajib diisi.',
        ]);

        $newStock = (int) $request->input('new_stock');
        $oldStock = $material->stock;
        $notes = $request->input('notes');

        $material->adjustStock($newStock, $notes, auth()->id());

        return redirect()->back()->with('success', "Stok \"{$material->name}\" berhasil disesuaikan dari {$oldStock} menjadi {$newStock} {$material->unit}.");
    }

    /**
     * Hapus Material Manual (Delete Single)
     */
    public function destroy($id)
    {
        $material = Material::findOrFail($id);
        $name = $material->name;
        $code = $material->code;

        $material->delete();

        return redirect()->back()->with('success', "Barang \"{$name}\" ({$code}) berhasil dihapus dari sistem inventaris.");
    }

    /**
     * Hapus Material Manual Terpilih (Bulk Delete)
     */
    public function bulkDelete(Request $request)
    {
        $deleteAll = $request->boolean('delete_all', false);

        if ($deleteAll) {
            $count = Material::count();
            MaterialMutation::query()->delete();
            Material::query()->delete();
            return redirect()->back()->with('success', "Seluruh data stok barang ({$count} item) berhasil dikosongkan.");
        }

        $ids = $request->input('ids', []);

        if (empty($ids) || !is_array($ids)) {
            return redirect()->back()->with('error', 'Tidak ada barang yang dipilih untuk dihapus.');
        }

        $count = Material::whereIn('id', $ids)->count();
        MaterialMutation::whereIn('material_id', $ids)->delete();
        Material::whereIn('id', $ids)->delete();

        return redirect()->back()->with('success', "Sebanyak {$count} item barang berhasil dihapus dari sistem.");
    }

    /**
     * Ekspor Data Stok Material ke Excel (.xls Spreadsheet XML)
     */
    public function export(Request $request)
    {
        $search = trim($request->input('q', ''));
        $categoryFilter = $request->input('category', 'all');
        $statusFilter = $request->input('status', 'all');

        $query = Material::with('mutations');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }

        if ($categoryFilter !== 'all') {
            $query->where('category', $categoryFilter);
        }

        if ($statusFilter === 'aman') {
            $query->whereColumn('stock', '>', 'min_stock');
        } elseif ($statusFilter === 'menipis') {
            $query->whereColumn('stock', '<=', 'min_stock')->where('stock', '>', 0);
        } elseif ($statusFilter === 'habis') {
            $query->where('stock', '<=', 0);
        }

        $materials = $query->orderBy('category', 'asc')->orderBy('name', 'asc')->get();
        $filename = 'Stok_Barang_Banterpool_' . date('Ymd_His') . '.xls';

        return response()->streamDownload(function () use ($materials, $categoryFilter, $statusFilter, $search) {
            echo "\xEF\xBB\xBF"; // UTF-8 BOM
            echo view('admin.exports.material_excel', compact('materials', 'categoryFilter', 'statusFilter', 'search'))->render();
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'max-age=0, no-cache, must-revalidate, proxy-revalidate',
        ]);
    }

    /**
     * Riwayat Mutasi Stok (Log Mutasi Lengkap)
     */
    public function mutations(Request $request)
    {
        $materialId = $request->input('material_id');
        $type = $request->input('type', 'all');

        $query = MaterialMutation::with(['material', 'user'])->latest();

        if (!empty($materialId)) {
            $query->where('material_id', $materialId);
        }

        if ($type !== 'all') {
            $query->where('type', $type);
        }

        $mutations = $query->paginate(30)->withQueryString();
        $materials = Material::select('id', 'name', 'code', 'unit')->orderBy('name')->get();

        return view('admin.material.mutations', compact('mutations', 'materials', 'materialId', 'type'));
    }
}
