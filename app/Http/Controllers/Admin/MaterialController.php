<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\MaterialMonthlyStock;
use App\Models\MaterialMutation;
use App\Services\CustomerImportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MaterialController extends Controller
{
    /**
     * Daftar Nama Bulan dalam Bahasa Indonesia
     */
    public const MONTHS = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    /**
     * Halaman Utama Stok Material (POV Admin & Direktur)
     * Mendukung pilihan periode bulanan untuk Laporan Stok, Barang Masuk, Barang Keluar, dan Tampilan Berdampingan
     */
    public function index(Request $request)
    {
        $tab = $request->input('tab', 'laporan'); // laporan, masuk, keluar, spreadsheet
        $search = trim($request->input('q', $request->input('search', '')));
        $categoryFilter = $request->input('category', 'all');
        $statusFilter = $request->input('status', 'all'); // all, aman, menipis, habis, minus
        $sort = $request->input('sort', !empty($search) ? 'name_asc' : 'code_asc');

        // Dukungan AJAX Live Autocomplete Suggestion (Pencarian Berdasarkan Awalan / Inisial Abjad a-z)
        if ($request->ajax() || $request->wantsJson() || $request->has('ajax')) {
            $search = trim($request->input('q', ''));
            if (empty($search)) {
                return response()->json([]);
            }
            $lowerSearch = strtolower($search);
            $upperSearch = strtoupper($search);

            $results = Material::where(function ($q) use ($search, $lowerSearch, $upperSearch) {
                $q->where('name', 'like', "{$search}%")
                  ->orWhere('name', 'like', "{$lowerSearch}%")
                  ->orWhere('name', 'like', "{$upperSearch}%")
                  ->orWhere('name', 'like', " {$search}%")
                  ->orWhere('name', 'like', " {$lowerSearch}%")
                  ->orWhere('name', 'like', " {$upperSearch}%")
                  ->orWhere('code', 'like', "{$upperSearch}%")
                  ->orWhere('code', 'like', "{$search}%");

                if (strlen($search) > 2) {
                    $q->orWhere('name', 'like', "% {$search}%")
                      ->orWhere('name', 'like', "%{$search}%")
                      ->orWhere('category', 'like', "{$search}%");
                }
            })
            ->orderBy('name', 'asc')
            ->limit(10)
            ->get(['id', 'code', 'name', 'unit', 'stock', 'category']);

            return response()->json($results);
        }

        // Pilihan Periode Bulanan & Tahunan (Default: Bulan 10 / Oktober Tahun 2026)
        $monthInput = $request->input('month', '10');
        $yearInput = $request->input('year', '2026');

        $selectedMonth = $monthInput === 'all' ? 'all' : (int) $monthInput;
        $selectedYear = $yearInput === 'all' ? 'all' : (int) $yearInput;

        $isMonthly = $selectedMonth !== 'all';
        $isYearly = $selectedYear !== 'all';
        $monthName = $isMonthly ? (self::MONTHS[$selectedMonth] ?? 'Oktober') : 'Semua Periode';

        if ($isMonthly) {
            $yearForDate = $isYearly ? $selectedYear : 2026;
            $startDate = Carbon::createFromDate($yearForDate, $selectedMonth, 1)->startOfMonth()->toDateString();
            $endDate = Carbon::createFromDate($yearForDate, $selectedMonth, 1)->endOfMonth()->toDateString();
            $tglAwal = Carbon::parse($startDate)->format('d/m/Y');
            $tglClosing = Carbon::parse($endDate)->format('d/m/Y');

            // Navigasi Cepat Bulan Sebelumnya & Berikutnya
            $currDate = Carbon::createFromDate($yearForDate, $selectedMonth, 1);
            $prevDate = $currDate->copy()->subMonth();
            $nextDate = $currDate->copy()->addMonth();
            $prevMonth = $prevDate->month;
            $prevYear = $prevDate->year;
            $nextMonth = $nextDate->month;
            $nextYear = $nextDate->year;
        } else {
            $startDate = null;
            $endDate = null;
            $tglAwal = 'Awal Sistem';
            $tglClosing = date('d/m/Y');
            $prevMonth = 9;
            $prevYear = 2026;
            $nextMonth = 11;
            $nextYear = 2026;
        }

        if ($isMonthly) {
            // Query Master Material dengan kalkulasi periode bulanan
            $query = Material::query();

            $query->select('materials.*')
                ->selectSub(function ($q) use ($startDate) {
                    $q->from('material_mutations')
                      ->whereColumn('material_mutations.material_id', 'materials.id')
                      ->where('type', 'in')
                      ->where(function ($sub) use ($startDate) {
                          $sub->where('transaction_date', '<', $startDate)
                              ->orWhere(function ($s) use ($startDate) {
                                  $s->whereNull('transaction_date')->whereDate('created_at', '<', $startDate);
                              });
                      })
                      ->selectRaw('COALESCE(SUM(amount), 0)');
                }, 'in_before')
                ->selectSub(function ($q) use ($startDate) {
                    $q->from('material_mutations')
                      ->whereColumn('material_mutations.material_id', 'materials.id')
                      ->where('type', 'out')
                      ->where(function ($sub) use ($startDate) {
                          $sub->where('transaction_date', '<', $startDate)
                              ->orWhere(function ($s) use ($startDate) {
                                  $s->whereNull('transaction_date')->whereDate('created_at', '<', $startDate);
                              });
                      })
                      ->selectRaw('COALESCE(SUM(amount), 0)');
                }, 'out_before')
                ->selectSub(function ($q) use ($startDate, $endDate) {
                    $q->from('material_mutations')
                      ->whereColumn('material_mutations.material_id', 'materials.id')
                      ->where('type', 'in')
                      ->where(function ($sub) use ($startDate, $endDate) {
                          $sub->whereBetween('transaction_date', [$startDate, $endDate])
                              ->orWhere(function ($s) use ($startDate, $endDate) {
                                  $s->whereNull('transaction_date')->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate]);
                              });
                      })
                      ->selectRaw('COALESCE(SUM(amount), 0)');
                }, 'period_in')
                ->selectSub(function ($q) use ($startDate, $endDate) {
                    $q->from('material_mutations')
                      ->whereColumn('material_mutations.material_id', 'materials.id')
                      ->where('type', 'out')
                      ->where(function ($sub) use ($startDate, $endDate) {
                          $sub->whereBetween('transaction_date', [$startDate, $endDate])
                              ->orWhere(function ($s) use ($startDate, $endDate) {
                                  $s->whereNull('transaction_date')->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate]);
                              });
                      })
                      ->selectRaw('COALESCE(SUM(amount), 0)');
                }, 'period_out');

            // Pencarian Teks (Inisial Awalan A-Z, Nama, Kode, Kategori, Bulan, Lokasi)
            if (!empty($search)) {
                $trimmedSearch = trim($search);
                $lowerSearch = strtolower($trimmedSearch);
                $upperSearch = strtoupper($trimmedSearch);

                $query->where(function ($q) use ($trimmedSearch, $lowerSearch, $upperSearch) {
                    $q->where('name', 'like', "{$trimmedSearch}%")
                      ->orWhere('name', 'like', "{$lowerSearch}%")
                      ->orWhere('name', 'like', "{$upperSearch}%")
                      ->orWhere('name', 'like', " {$trimmedSearch}%")
                      ->orWhere('name', 'like', " {$lowerSearch}%")
                      ->orWhere('name', 'like', " {$upperSearch}%")
                      ->orWhere('code', 'like', "{$upperSearch}%")
                      ->orWhere('code', 'like', "{$trimmedSearch}%");

                    if (strlen($trimmedSearch) > 1) {
                        $q->orWhere('name', 'like', "% {$trimmedSearch}%")
                          ->orWhere('name', 'like', "%{$trimmedSearch}%")
                          ->orWhere('category', 'like', "%{$trimmedSearch}%")
                          ->orWhere('month', 'like', "%{$trimmedSearch}%")
                          ->orWhere('location', 'like', "%{$trimmedSearch}%")
                          ->orWhere('description', 'like', "%{$trimmedSearch}%");
                    }
                });
            }

            // Filter Kategori
            if ($categoryFilter !== 'all') {
                $query->where('category', $categoryFilter);
            }

            // Filter Bulan (Tampilkan seluruh material yang memiliki data stok, mutasi, atau terdaftar pada periode terpilih)
            $query->where(function ($q) use ($selectedMonth, $selectedYear, $monthName, $startDate, $endDate, $isYearly) {
                $q->whereHas('monthlyStocks', function ($sq) use ($selectedMonth, $selectedYear, $isYearly) {
                    $sq->where('month', $selectedMonth);
                    if ($isYearly) {
                        $sq->where('year', $selectedYear);
                    }
                })
                ->orWhereHas('mutations', function ($sq) use ($startDate, $endDate) {
                    $sq->whereBetween('transaction_date', [$startDate, $endDate])
                       ->orWhere(function ($sub) use ($startDate, $endDate) {
                           $sub->whereNull('transaction_date')->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate]);
                       });
                })
                ->orWhere(function ($sq) use ($selectedMonth, $selectedYear, $monthName, $isYearly) {
                    $sq->where(function ($s) use ($selectedMonth, $monthName) {
                        $s->where('month', $monthName)
                          ->orWhere('month', (string)$selectedMonth)
                          ->orWhere('month', 'like', "%{$monthName}%");
                    });
                    if ($isYearly) {
                        $sq->where(function ($s) use ($selectedYear) {
                            $s->where('year', $selectedYear)->orWhereNull('year');
                        });
                    }
                });
            });

            // Eager load monthlyStocks untuk periode terpilih
            $query->with(['monthlyStocks' => function ($q) use ($selectedMonth, $selectedYear, $isYearly) {
                $q->where('month', $selectedMonth);
                if ($isYearly) {
                    $q->where('year', $selectedYear);
                }
            }]);

            // Ambil data materials dan hitung nilai dinamis untuk periode terpilih
            $allItems = $query->get()->map(function ($m) use ($selectedMonth, $selectedYear, $isYearly, $monthName) {
                $monthly = $m->monthlyStocks->first(function ($ms) use ($selectedMonth, $selectedYear, $isYearly) {
                    return (int)$ms->month === (int)$selectedMonth && (!$isYearly || (int)$ms->year === (int)$selectedYear);
                });

                if ($monthly) {
                    $displayIn = max((float)$monthly->total_in, (float)($m->period_in ?? 0));
                    $displayOut = max((float)$monthly->total_out, (float)($m->period_out ?? 0));
                    $displayAwal = (float)$monthly->initial_stock;
                    $displayAkhir = $displayAwal + $displayIn - $displayOut;
                    $displayMonthName = $monthly->month_name ?: $monthName;
                } else {
                    $displayIn = (float) ($m->period_in ?? 0);
                    $displayOut = (float) ($m->period_out ?? 0);
                    $displayAwal = (float) $m->initial_stock + (float) ($m->in_before ?? 0) - (float) ($m->out_before ?? 0);
                    $displayAkhir = $displayAwal + $displayIn - $displayOut;
                    $displayMonthName = !empty($m->month) ? $m->month : $monthName;
                }

                $m->computed_initial_stock = $displayAwal;
                $m->computed_total_in = $displayIn;
                $m->computed_total_out = $displayOut;
                $m->computed_stock = $displayAkhir;
                $m->display_month = $displayMonthName;
                return $m;
            });
        } else {
            // Semua Periode: Tampilkan seluruh rekaman laporan stok bulanan (Januari s.d. Oktober)
            $monthlyQuery = MaterialMonthlyStock::with('material');

            if ($isYearly) {
                $monthlyQuery->where(function ($q) use ($selectedYear) {
                    $q->where('year', $selectedYear)->orWhereNull('year');
                });
            }

            if (!empty($search)) {
                $trimmedSearch = trim($search);
                $lowerSearch = strtolower($trimmedSearch);
                $upperSearch = strtoupper($trimmedSearch);

                $monthlyQuery->where(function ($q) use ($trimmedSearch, $lowerSearch, $upperSearch) {
                    $q->where('month_name', 'like', "%{$trimmedSearch}%")
                      ->orWhereHas('material', function ($mq) use ($trimmedSearch, $lowerSearch, $upperSearch) {
                          $mq->where('name', 'like', "{$trimmedSearch}%")
                             ->orWhere('name', 'like', "{$lowerSearch}%")
                             ->orWhere('name', 'like', "{$upperSearch}%")
                             ->orWhere('name', 'like', " {$trimmedSearch}%")
                             ->orWhere('name', 'like', " {$lowerSearch}%")
                             ->orWhere('name', 'like', " {$upperSearch}%")
                             ->orWhere('code', 'like', "{$upperSearch}%")
                             ->orWhere('code', 'like', "{$trimmedSearch}%");

                          if (strlen($trimmedSearch) > 1) {
                              $mq->orWhere('name', 'like', "% {$trimmedSearch}%")
                                 ->orWhere('name', 'like', "%{$trimmedSearch}%")
                                 ->orWhere('category', 'like', "%{$trimmedSearch}%")
                                 ->orWhere('location', 'like', "%{$trimmedSearch}%")
                                 ->orWhere('description', 'like', "%{$trimmedSearch}%");
                          }
                      });
                });
            }

            if ($categoryFilter !== 'all') {
                $monthlyQuery->whereHas('material', fn($mq) => $mq->where('category', $categoryFilter));
            }

            $monthlyRecords = $monthlyQuery->orderBy('year', 'desc')->orderBy('month', 'asc')->get();

            $allItems = $monthlyRecords->map(function ($ms) {
                $mat = $ms->material ?: new Material();
                $item = clone $mat;
                $item->id = $mat->id ?? $ms->id;
                $item->monthly_stock_id = $ms->id;
                $item->computed_initial_stock = (float) $ms->initial_stock;
                $item->computed_total_in = (float) $ms->total_in;
                $item->computed_total_out = (float) $ms->total_out;
                $item->computed_stock = (float) $ms->final_stock;
                $item->display_month = $ms->month_name ?: (self::MONTHS[$ms->month] ?? 'Semua Periode');
                $item->month = $item->display_month;
                $item->year = $ms->year;
                return $item;
            });

            // Sertakan juga material yatim (yang belum memiliki rekaman di monthlyStocks) agar tidak hilang
            $existingMatIds = $monthlyRecords->pluck('material_id')->unique()->filter();
            $orphansQuery = Material::whereNotIn('id', $existingMatIds);
            if ($categoryFilter !== 'all') {
                $orphansQuery->where('category', $categoryFilter);
            }
            if (!empty($search)) {
                $trimmedSearch = trim($search);
                $lowerSearch = strtolower($trimmedSearch);
                $upperSearch = strtoupper($trimmedSearch);

                $orphansQuery->where(function ($q) use ($trimmedSearch, $lowerSearch, $upperSearch) {
                    $q->where('name', 'like', "{$trimmedSearch}%")
                      ->orWhere('name', 'like', "{$lowerSearch}%")
                      ->orWhere('name', 'like', "{$upperSearch}%")
                      ->orWhere('name', 'like', " {$trimmedSearch}%")
                      ->orWhere('name', 'like', " {$lowerSearch}%")
                      ->orWhere('name', 'like', " {$upperSearch}%")
                      ->orWhere('code', 'like', "{$upperSearch}%")
                      ->orWhere('code', 'like', "{$trimmedSearch}%");

                    if (strlen($trimmedSearch) > 1) {
                        $q->orWhere('name', 'like', "% {$trimmedSearch}%")
                          ->orWhere('name', 'like', "%{$trimmedSearch}%")
                          ->orWhere('category', 'like', "%{$trimmedSearch}%")
                          ->orWhere('location', 'like', "%{$trimmedSearch}%")
                          ->orWhere('description', 'like', "%{$trimmedSearch}%");
                    }
                });
            }
            $orphanItems = $orphansQuery->get()->map(function ($m) {
                $m->computed_initial_stock = (float) $m->initial_stock;
                $m->computed_total_in = (float) $m->total_in;
                $m->computed_total_out = (float) $m->total_out;
                $m->computed_stock = (float) $m->stock;
                $m->display_month = !empty($m->month) ? $m->month : 'Semua Periode';
                return $m;
            });

            $allItems = $allItems->concat($orphanItems);
        }

        // Hitung status counts secara akurat berdasarkan tabel pada periode terpilih
        $safeCount = 0;
        $lowCount = 0;
        $outCount = 0;
        $negCount = 0;

        foreach ($allItems as $m) {
            $stk = (float) $m->computed_stock;
            $minStk = (float) $m->min_stock;
            if ($stk < 0) {
                $negCount++;
            } elseif ($stk == 0.0) {
                $outCount++;
            } elseif ($stk <= $minStk) {
                $lowCount++;
            } else {
                $safeCount++;
            }
        }

        // Filter status jika diminta (Hanya menampilkan isi status yang diinginkan saja)
        $filteredItems = $allItems;
        if ($statusFilter !== 'all') {
            $filteredItems = $filteredItems->filter(function ($m) use ($statusFilter) {
                return match ($statusFilter) {
                    'aman' => $m->computed_stock > $m->min_stock,
                    'menipis' => $m->computed_stock <= $m->min_stock && $m->computed_stock > 0,
                    'habis' => (float) $m->computed_stock == 0.0,
                    'minus' => (float) $m->computed_stock < 0.0,
                    default => true,
                };
            })->values();
        }

        // Sortir / Pengurutan Akurat
        $sortedItems = match ($sort) {
            'code_asc' => $filteredItems->sortBy('code', SORT_NATURAL)->values(),
            'code_desc' => $filteredItems->sortByDesc('code', SORT_NATURAL)->values(),
            'name_asc' => $filteredItems->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values(),
            'name_desc' => $filteredItems->sortByDesc('name', SORT_NATURAL | SORT_FLAG_CASE)->values(),
            'category_asc' => $filteredItems->sortBy('category', SORT_NATURAL | SORT_FLAG_CASE)->values(),
            'category_desc' => $filteredItems->sortByDesc('category', SORT_NATURAL | SORT_FLAG_CASE)->values(),
            'unit_asc' => $filteredItems->sortBy('unit', SORT_NATURAL | SORT_FLAG_CASE)->values(),
            'unit_desc' => $filteredItems->sortByDesc('unit', SORT_NATURAL | SORT_FLAG_CASE)->values(),
            'month_asc' => $filteredItems->sortBy('display_month', SORT_NATURAL | SORT_FLAG_CASE)->values(),
            'month_desc' => $filteredItems->sortByDesc('display_month', SORT_NATURAL | SORT_FLAG_CASE)->values(),
            'initial_desc' => $filteredItems->sortByDesc('computed_initial_stock')->values(),
            'initial_asc' => $filteredItems->sortBy('computed_initial_stock')->values(),
            'in_desc' => $filteredItems->sortByDesc('computed_total_in')->values(),
            'in_asc' => $filteredItems->sortBy('computed_total_in')->values(),
            'out_desc' => $filteredItems->sortByDesc('computed_total_out')->values(),
            'out_asc' => $filteredItems->sortBy('computed_total_out')->values(),
            'stock_desc' => $filteredItems->sortByDesc('computed_stock')->values(),
            'stock_asc' => $filteredItems->sortBy('computed_stock')->values(),
            'status_asc' => $filteredItems->sortBy(function ($m) {
                if ($m->computed_stock > $m->min_stock) return 1;
                if ($m->computed_stock > 0 && $m->computed_stock <= $m->min_stock) return 2;
                if ($m->computed_stock == 0) return 3;
                return 4;
            })->values(),
            'status_desc' => $filteredItems->sortBy(function ($m) {
                if ($m->computed_stock < 0) return 1;
                if ($m->computed_stock == 0) return 2;
                if ($m->computed_stock <= $m->min_stock) return 3;
                return 4;
            })->values(),
            default => $filteredItems->sortBy('code', SORT_NATURAL)->values(),
        };

        // Paginate secara akurat sesuai data yang difilter
        $page = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 35;
        $currentSlice = $sortedItems->slice(($page - 1) * $perPage, $perPage)->values();
        $materials = new LengthAwarePaginator($currentSlice, $sortedItems->count(), $perPage, $page, [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
            'query' => $request->query(),
        ]);

        // Query Tab Barang Masuk (Difilter per bulan terpilih)
        $incomingQuery = MaterialMutation::where('type', 'in')->with(['material', 'user']);
        if ($isMonthly) {
            $incomingQuery->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('transaction_date', [$startDate, $endDate])
                  ->orWhere(function ($s) use ($startDate, $endDate) {
                      $s->whereNull('transaction_date')->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate]);
                  });
            });
        }
        if (!empty($search)) {
            $trimmedSearch = trim($search);
            $lowerSearch = strtolower($trimmedSearch);
            $upperSearch = strtoupper($trimmedSearch);

            $incomingQuery->where(function ($q) use ($trimmedSearch, $lowerSearch, $upperSearch) {
                $q->where('notes', 'like', "%{$trimmedSearch}%")
                  ->orWhereHas('material', function ($mq) use ($trimmedSearch, $lowerSearch, $upperSearch) {
                      $mq->where('name', 'like', "{$trimmedSearch}%")
                         ->orWhere('name', 'like', "{$lowerSearch}%")
                         ->orWhere('name', 'like', "{$upperSearch}%")
                         ->orWhere('name', 'like', " {$trimmedSearch}%")
                         ->orWhere('code', 'like', "{$upperSearch}%")
                         ->orWhere('code', 'like', "{$trimmedSearch}%")
                         ->orWhere('name', 'like', "%{$trimmedSearch}%");
                  });
            });
        }
        $incomingMutations = $incomingQuery->orderBy('transaction_date', 'desc')->latest('id')->paginate(30, ['*'], 'page_in')->withQueryString();

        // Query Tab Barang Keluar (Difilter per bulan terpilih)
        $outgoingQuery = MaterialMutation::where('type', 'out')->with(['material', 'user']);
        if ($isMonthly) {
            $outgoingQuery->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('transaction_date', [$startDate, $endDate])
                  ->orWhere(function ($s) use ($startDate, $endDate) {
                      $s->whereNull('transaction_date')->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate]);
                  });
            });
        }
        if (!empty($search)) {
            $trimmedSearch = trim($search);
            $lowerSearch = strtolower($trimmedSearch);
            $upperSearch = strtoupper($trimmedSearch);

            $outgoingQuery->where(function ($q) use ($trimmedSearch, $lowerSearch, $upperSearch) {
                $q->where('notes', 'like', "%{$trimmedSearch}%")
                  ->orWhereHas('material', function ($mq) use ($trimmedSearch, $lowerSearch, $upperSearch) {
                      $mq->where('name', 'like', "{$trimmedSearch}%")
                         ->orWhere('name', 'like', "{$lowerSearch}%")
                         ->orWhere('name', 'like', "{$upperSearch}%")
                         ->orWhere('name', 'like', " {$trimmedSearch}%")
                         ->orWhere('code', 'like', "{$upperSearch}%")
                         ->orWhere('code', 'like', "{$trimmedSearch}%")
                         ->orWhere('name', 'like', "%{$trimmedSearch}%");
                  });
            });
        }
        $outgoingMutations = $outgoingQuery->orderBy('transaction_date', 'desc')->latest('id')->paginate(30, ['*'], 'page_out')->withQueryString();

        // Data untuk Tab Tampilan Berdampingan (Spreadsheet CSV View difilter per bulan)
        $spreadsheetMaterials = [];
        $spreadsheetIncoming = [];
        $spreadsheetOutgoing = [];
        if ($tab === 'spreadsheet') {
            if ($isMonthly) {
                $spreadsheetMaterials = $this->getMonthlyCalculatedMaterials($startDate, $endDate, $selectedMonth, $selectedYear);
            } else {
                $spreadsheetMaterials = $allItems;
            }

            $spreadInQuery = MaterialMutation::where('type', 'in')->with('material')->orderBy('transaction_date', 'asc');
            $spreadOutQuery = MaterialMutation::where('type', 'out')->with('material')->orderBy('transaction_date', 'asc');
            if ($isMonthly) {
                $spreadInQuery->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('transaction_date', [$startDate, $endDate])
                      ->orWhere(function ($s) use ($startDate, $endDate) {
                          $s->whereNull('transaction_date')->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate]);
                      });
                });
                $spreadOutQuery->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('transaction_date', [$startDate, $endDate])
                      ->orWhere(function ($s) use ($startDate, $endDate) {
                          $s->whereNull('transaction_date')->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate]);
                      });
                });
            }
            $spreadsheetIncoming = $spreadInQuery->get();
            $spreadsheetOutgoing = $spreadOutQuery->get();
        }

        // Statistik KPI Utama Periode Terpilih
        $kpiInCount = $isMonthly
            ? MaterialMutation::where('type', 'in')->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('transaction_date', [$startDate, $endDate])
                  ->orWhere(function ($s) use ($startDate, $endDate) {
                      $s->whereNull('transaction_date')->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate]);
                  });
            })->count()
            : MaterialMutation::where('type', 'in')->count();

        $kpiOutCount = $isMonthly
            ? MaterialMutation::where('type', 'out')->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('transaction_date', [$startDate, $endDate])
                  ->orWhere(function ($s) use ($startDate, $endDate) {
                      $s->whereNull('transaction_date')->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate]);
                  });
            })->count()
            : MaterialMutation::where('type', 'out')->count();

        if ($isMonthly) {
            $kpiTotalIn = (float) MaterialMutation::where('type', 'in')->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('transaction_date', [$startDate, $endDate])
                  ->orWhere(function ($s) use ($startDate, $endDate) {
                      $s->whereNull('transaction_date')->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate]);
                  });
            })->sum('amount');
            $mInSumQuery = MaterialMonthlyStock::where('month', $selectedMonth);
            if ($isYearly) {
                $mInSumQuery->where('year', $selectedYear);
            }
            $kpiTotalIn = max($kpiTotalIn, (float) $mInSumQuery->sum('total_in'));
        } else {
            $mutInSum = (float) MaterialMutation::where('type', 'in')->sum('amount');
            $stockInSumQuery = MaterialMonthlyStock::query();
            if ($isYearly) {
                $stockInSumQuery->where(function ($q) use ($selectedYear) {
                    $q->where('year', $selectedYear)->orWhereNull('year');
                });
            }
            $kpiTotalIn = max($mutInSum, (float) $stockInSumQuery->sum('total_in'), (float) Material::sum('total_in'));
        }

        if ($isMonthly) {
            $kpiTotalOut = (float) MaterialMutation::where('type', 'out')->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('transaction_date', [$startDate, $endDate])
                  ->orWhere(function ($s) use ($startDate, $endDate) {
                      $s->whereNull('transaction_date')->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate]);
                  });
            })->sum('amount');
            $mOutSumQuery = MaterialMonthlyStock::where('month', $selectedMonth);
            if ($isYearly) {
                $mOutSumQuery->where('year', $selectedYear);
            }
            $kpiTotalOut = max($kpiTotalOut, (float) $mOutSumQuery->sum('total_out'));
        } else {
            $mutOutSum = (float) MaterialMutation::where('type', 'out')->sum('amount');
            $stockOutSumQuery = MaterialMonthlyStock::query();
            if ($isYearly) {
                $stockOutSumQuery->where(function ($q) use ($selectedYear) {
                    $q->where('year', $selectedYear)->orWhereNull('year');
                });
            }
            $kpiTotalOut = max($mutOutSum, (float) $stockOutSumQuery->sum('total_out'), (float) Material::sum('total_out'));
        }

        $totalInitialVal = (float) Material::sum('initial_stock');
        $totalUnitsVal = $totalInitialVal + $kpiTotalIn - $kpiTotalOut;

        $stats = [
            'total_items' => $allItems->count(),
            'total_initial' => (float) $allItems->sum('computed_initial_stock'),
            'total_in' => $kpiTotalIn,
            'total_out' => $kpiTotalOut,
            'total_units' => (float) $allItems->sum('computed_stock'),
            'low_stock_count' => $lowCount,
            'out_of_stock_count' => $outCount,
            'negative_stock_count' => $negCount,
            'safe_stock_count' => $safeCount,
            'total_asset_value' => (float) ($allItems->sum(fn($m) => $m->computed_stock * $m->unit_price)),
            'mutations_today' => MaterialMutation::whereDate('created_at', today())->count(),
            'incoming_count' => $kpiInCount,
            'outgoing_count' => $kpiOutCount,
            'tgl_awal' => $tglAwal,
            'tgl_closing' => $tglClosing,
        ];

        // Daftar Semua Kategori Unik
        $categories = Material::select('category')->distinct()->pluck('category')->filter()->values();

        // Seluruh daftar material ringan untuk modal dropdown input mutasi
        $allMaterialsList = Material::select('id', 'code', 'name', 'unit', 'stock')->orderBy('name')->get();

        return view('admin.material.index', compact(
            'tab',
            'materials',
            'incomingMutations',
            'outgoingMutations',
            'spreadsheetMaterials',
            'spreadsheetIncoming',
            'spreadsheetOutgoing',
            'stats',
            'categories',
            'allMaterialsList',
            'search',
            'categoryFilter',
            'statusFilter',
            'sort',
            'selectedMonth',
            'selectedYear',
            'monthName',
            'isMonthly',
            'prevMonth',
            'prevYear',
            'nextMonth',
            'nextYear'
        ));
    }

    /**
     * Helper untuk mengambil material dengan kalkulasi bulanan lengkap
     */
    private function getMonthlyCalculatedMaterials(?string $startDate, ?string $endDate, int $selectedMonth, int $selectedYear)
    {
        $monthName = self::MONTHS[$selectedMonth] ?? 'Oktober';
        return Material::orderByRaw("LENGTH(code) ASC, code ASC")
            ->select('materials.*')
            ->selectSub(function ($q) use ($startDate) {
                $q->from('material_mutations')
                  ->whereColumn('material_mutations.material_id', 'materials.id')
                  ->where('type', 'in')
                  ->where(function ($sub) use ($startDate) {
                      $sub->where('transaction_date', '<', $startDate)
                          ->orWhere(function ($s) use ($startDate) {
                              $s->whereNull('transaction_date')->whereDate('created_at', '<', $startDate);
                          });
                  })
                  ->selectRaw('COALESCE(SUM(amount), 0)');
            }, 'in_before')
            ->selectSub(function ($q) use ($startDate) {
                $q->from('material_mutations')
                  ->whereColumn('material_mutations.material_id', 'materials.id')
                  ->where('type', 'out')
                  ->where(function ($sub) use ($startDate) {
                      $sub->where('transaction_date', '<', $startDate)
                          ->orWhere(function ($s) use ($startDate) {
                              $s->whereNull('transaction_date')->whereDate('created_at', '<', $startDate);
                          });
                  })
                  ->selectRaw('COALESCE(SUM(amount), 0)');
            }, 'out_before')
            ->selectSub(function ($q) use ($startDate, $endDate) {
                $q->from('material_mutations')
                  ->whereColumn('material_mutations.material_id', 'materials.id')
                  ->where('type', 'in')
                  ->where(function ($sub) use ($startDate, $endDate) {
                      $sub->whereBetween('transaction_date', [$startDate, $endDate])
                          ->orWhere(function ($s) use ($startDate, $endDate) {
                              $s->whereNull('transaction_date')->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate]);
                          });
                  })
                  ->selectRaw('COALESCE(SUM(amount), 0)');
            }, 'period_in')
            ->selectSub(function ($q) use ($startDate, $endDate) {
                $q->from('material_mutations')
                  ->whereColumn('material_mutations.material_id', 'materials.id')
                  ->where('type', 'out')
                  ->where(function ($sub) use ($startDate, $endDate) {
                      $sub->whereBetween('transaction_date', [$startDate, $endDate])
                          ->orWhere(function ($s) use ($startDate, $endDate) {
                              $s->whereNull('transaction_date')->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate]);
                          });
                  })
                  ->selectRaw('COALESCE(SUM(amount), 0)');
            }, 'period_out')
            ->where(function ($q) use ($selectedMonth, $selectedYear, $monthName, $startDate, $endDate) {
                $q->whereHas('monthlyStocks', function ($sq) use ($selectedMonth, $selectedYear) {
                    $sq->where('month', $selectedMonth)->where('year', $selectedYear);
                })
                ->orWhereHas('mutations', function ($sq) use ($startDate, $endDate) {
                    $sq->whereBetween('transaction_date', [$startDate, $endDate])
                       ->orWhere(function ($sub) use ($startDate, $endDate) {
                           $sub->whereNull('transaction_date')->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate]);
                       });
                })
                ->orWhere(function ($sq) use ($selectedMonth, $selectedYear, $monthName) {
                    $sq->where(function ($s) use ($selectedMonth, $monthName) {
                        $s->where('month', $monthName)
                          ->orWhere('month', (string)$selectedMonth)
                          ->orWhere('month', 'like', "%{$monthName}%");
                    })->where(function ($s) use ($selectedYear) {
                        $s->where('year', $selectedYear)->orWhereNull('year');
                    });
                });
            })
            ->with(['monthlyStocks' => function ($q) use ($selectedMonth, $selectedYear) {
                $q->where('month', $selectedMonth)->where('year', $selectedYear);
            }])
            ->get()
            ->map(function ($m) use ($selectedMonth, $selectedYear, $monthName) {
                $monthly = $m->monthlyStocks->first(function ($ms) use ($selectedMonth, $selectedYear) {
                    return (int)$ms->month === (int)$selectedMonth && (int)$ms->year === (int)$selectedYear;
                });

                if ($monthly) {
                    $displayIn = max((float)$monthly->total_in, (float)($m->period_in ?? 0));
                    $displayOut = max((float)$monthly->total_out, (float)($m->period_out ?? 0));
                    $displayAwal = (float)$monthly->initial_stock;
                    $displayAkhir = $displayAwal + $displayIn - $displayOut;
                    $displayMonthName = $monthly->month_name ?: $monthName;
                } else {
                    $displayIn = (float) ($m->period_in ?? 0);
                    $displayOut = (float) ($m->period_out ?? 0);
                    $displayAwal = (float) $m->initial_stock + (float) ($m->in_before ?? 0) - (float) ($m->out_before ?? 0);
                    $displayAkhir = $displayAwal + $displayIn - $displayOut;
                    $displayMonthName = !empty($m->month) ? $m->month : $monthName;
                }

                $m->computed_initial_stock = $displayAwal;
                $m->computed_total_in = $displayIn;
                $m->computed_total_out = $displayOut;
                $m->computed_stock = $displayAkhir;
                $m->display_month = $displayMonthName;
                return $m;
            });
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
            'month' => 'nullable|string|max:50',
            'initial_stock' => 'required|numeric',
            'min_stock' => 'nullable|numeric|min:0',
            'unit_price' => 'nullable|numeric|min:0',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ], [
            'code.unique' => 'Kode material tersebut sudah digunakan.',
            'name.required' => 'Nama material wajib diisi.',
            'category.required' => 'Kategori material wajib dipilih.',
            'unit.required' => 'Satuan material wajib diisi.',
            'initial_stock.required' => 'Jumlah stok awal wajib diisi.',
        ]);

        if (empty($validated['code'])) {
            $prefix = match (strtoupper(substr($validated['category'], 0, 3))) {
                'TIA' => 'A',
                'KAB', 'FIB', 'DRO' => 'KBL-',
                'MOD', 'ONT' => 'ONT-',
                'ROU', 'AP ' => 'RTR-',
                default => 'BRG-',
            };
            $nextNum = Material::count() + 1;
            $validated['code'] = $prefix . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
        }

        $validated['initial_stock'] = (float) $validated['initial_stock'];
        $validated['total_in'] = 0;
        $validated['total_out'] = 0;
        $validated['stock'] = $validated['initial_stock'];
        $validated['min_stock'] = (float) ($validated['min_stock'] ?? 5);
        $validated['unit_price'] = (float) ($validated['unit_price'] ?? 0);
        $validated['month'] = $validated['month'] ?: 'Oktober';

        $material = Material::create($validated);

        return redirect()->back()->with('success', "Barang \"{$material->name}\" ({$material->code}) berhasil didaftarkan ke inventaris.");
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
            'month' => 'nullable|string|max:50',
            'initial_stock' => 'required|numeric',
            'min_stock' => 'nullable|numeric|min:0',
            'unit_price' => 'nullable|numeric|min:0',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $initialStock = (float) $validated['initial_stock'];
        $validated['initial_stock'] = $initialStock;
        $validated['stock'] = $initialStock + ($material->total_in ?? 0) - ($material->total_out ?? 0);
        $validated['min_stock'] = (float) ($validated['min_stock'] ?? 5);
        $validated['unit_price'] = (float) ($validated['unit_price'] ?? 0);

        $material->update($validated);

        return redirect()->back()->with('success', "Informasi barang \"{$material->name}\" ({$material->code}) berhasil diperbarui.");
    }

    /**
     * Catat Barang Masuk (Baru atau Tambah Stok)
     */
    public function storeIncoming(Request $request)
    {
        $request->validate([
            'material_id' => 'required|exists:materials,id',
            'amount' => 'required|numeric|min:0.01',
            'transaction_date' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
        ], [
            'material_id.required' => 'Pilih barang yang masuk.',
            'amount.required' => 'Jumlah barang masuk wajib diisi.',
            'amount.min' => 'Jumlah barang masuk minimal lebih dari 0.',
        ]);

        $material = Material::findOrFail($request->input('material_id'));
        $amount = (float) $request->input('amount');
        $notes = $request->input('notes') ?: 'Barang Masuk';
        $date = $request->input('transaction_date') ?: now()->toDateString();

        $material->addStock($amount, $notes, auth()->id(), $date);

        // Perbarui snapshot bulanan jika ada pada bulan dan tahun transaksi tersebut
        try {
            $txCarbon = Carbon::parse($date);
            $mStock = MaterialMonthlyStock::where('material_id', $material->id)
                ->where('month', $txCarbon->month)
                ->where('year', $txCarbon->year)
                ->first();
            if ($mStock) {
                $mStock->total_in = (float)$mStock->total_in + $amount;
                $mStock->final_stock = (float)$mStock->initial_stock + $mStock->total_in - (float)$mStock->total_out;
                $mStock->save();
            }
        } catch (\Throwable $e) {}

        return redirect()->back()->with('success', "Berhasil mencatat Barang Masuk: +{$amount} {$material->unit} untuk \"{$material->name}\". Stok akhir: " . Material::formatQty($material->stock) . " {$material->unit}.");
    }

    /**
     * Catat Barang Keluar (Pemakaian Lapangan / Teknisi)
     */
    public function storeOutgoing(Request $request)
    {
        $request->validate([
            'material_id' => 'required|exists:materials,id',
            'amount' => 'required|numeric|min:0.01',
            'transaction_date' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
        ], [
            'material_id.required' => 'Pilih barang yang keluar.',
            'amount.required' => 'Jumlah barang keluar wajib diisi.',
            'amount.min' => 'Jumlah barang keluar minimal lebih dari 0.',
        ]);

        $material = Material::findOrFail($request->input('material_id'));
        $amount = (float) $request->input('amount');
        $notes = $request->input('notes') ?: 'Barang Keluar';
        $date = $request->input('transaction_date') ?: now()->toDateString();

        $material->reduceStock($amount, $notes, auth()->id(), $date);

        // Perbarui snapshot bulanan jika ada pada bulan dan tahun transaksi tersebut
        try {
            $txCarbon = Carbon::parse($date);
            $mStock = MaterialMonthlyStock::where('material_id', $material->id)
                ->where('month', $txCarbon->month)
                ->where('year', $txCarbon->year)
                ->first();
            if ($mStock) {
                $mStock->total_out = (float)$mStock->total_out + $amount;
                $mStock->final_stock = (float)$mStock->initial_stock + (float)$mStock->total_in - $mStock->total_out;
                $mStock->save();
            }
        } catch (\Throwable $e) {}

        return redirect()->back()->with('success', "Berhasil mencatat Barang Keluar: -{$amount} {$material->unit} untuk \"{$material->name}\". Sisa stok akhir: " . Material::formatQty($material->stock) . " {$material->unit}.");
    }

    /**
     * Fitur Tambah Stok Manual (Restock / Barang Masuk dari Modal Baris)
     */
    public function addStock(Request $request, $id)
    {
        $material = Material::findOrFail($id);

        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:500',
            'transaction_date' => 'nullable|date',
        ]);

        $amount = (float) $request->input('amount');
        $notes = $request->input('notes') ?: 'Penambahan stok manual oleh ' . (auth()->user()->name ?? 'Admin');
        $date = $request->input('transaction_date') ?: now()->toDateString();

        $material->addStock($amount, $notes, auth()->id(), $date);

        try {
            $txCarbon = Carbon::parse($date);
            $mStock = MaterialMonthlyStock::where('material_id', $material->id)
                ->where('month', $txCarbon->month)
                ->where('year', $txCarbon->year)
                ->first();
            if ($mStock) {
                $mStock->total_in = (float)$mStock->total_in + $amount;
                $mStock->final_stock = (float)$mStock->initial_stock + $mStock->total_in - (float)$mStock->total_out;
                $mStock->save();
            }
        } catch (\Throwable $e) {}

        return redirect()->back()->with('success', "Berhasil menambahkan +{$amount} {$material->unit} ke stok \"{$material->name}\". Total stok akhir: " . Material::formatQty($material->stock) . " {$material->unit}.");
    }

    /**
     * Fitur Kurang Stok Manual (Pengeluaran Barang dari Modal Baris)
     */
    public function reduceStock(Request $request, $id)
    {
        $material = Material::findOrFail($id);

        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:500',
            'transaction_date' => 'nullable|date',
        ]);

        $amount = (float) $request->input('amount');
        $notes = $request->input('notes') ?: 'Pengurangan stok manual oleh ' . (auth()->user()->name ?? 'Admin');
        $date = $request->input('transaction_date') ?: now()->toDateString();

        $material->reduceStock($amount, $notes, auth()->id(), $date);

        try {
            $txCarbon = Carbon::parse($date);
            $mStock = MaterialMonthlyStock::where('material_id', $material->id)
                ->where('month', $txCarbon->month)
                ->where('year', $txCarbon->year)
                ->first();
            if ($mStock) {
                $mStock->total_out = (float)$mStock->total_out + $amount;
                $mStock->final_stock = (float)$mStock->initial_stock + (float)$mStock->total_in - $mStock->total_out;
                $mStock->save();
            }
        } catch (\Throwable $e) {}

        return redirect()->back()->with('success', "Berhasil mengurangi -{$amount} {$material->unit} dari stok \"{$material->name}\". Sisa stok akhir: " . Material::formatQty($material->stock) . " {$material->unit}.");
    }

    /**
     * Fitur Penyesuaian Stok Opname Manual (Direct Adjustment)
     */
    public function adjustStock(Request $request, $id)
    {
        $material = Material::findOrFail($id);

        $request->validate([
            'new_stock' => 'required|numeric',
            'notes' => 'required|string|max:500',
        ]);

        $newStock = (float) $request->input('new_stock');
        $oldStock = $material->stock;
        $notes = $request->input('notes');

        $material->adjustStock($newStock, $notes, auth()->id());

        return redirect()->back()->with('success', "Stok \"{$material->name}\" berhasil disesuaikan dari " . Material::formatQty($oldStock) . " menjadi " . Material::formatQty($newStock) . " {$material->unit}.");
    }

    /**
     * Perbarui Catatan Mutasi (Barang Masuk / Keluar)
     */
    public function updateMutation(Request $request, $id)
    {
        $request->validate([
            'material_id' => 'required|exists:materials,id',
            'amount' => 'required|numeric|min:0.01',
            'transaction_date' => 'required|date',
            'notes' => 'nullable|string|max:500',
        ], [
            'material_id.required' => 'Pilih barang yang bersangkutan.',
            'amount.required' => 'Jumlah barang wajib diisi.',
            'amount.min' => 'Jumlah barang minimal lebih dari 0.',
            'transaction_date.required' => 'Tanggal transaksi wajib diisi.',
        ]);

        return DB::transaction(function () use ($request, $id) {
            $mut = MaterialMutation::findOrFail($id);
            $oldMaterial = Material::find($mut->material_id);
            $oldAmount = (float) $mut->amount;
            $oldType = $mut->type;
            $oldDate = $mut->transaction_date;

            $newMaterialId = (int) $request->input('material_id');
            $newAmount = (float) $request->input('amount');
            $newDate = $request->input('transaction_date');
            $newNotes = $request->input('notes');

            // 1. Revert efek mutasi lama dari oldMaterial & monthly snapshot
            if ($oldMaterial) {
                if ($oldType === 'in') {
                    $oldMaterial->total_in = max(0, (float)($oldMaterial->total_in ?? 0) - $oldAmount);
                } elseif ($oldType === 'out') {
                    $oldMaterial->total_out = max(0, (float)($oldMaterial->total_out ?? 0) - $oldAmount);
                }
                $oldMaterial->stock = (float)($oldMaterial->initial_stock ?? 0) + (float)($oldMaterial->total_in ?? 0) - (float)($oldMaterial->total_out ?? 0);
                $oldMaterial->save();

                if ($oldDate) {
                    try {
                        $oldCarbon = Carbon::parse($oldDate);
                        $oldStock = MaterialMonthlyStock::where('material_id', $oldMaterial->id)
                            ->where('month', $oldCarbon->month)
                            ->where('year', $oldCarbon->year)
                            ->first();
                        if ($oldStock) {
                            if ($oldType === 'in') {
                                $oldStock->total_in = max(0, (float)$oldStock->total_in - $oldAmount);
                            } elseif ($oldType === 'out') {
                                $oldStock->total_out = max(0, (float)$oldStock->total_out - $oldAmount);
                            }
                            $oldStock->final_stock = (float)$oldStock->initial_stock + (float)$oldStock->total_in - (float)$oldStock->total_out;
                            $oldStock->save();
                        }
                    } catch (\Throwable $e) {}
                }
            }

            // 2. Terapkan efek mutasi baru ke newMaterial & monthly snapshot
            $newMaterial = Material::findOrFail($newMaterialId);
            if ($oldType === 'in') {
                $newMaterial->total_in = (float)($newMaterial->total_in ?? 0) + $newAmount;
            } elseif ($oldType === 'out') {
                $newMaterial->total_out = (float)($newMaterial->total_out ?? 0) + $newAmount;
            }
            $newMaterial->stock = (float)($newMaterial->initial_stock ?? 0) + (float)($newMaterial->total_in ?? 0) - (float)($newMaterial->total_out ?? 0);
            $newMaterial->save();

            if ($newDate) {
                try {
                    $newCarbon = Carbon::parse($newDate);
                    $newStock = MaterialMonthlyStock::where('material_id', $newMaterial->id)
                        ->where('month', $newCarbon->month)
                        ->where('year', $newCarbon->year)
                        ->first();
                    if ($newStock) {
                        if ($oldType === 'in') {
                            $newStock->total_in = (float)$newStock->total_in + $newAmount;
                        } elseif ($oldType === 'out') {
                            $newStock->total_out = (float)$newStock->total_out + $newAmount;
                        }
                        $newStock->final_stock = (float)$newStock->initial_stock + (float)$newStock->total_in - (float)$newStock->total_out;
                        $newStock->save();
                    }
                } catch (\Throwable $e) {}
            }

            // 3. Perbarui record mutasi
            $typeSuffix = $oldType === 'in' ? ' (Barang Masuk)' : ' (Barang Keluar)';
            $formattedNotes = $newNotes ? (str_ends_with($newNotes, $typeSuffix) ? $newNotes : ($newNotes . $typeSuffix)) : ($oldType === 'in' ? 'Barang Masuk' : 'Barang Keluar');

            $mut->update([
                'material_id' => $newMaterial->id,
                'amount' => $newAmount,
                'transaction_date' => $newDate,
                'notes' => $formattedNotes,
                'stock_before' => $oldType === 'in' ? ($newMaterial->stock - $newAmount) : ($newMaterial->stock + $newAmount),
                'stock_after' => $newMaterial->stock,
            ]);

            $typeLabel = $oldType === 'in' ? 'Barang Masuk' : 'Barang Keluar';
            return redirect()->back()->with('success', "Catatan {$typeLabel} untuk \"{$newMaterial->name}\" berhasil diperbarui.");
        });
    }

    /**
     * Hapus Catatan Mutasi Tertentu
     */
    public function destroyMutation($id)
    {
        $mut = MaterialMutation::with('material')->findOrFail($id);
        $material = $mut->material;

        if ($material) {
            if ($mut->type === 'in') {
                $material->total_in = max(0, ($material->total_in ?? 0) - $mut->amount);
                $material->stock = ($material->initial_stock ?? 0) + $material->total_in - ($material->total_out ?? 0);
                $material->save();
            } elseif ($mut->type === 'out') {
                $material->total_out = max(0, ($material->total_out ?? 0) - $mut->amount);
                $material->stock = ($material->initial_stock ?? 0) + ($material->total_in ?? 0) - $material->total_out;
                $material->save();
            }

            if ($mut->transaction_date) {
                try {
                    $mCarbon = Carbon::parse($mut->transaction_date);
                    $mStock = MaterialMonthlyStock::where('material_id', $material->id)
                        ->where('month', $mCarbon->month)
                        ->where('year', $mCarbon->year)
                        ->first();
                    if ($mStock) {
                        if ($mut->type === 'in') {
                            $mStock->total_in = max(0, (float)$mStock->total_in - (float)$mut->amount);
                        } elseif ($mut->type === 'out') {
                            $mStock->total_out = max(0, (float)$mStock->total_out - (float)$mut->amount);
                        }
                        $mStock->final_stock = (float)$mStock->initial_stock + (float)$mStock->total_in - (float)$mStock->total_out;
                        $mStock->save();
                    }
                } catch (\Throwable $e) {}
            }
        }

        $mut->delete();

        return redirect()->back()->with('success', "Catatan mutasi berhasil dihapus dan kalkulasi stok material telah diperbarui.");
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

        return redirect()->back()->with('success', "Barang \"{$name}\" ({$code}) berhasil dihapus dari inventaris.");
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
     * Impor Data Stok Barang, Barang Masuk, dan Barang Keluar dari Berkas Excel (.xlsx, .xls) atau CSV (.csv, .txt)
     * Mendukung pembacaan berkas 1 sheet atau multi sheet dengan layout berdampingan,
     * serta otomatis melengkapi data yang keterangannya tidak lengkap dalam 1 periode bulan.
     */
    public function importCsv(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file',
        ], [
            'csv_file.required' => 'Pilih berkas Excel atau CSV yang akan diunggah.',
        ]);

        $file = $request->file('csv_file');
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, ['csv', 'txt', 'xlsx', 'xls'])) {
            return redirect()->back()->with('error', 'Format berkas harus berekstensi .xlsx, .xls, .csv, atau .txt.');
        }

        $sheets = [];
        try {
            $sheets = CustomerImportService::extractSheetsFromFile($file);
        } catch (\Throwable $e) {}

        if (empty($sheets)) {
            $content = @file_get_contents($file->getRealPath());
            $lines = preg_split('/\r\n|\r|\n/', (string)$content);
            $lines = array_filter($lines, fn($l) => trim($l) !== '');
            $lines = array_values($lines);

            if (!empty($lines)) {
                $delimiter = ';';
                if (isset($lines[0]) && substr_count($lines[0], ',') > substr_count($lines[0], ';')) {
                    $delimiter = ',';
                }
                $csvRows = [];
                foreach ($lines as $line) {
                    $cols = str_getcsv($line, $delimiter);
                    $csvRows[] = array_map('trim', $cols);
                }
                $sheets[] = ['name' => 'Sheet 1', 'rows' => $csvRows];
            }
        }

        if (empty($sheets)) {
            return redirect()->back()->with('error', 'Berkas Excel / CSV kosong atau tidak ada data yang terbaca.');
        }

        // Pilihan Periode Bulan & Tahun (dari Form atau Otomatis Terdeteksi dari Berkas)
        $monthInput = $request->input('import_month', 'auto');
        $yearInput = $request->input('import_year', 'auto');

        $forcedMonth = ($monthInput !== 'auto' && is_numeric($monthInput)) ? (int)$monthInput : null;
        $forcedYear = ($yearInput !== 'auto' && is_numeric($yearInput)) ? (int)$yearInput : null;

        // Deteksi periode dari nama sheet, teks header, atau tanggal transaksi di dalam berkas
        $detectedMonthNumber = null;
        $detectedYear = null;

        $monthRegex = '/(Januari|Februari|Pebruari|Maret|April|Mei|Juni|Juli|Agustus|September|Oktober|November|Nopember|Desember)(?:\s+(\d{4}))?/i';
        $monthMap = [
            'januari' => 1, 'februari' => 2, 'pebruari' => 2, 'maret' => 3, 'april' => 4,
            'mei' => 5, 'juni' => 6, 'juli' => 7, 'agustus' => 8, 'september' => 9,
            'oktober' => 10, 'november' => 11, 'nopember' => 11, 'desember' => 12,
        ];

        foreach ($sheets as $s) {
            $sName = $s['name'] ?? '';
            if (preg_match($monthRegex, $sName, $m)) {
                $detectedMonthNumber = $monthMap[strtolower($m[1])] ?? null;
                if (!empty($m[2])) $detectedYear = (int)$m[2];
                break;
            }
            $rows = $s['rows'] ?? [];
            for ($r = 0; $r < min(count($rows), 15); $r++) {
                $rowStr = implode(' ', $rows[$r] ?? []);
                if (preg_match($monthRegex, $rowStr, $m)) {
                    $detectedMonthNumber = $monthMap[strtolower($m[1])] ?? null;
                    if (!empty($m[2])) $detectedYear = (int)$m[2];
                    break 2;
                }
            }
        }

        // Jika belum terdeteksi dari teks, deteksi dari tanggal transaksi pada kolom data
        if (!$detectedMonthNumber || !$detectedYear) {
            $monthVotes = [];
            $yearVotes = [];
            foreach ($sheets as $s) {
                $rows = $s['rows'] ?? [];
                foreach (array_slice($rows, 0, 50) as $r) {
                    foreach ($r as $cell) {
                        $cellStr = trim((string)$cell);
                        if (preg_match('/^(\d{1,2})[\/\.-](\d{1,2})[\/\.-](\d{4})$/', $cellStr, $dm)) {
                            $y = (int)$dm[3];
                            $m1 = (int)$dm[1];
                            $m2 = (int)$dm[2];
                            $yearVotes[$y] = ($yearVotes[$y] ?? 0) + 1;
                            if ($m1 >= 1 && $m1 <= 12) $monthVotes[$m1] = ($monthVotes[$m1] ?? 0) + 1;
                            if ($m2 >= 1 && $m2 <= 12 && $m2 !== $m1) $monthVotes[$m2] = ($monthVotes[$m2] ?? 0) + 1;
                        }
                    }
                }
            }
            if (!empty($yearVotes) && !$detectedYear) {
                arsort($yearVotes);
                $detectedYear = array_key_first($yearVotes);
            }
            if (!empty($monthVotes) && !$detectedMonthNumber) {
                arsort($monthVotes);
                $detectedMonthNumber = array_key_first($monthVotes);
            }
        }

        $fileMonthNumber = $forcedMonth ?: ($detectedMonthNumber ?: 10);
        $fileYear = $forcedYear ?: ($detectedYear ?: 2026);
        $fileMonthName = self::MONTHS[$fileMonthNumber] ?? 'Oktober';

        $adminId = auth()->id();
        $importedMaterials = 0;
        $importedIn = 0;
        $importedOut = 0;

        DB::beginTransaction();
        try {
            if ($request->boolean('replace_all', false)) {
                $delStart = Carbon::createFromDate($fileYear, $fileMonthNumber, 1)->startOfMonth()->toDateString();
                $delEnd = Carbon::createFromDate($fileYear, $fileMonthNumber, 1)->endOfMonth()->toDateString();
                MaterialMutation::where(function ($q) use ($delStart, $delEnd) {
                    $q->whereBetween('transaction_date', [$delStart, $delEnd])
                      ->orWhere(function ($s) use ($delStart, $delEnd) {
                          $s->whereNull('transaction_date')->whereBetween(DB::raw('DATE(created_at)'), [$delStart, $delEnd]);
                      });
                })->delete();
                MaterialMonthlyStock::where('month', $fileMonthNumber)->where('year', $fileYear)->delete();
            }

            // Cache master material untuk pencarian cepat
            $materialMap = [];
            foreach (Material::all() as $m) {
                $materialMap[$m->code] = $m;
                $cleanName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $m->name));
                $materialMap['name_' . $cleanName] = $m;
            }

            $assignedCodes = [];
            $materialInSums = [];
            $materialOutSums = [];

            // Proses setiap sheet yang ada di berkas
            foreach ($sheets as $s) {
                $rows = $s['rows'] ?? [];
                if (empty($rows)) continue;

                $this->processSheetData(
                    $rows,
                    $fileMonthNumber,
                    $fileMonthName,
                    $fileYear,
                    $adminId,
                    $materialMap,
                    $assignedCodes,
                    $materialInSums,
                    $materialOutSums,
                    $importedMaterials,
                    $importedIn,
                    $importedOut,
                    $s['name'] ?? ''
                );
            }

            // Rekonsiliasi & sinkronisasi total masuk, keluar, dan stok untuk material yang terdampak pada periode ini
            foreach ($materialMap as $k => $mat) {
                if (!str_starts_with($k, 'name_')) {
                    $matId = $mat->id;
                    $hasMutations = isset($materialInSums[$matId]) || isset($materialOutSums[$matId]);
                    if ($hasMutations) {
                        $sumIn = $materialInSums[$matId] ?? 0;
                        $sumOut = $materialOutSums[$matId] ?? 0;

                        $mStock = MaterialMonthlyStock::firstOrNew([
                            'material_id' => $matId,
                            'month' => $fileMonthNumber,
                            'year' => $fileYear,
                        ]);
                        $baseInitial = $mStock->exists ? (float)$mStock->initial_stock : (float)$mat->initial_stock;
                        $baseIn = $mStock->exists ? (float)$mStock->total_in : (float)$mat->total_in;
                        $baseOut = $mStock->exists ? (float)$mStock->total_out : (float)$mat->total_out;

                        $updatedTotalIn = max($baseIn, (float)$sumIn);
                        $updatedTotalOut = max($baseOut, (float)$sumOut);
                        $updatedStock = $baseInitial + $updatedTotalIn - $updatedTotalOut;

                        $mStock->month_name = $fileMonthName;
                        $mStock->initial_stock = $baseInitial;
                        $mStock->total_in = $updatedTotalIn;
                        $mStock->total_out = $updatedTotalOut;
                        $mStock->final_stock = $updatedStock;
                        $mStock->save();

                        $mat->update([
                            'total_in' => $updatedTotalIn,
                            'total_out' => $updatedTotalOut,
                            'stock' => $updatedStock,
                        ]);
                    }
                }
            }

            DB::commit();

            return redirect()->route('admin.material.index', [
                'month' => $fileMonthNumber,
                'year' => $fileYear,
            ])->with('success', "Impor Berhasil: {$importedMaterials} data stok barang, {$importedIn} transaksi barang masuk, dan {$importedOut} transaksi barang keluar berhasil disimpan untuk periode {$fileMonthName} {$fileYear}.");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal memproses berkas Excel / CSV: ' . $e->getMessage());
        }
    }

    /**
     * Memproses data dalam satu sheet berkas: mendeteksi bagian Stok Barang,
     * Barang Masuk, dan Barang Keluar secara cerdas dan fleksibel.
     */
    private function processSheetData(
        array $rows,
        int $fileMonthNumber,
        string $fileMonthName,
        int $fileYear,
        ?int $adminId,
        array &$materialMap,
        array &$assignedCodes,
        array &$materialInSums,
        array &$materialOutSums,
        int &$importedMaterials,
        int &$importedIn,
        int &$importedOut,
        string $sheetName = ''
    ): void {
        if (empty($rows)) return;

        // 1. Cari baris header utama (baris yang mendefinisikan kolom-kolom seperti Kode Barang, Nama Barang, Stok Awal, dsb.)
        $headerRowIndex = 0;
        for ($i = 0; $i < min(count($rows), 25); $i++) {
            $joined = strtolower(implode(' ', array_slice($rows[$i] ?? [], 0, 20)));
            if (str_starts_with(trim($joined), 'tgl stok awal') || str_starts_with(trim($joined), 'tgl closing') || str_starts_with(trim($joined), 'laporan')) {
                continue;
            }
            if (
                (str_contains($joined, 'kode') && (str_contains($joined, 'nama') || str_contains($joined, 'barang'))) ||
                (str_contains($joined, 'nama barang') && str_contains($joined, 'stok')) ||
                (str_contains($joined, 'kode barang') && str_contains($joined, 'stok')) ||
                (str_contains($joined, 'stok awal') && (str_contains($joined, 'kode') || str_contains($joined, 'nama') || str_contains($joined, 'stok akhir'))) ||
                (str_contains($joined, 'jumlah masuk') && str_contains($joined, 'jumlah keluar')) ||
                ((str_contains($joined, 'tgl') || str_contains($joined, 'tanggal') || str_contains($joined, 'date')) && (str_contains($joined, 'nama') || str_contains($joined, 'barang')) && (str_contains($joined, 'masuk') || str_contains($joined, 'keluar') || str_contains($joined, 'jumlah') || str_contains($joined, 'qty')))
            ) {
                $headerRowIndex = $i;
                break;
            }
        }

        $headerCols = $rows[$headerRowIndex] ?? [];
        $totalCols = count($headerCols);

        // 2. Deteksi pemetaan kolom untuk 3 bagian:
        // - Stok Barang (code, name, month, initial_stock, total_in, total_out, stock)
        // - Barang Masuk: 1. Tanggal, 2. Nama Barang, 3. Jumlah Masuk, 4. Catatan
        // - Barang Keluar: 1. Tanggal, 2. Nama Barang, 3. Jumlah Keluar, 4. Catatan
        $stokCols = ['code' => null, 'name' => null, 'month' => null, 'initial_stock' => null, 'total_in' => null, 'total_out' => null, 'stock' => null];
        $inCols = ['date' => null, 'name' => null, 'amount' => null, 'notes' => null];
        $outCols = ['date' => null, 'name' => null, 'amount' => null, 'notes' => null];

        $sNameLower = strtolower(trim($sheetName));
        $isDedicatedInSheet = str_contains($sNameLower, 'barang masuk') || $sNameLower === 'masuk' || str_contains($sNameLower, 'mutasi masuk');
        $isDedicatedOutSheet = str_contains($sNameLower, 'barang keluar') || $sNameLower === 'keluar' || str_contains($sNameLower, 'mutasi keluar');

        // Cari tahu apakah ada judul seksi di baris-baris SEBELUM header (contoh: BARANG MASUK, Barang Keluar)
        $masukColStart = null;
        $keluarColStart = null;
        for ($r = 0; $r < $headerRowIndex; $r++) {
            foreach ($rows[$r] ?? [] as $cIdx => $cellVal) {
                $cClean = strtolower(trim((string)$cellVal));
                if (str_contains($cClean, 'barang masuk') || $cClean === 'masuk') {
                    if ($masukColStart === null || $cIdx < $masukColStart) {
                        $masukColStart = $cIdx;
                    }
                } elseif (str_contains($cClean, 'barang keluar') || $cClean === 'keluar') {
                    if ($keluarColStart === null || $cIdx < $keluarColStart) {
                        $keluarColStart = $cIdx;
                    }
                }
            }
        }

        // Petakan kolom berdasarkan teks header
        foreach ($headerCols as $cIdx => $cHdr) {
            $h = strtolower(trim((string)$cHdr));
            if ($h === '') continue;

            $isKeluarZone = ($keluarColStart !== null && $cIdx >= $keluarColStart);
            $isMasukZone = !$isKeluarZone && ($masukColStart !== null && $cIdx >= $masukColStart);

            // Deteksi kolom spesifik Barang Keluar / Masuk
            if (str_contains($h, 'jumlah keluar') || str_contains($h, 'jml keluar') || str_contains($h, 'qty keluar')) {
                $outCols['amount'] = $cIdx;
                if ($outCols['name'] === null && $cIdx >= 1) $outCols['name'] = $cIdx - 1;
                if ($outCols['date'] === null && $cIdx >= 2) $outCols['date'] = $cIdx - 2;
                if ($outCols['notes'] === null && isset($headerCols[$cIdx + 1])) $outCols['notes'] = $cIdx + 1;
            } elseif (str_contains($h, 'jumlah masuk') || str_contains($h, 'jml masuk') || str_contains($h, 'qty masuk')) {
                $inCols['amount'] = $cIdx;
                if ($inCols['name'] === null && $cIdx >= 1) $inCols['name'] = $cIdx - 1;
                if ($inCols['date'] === null && $cIdx >= 2) $inCols['date'] = $cIdx - 2;
                if ($inCols['notes'] === null && isset($headerCols[$cIdx + 1])) $inCols['notes'] = $cIdx + 1;
            } elseif ($isDedicatedInSheet) {
                if ($inCols['date'] === null && (str_contains($h, 'tgl') || str_contains($h, 'tanggal') || str_contains($h, 'date'))) {
                    $inCols['date'] = $cIdx;
                } elseif ($inCols['name'] === null && (str_contains($h, 'nama') || str_contains($h, 'barang') || str_contains($h, 'item'))) {
                    $inCols['name'] = $cIdx;
                } elseif ($inCols['amount'] === null && (str_contains($h, 'jumlah') || str_contains($h, 'qty') || str_contains($h, 'masuk') || str_contains($h, 'total'))) {
                    $inCols['amount'] = $cIdx;
                } elseif ($inCols['notes'] === null && (str_contains($h, 'catatan') || str_contains($h, 'keterangan') || str_contains($h, 'ket') || str_contains($h, 'supplier') || str_contains($h, 'vendor'))) {
                    $inCols['notes'] = $cIdx;
                }
            } elseif ($isDedicatedOutSheet) {
                if ($outCols['date'] === null && (str_contains($h, 'tgl') || str_contains($h, 'tanggal') || str_contains($h, 'date'))) {
                    $outCols['date'] = $cIdx;
                } elseif ($outCols['name'] === null && (str_contains($h, 'nama') || str_contains($h, 'barang') || str_contains($h, 'item'))) {
                    $outCols['name'] = $cIdx;
                } elseif ($outCols['amount'] === null && (str_contains($h, 'jumlah') || str_contains($h, 'qty') || str_contains($h, 'keluar') || str_contains($h, 'total'))) {
                    $outCols['amount'] = $cIdx;
                } elseif ($outCols['notes'] === null && (str_contains($h, 'catatan') || str_contains($h, 'keterangan') || str_contains($h, 'ket') || str_contains($h, 'teknisi') || str_contains($h, 'keperluan'))) {
                    $outCols['notes'] = $cIdx;
                }
            } elseif ($isKeluarZone) {
                if ($outCols['date'] === null && (str_contains($h, 'tgl') || str_contains($h, 'tanggal') || str_contains($h, 'date'))) {
                    $outCols['date'] = $cIdx;
                } elseif ($outCols['name'] === null && (str_contains($h, 'nama') || str_contains($h, 'barang') || str_contains($h, 'item'))) {
                    $outCols['name'] = $cIdx;
                } elseif ($outCols['amount'] === null && (str_contains($h, 'jumlah') || str_contains($h, 'qty') || str_contains($h, 'keluar') || str_contains($h, 'total'))) {
                    $outCols['amount'] = $cIdx;
                } elseif ($outCols['notes'] === null && (str_contains($h, 'catatan') || str_contains($h, 'keterangan') || str_contains($h, 'ket') || str_contains($h, 'teknisi') || str_contains($h, 'keperluan') || str_contains($h, 'tujuan'))) {
                    $outCols['notes'] = $cIdx;
                }
            } elseif ($isMasukZone) {
                if ($inCols['date'] === null && (str_contains($h, 'tgl') || str_contains($h, 'tanggal') || str_contains($h, 'date'))) {
                    $inCols['date'] = $cIdx;
                } elseif ($inCols['name'] === null && (str_contains($h, 'nama') || str_contains($h, 'barang') || str_contains($h, 'item'))) {
                    $inCols['name'] = $cIdx;
                } elseif ($inCols['amount'] === null && (str_contains($h, 'jumlah') || str_contains($h, 'qty') || str_contains($h, 'masuk') || str_contains($h, 'total'))) {
                    $inCols['amount'] = $cIdx;
                } elseif ($inCols['notes'] === null && (str_contains($h, 'catatan') || str_contains($h, 'keterangan') || str_contains($h, 'ket') || str_contains($h, 'supplier') || str_contains($h, 'vendor') || str_contains($h, 'petugas'))) {
                    $inCols['notes'] = $cIdx;
                }
            } else {
                // Seksi Stok Barang Utama
                if ($stokCols['code'] === null && (str_contains($h, 'kode') || $h === 'code')) {
                    $stokCols['code'] = $cIdx;
                } elseif ($stokCols['month'] === null && (str_contains($h, 'bulan') || str_contains($h, 'periode') || $h === 'month')) {
                    $stokCols['month'] = $cIdx;
                } elseif ($stokCols['name'] === null && (str_contains($h, 'nama') || str_contains($h, 'barang') || str_contains($h, 'material') || str_contains($h, 'item') || str_contains($h, 'deskripsi'))) {
                    $stokCols['name'] = $cIdx;
                } elseif ($stokCols['initial_stock'] === null && (str_contains($h, 'awal') || str_contains($h, 'initial'))) {
                    $stokCols['initial_stock'] = $cIdx;
                } elseif ($stokCols['total_in'] === null && (str_contains($h, 'masuk') || str_contains($h, 'in'))) {
                    $stokCols['total_in'] = $cIdx;
                } elseif ($stokCols['total_out'] === null && (str_contains($h, 'keluar') || str_contains($h, 'out'))) {
                    $stokCols['total_out'] = $cIdx;
                } elseif ($stokCols['stock'] === null && (str_contains($h, 'akhir') || str_contains($h, 'sisa') || str_contains($h, 'saldo') || $h === 'stok')) {
                    $stokCols['stock'] = $cIdx;
                }
            }
        }

        // Fallback cerdas berdasarkan struktur kolom
        if ($totalCols >= 17 && (strtolower(trim((string)($headerCols[1] ?? ''))) === 'bulan' || strtolower(trim((string)($headerCols[2] ?? ''))) === 'bulan' || $stokCols['month'] !== null)) {
            // Berkas Excel / CSV Export Banterpool (17 kolom dengan kolom Bulan)
            if ($stokCols['code'] === null) $stokCols['code'] = 0;
            if (strtolower(trim((string)($headerCols[1] ?? ''))) === 'bulan') {
                if ($stokCols['month'] === null) $stokCols['month'] = 1;
                if ($stokCols['name'] === null) $stokCols['name'] = 2;
            } else {
                if ($stokCols['name'] === null) $stokCols['name'] = 1;
                if ($stokCols['month'] === null) $stokCols['month'] = 2;
            }
            if ($stokCols['initial_stock'] === null) $stokCols['initial_stock'] = 3;
            if ($stokCols['total_in'] === null) $stokCols['total_in'] = 4;
            if ($stokCols['total_out'] === null) $stokCols['total_out'] = 5;
            if ($stokCols['stock'] === null) $stokCols['stock'] = 6;

            if ($inCols['date'] === null) $inCols['date'] = 8;
            if ($inCols['name'] === null) $inCols['name'] = 9;
            if ($inCols['amount'] === null) $inCols['amount'] = 10;
            if ($inCols['notes'] === null) $inCols['notes'] = 11;

            if ($outCols['date'] === null) $outCols['date'] = 13;
            if ($outCols['name'] === null) $outCols['name'] = 14;
            if ($outCols['amount'] === null) $outCols['amount'] = 15;
            if ($outCols['notes'] === null) $outCols['notes'] = 16;
        } elseif ($totalCols >= 16) {
            // Berkas CSV Standar Banterpool (16 kolom tanpa kolom Bulan)
            if ($stokCols['code'] === null) $stokCols['code'] = 0;
            if ($stokCols['name'] === null) $stokCols['name'] = 1;
            if ($stokCols['initial_stock'] === null) $stokCols['initial_stock'] = 2;
            if ($stokCols['total_in'] === null) $stokCols['total_in'] = 3;
            if ($stokCols['total_out'] === null) $stokCols['total_out'] = 4;
            if ($stokCols['stock'] === null) $stokCols['stock'] = 5;

            if ($inCols['date'] === null) $inCols['date'] = 7;
            if ($inCols['name'] === null) $inCols['name'] = 8;
            if ($inCols['amount'] === null) $inCols['amount'] = 9;
            if ($inCols['notes'] === null) $inCols['notes'] = 10;

            if ($outCols['date'] === null) $outCols['date'] = 12;
            if ($outCols['name'] === null) $outCols['name'] = 13;
            if ($outCols['amount'] === null) $outCols['amount'] = 14;
            if ($outCols['notes'] === null) $outCols['notes'] = 15;
        } elseif ($isDedicatedInSheet && $totalCols >= 4) {
            // Dedicated Sheet Barang Masuk: urutan [Tanggal, Nama Barang, Jumlah Masuk, Catatan]
            if ($inCols['date'] === null) $inCols['date'] = 0;
            if ($inCols['name'] === null) $inCols['name'] = 1;
            if ($inCols['amount'] === null) $inCols['amount'] = 2;
            if ($inCols['notes'] === null) $inCols['notes'] = 3;
        } elseif ($isDedicatedOutSheet && $totalCols >= 4) {
            // Dedicated Sheet Barang Keluar: urutan [Tanggal, Nama Barang, Jumlah Keluar, Catatan]
            if ($outCols['date'] === null) $outCols['date'] = 0;
            if ($outCols['name'] === null) $outCols['name'] = 1;
            if ($outCols['amount'] === null) $outCols['amount'] = 2;
            if ($outCols['notes'] === null) $outCols['notes'] = 3;
        } else {
            // Jika kolom stok belum terpetakan di sheet yang lebih sederhana
            if ($stokCols['code'] === null && $stokCols['name'] === null) {
                $stokCols['code'] = 0;
                $stokCols['name'] = 1;
            } elseif ($stokCols['name'] === null) {
                $stokCols['name'] = ($stokCols['code'] === 0) ? 1 : 0;
            }
            if ($stokCols['initial_stock'] === null) $stokCols['initial_stock'] = 2;
            if ($stokCols['total_in'] === null) $stokCols['total_in'] = 3;
            if ($stokCols['total_out'] === null) $stokCols['total_out'] = 4;
            if ($stokCols['stock'] === null) $stokCols['stock'] = 5;
        }

        $isMonthName = function(string $val): bool {
            $clean = strtolower(trim($val));
            if (empty($clean)) return false;
            $months = ['januari', 'februari', 'pebruari', 'maret', 'april', 'mei', 'juni', 'juli', 'agustus', 'september', 'oktober', 'november', 'nopember', 'desember'];
            foreach ($months as $m) {
                if (str_starts_with($clean, $m)) return true;
            }
            return false;
        };

        $isSummaryLabel = function(string $val): bool {
            $clean = strtolower(trim($val));
            return in_array($clean, [
                'total', 'total masuk', 'total keluar', 'jumlah total',
                'grand total', 'subtotal', 'nama barang', 'kode barang', 'tgl', 'tanggal'
            ]);
        };

        // 3. Iterasi setiap baris data
        // ==========================================
        // PASS 1: Laporan Master Stok Barang
        // ==========================================
        for ($i = $headerRowIndex + 1; $i < count($rows); $i++) {
            $cols = $rows[$i];
            if (empty($cols) || count(array_filter($cols, fn($v) => trim((string)$v) !== '')) === 0) {
                continue;
            }

            $code = $stokCols['code'] !== null ? trim((string)($cols[$stokCols['code']] ?? '')) : '';
            $name = $stokCols['name'] !== null ? trim((string)($cols[$stokCols['name']] ?? '')) : '';
            $rowMonth = $stokCols['month'] !== null ? trim((string)($cols[$stokCols['month']] ?? '')) : '';

            // Abaikan baris summary / total
            if ($isSummaryLabel($name) || $isSummaryLabel($code)) {
                continue;
            }

            // Jika $name berisi nama bulan (misal "Januari"), pisahkan dan cari nama barang yang asli
            if ($isMonthName($name)) {
                if (empty($rowMonth)) {
                    $rowMonth = $name;
                }
                $candidateNext = trim((string)($cols[$stokCols['name'] + 1] ?? ''));
                if (!empty($candidateNext) && !$isMonthName($candidateNext) && !$isSummaryLabel($candidateNext)) {
                    $name = $candidateNext;
                } else {
                    $candidatePrev = trim((string)($cols[$stokCols['name'] - 1] ?? ''));
                    if (!empty($candidatePrev) && !$isMonthName($candidatePrev) && $stokCols['name'] - 1 !== $stokCols['code'] && !$isSummaryLabel($candidatePrev)) {
                        $name = $candidatePrev;
                    }
                }
            }

            // Jika nama barang masih kosong atau berisi bulan, cari kolom teks pertama di seksi stok
            if (empty($name) || $isMonthName($name)) {
                for ($c = 0; $c < min(count($cols), 6); $c++) {
                    $val = trim((string)($cols[$c] ?? ''));
                    if (!empty($val) && !$isMonthName($val) && !is_numeric($val) && $c !== $stokCols['code'] && !$isSummaryLabel($val)) {
                        $name = $val;
                        break;
                    }
                }
            }

            // Jika nama barang ada (meskipun kode barang kosong/keterangan tidak lengkap):
            if (!empty($name) && !$isMonthName($name) && !$isSummaryLabel($name)) {
                // Lengkapi kode barang jika kosong
                if (empty($code)) {
                    $existingMat = $this->findMaterialHelper($name, $materialMap);
                    $code = $existingMat ? $existingMat->code : $this->generateNextMaterialCode($assignedCodes);
                }

                $stokAwalStr = $stokCols['initial_stock'] !== null ? trim((string)($cols[$stokCols['initial_stock']] ?? '')) : '';
                $masukStr = $stokCols['total_in'] !== null ? trim((string)($cols[$stokCols['total_in']] ?? '')) : '';
                $keluarStr = $stokCols['total_out'] !== null ? trim((string)($cols[$stokCols['total_out']] ?? '')) : '';
                $stokAkhirStr = $stokCols['stock'] !== null ? trim((string)($cols[$stokCols['stock']] ?? '')) : '';

                $stokAwal = $this->parseNumberHelper($stokAwalStr);
                $totalIn = $this->parseNumberHelper($masukStr);
                $totalOut = $this->parseNumberHelper($keluarStr);
                $stokAkhir = $stokAkhirStr !== '' ? $this->parseNumberHelper($stokAkhirStr) : ($stokAwal + $totalIn - $totalOut);

                $itemMonth = !empty($rowMonth) ? $rowMonth : $fileMonthName;
                $itemYear = $fileYear;
                if (preg_match('/(\d{4})/', $itemMonth, $yMatch)) {
                    $itemYear = (int)$yMatch[1];
                    $itemMonth = trim(str_replace($yMatch[1], '', $itemMonth));
                }

                $category = $this->detectCategoryHelper($name);
                $unit = $this->detectUnitHelper($name);

                $mat = $materialMap[$code] ?? null;
                if (!$mat) {
                    $cleanName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name));
                    $mat = $materialMap['name_' . $cleanName] ?? null;
                }

                if (!$mat) {
                    $mat = Material::create([
                        'code' => $code,
                        'name' => $name,
                        'category' => $category,
                        'unit' => $unit,
                        'month' => $itemMonth,
                        'year' => $itemYear,
                        'initial_stock' => $stokAwal,
                        'total_in' => $totalIn,
                        'total_out' => $totalOut,
                        'stock' => $stokAkhir,
                        'min_stock' => 5,
                        'unit_price' => 0,
                    ]);
                    $importedMaterials++;
                } else {
                    $mat->update([
                        'code' => $code ?: $mat->code,
                        'name' => $name,
                        'category' => $mat->category ?: $category,
                        'unit' => $mat->unit ?: $unit,
                        'month' => $itemMonth,
                        'year' => $itemYear,
                        'initial_stock' => $stokAwal,
                        'total_in' => $totalIn,
                        'total_out' => $totalOut,
                        'stock' => $stokAkhir,
                    ]);
                    $importedMaterials++;
                }

                // Tentukan nomor bulan untuk item ini secara akurat
                $monthLookup = [
                    'januari' => 1, 'februari' => 2, 'pebruari' => 2, 'maret' => 3, 'april' => 4,
                    'mei' => 5, 'juni' => 6, 'juli' => 7, 'agustus' => 8, 'september' => 9,
                    'oktober' => 10, 'november' => 11, 'nopember' => 11, 'desember' => 12,
                ];
                $cleanMonthLower = strtolower(trim($itemMonth));
                $itemMonthNumber = $fileMonthNumber;
                foreach ($monthLookup as $mK => $mV) {
                    if (str_starts_with($cleanMonthLower, $mK)) {
                        $itemMonthNumber = $mV;
                        break;
                    }
                }

                // SIMPAN SNAPSHOT STOK BULANAN (Menjamin data bulan sebelumnya tidak tertimpa/hilang)
                MaterialMonthlyStock::updateOrCreate(
                    [
                        'material_id' => $mat->id,
                        'month' => $itemMonthNumber,
                        'year' => $itemYear,
                    ],
                    [
                        'month_name' => $itemMonth,
                        'initial_stock' => $stokAwal,
                        'total_in' => $totalIn,
                        'total_out' => $totalOut,
                        'final_stock' => $stokAkhir,
                    ]
                );

                $materialMap[$code] = $mat;
                $cleanName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name));
                $materialMap['name_' . $cleanName] = $mat;
            }
        }

        // ==========================================
        // PASS 2: Transaksi Barang Masuk dan Barang Keluar
        // Sesuai Aturan:
        // 1. Barang Masuk: dibaca mulai dari kolom tanggal (urutan tanggal-bulan-tahun),
        //    kemudian kolom nama barang dan isi dari nama barangnya,
        //    kemudian kolom jumlah masuk dari barangnya,
        //    serta yang terakhir yaitu kolom catatan.
        // 2. Barang Keluar: dibaca mulai dari kolom tanggal (urutan tanggal-bulan-tahun),
        //    kemudian kolom nama barang dan isi dari nama barangnya,
        //    kemudian kolom jumlah keluar dari barangnya,
        //    serta yang terakhir yaitu kolom catatan.
        // ==========================================
        for ($i = $headerRowIndex + 1; $i < count($rows); $i++) {
            $cols = $rows[$i];
            if (empty($cols) || count(array_filter($cols, fn($v) => trim((string)$v) !== '')) === 0) {
                continue;
            }

            // ATURAN 1: TABEL BARANG MASUK
            // 1. Tanggal (urutan tanggal-bulan-tahun) -> 2. Nama Barang -> 3. Jumlah Masuk -> 4. Catatan
            if ($inCols['name'] !== null && $inCols['amount'] !== null) {
                // 1. Kolom Tanggal (urutan tanggal-bulan-tahun)
                $tglMasuk = $inCols['date'] !== null ? trim((string)($cols[$inCols['date']] ?? '')) : '';
                // 2. Kolom Nama Barang dan isinya
                $namaMasuk = trim((string)($cols[$inCols['name']] ?? ''));
                // 3. Kolom Jumlah Masuk dari barangnya
                $jmlMasukStr = trim((string)($cols[$inCols['amount']] ?? ''));
                // 4. Kolom Catatan
                $catatanMasuk = $inCols['notes'] !== null ? trim((string)($cols[$inCols['notes']] ?? '')) : '';

                if (!empty($namaMasuk) && $jmlMasukStr !== '' && !$isSummaryLabel($namaMasuk) && !$isMonthName($namaMasuk)) {
                    $amt = $this->parseNumberHelper($jmlMasukStr);
                    if ($amt > 0) {
                        $mat = $this->findMaterialHelper($namaMasuk, $materialMap);
                        if (!$mat) {
                            // Otomatis buat master material jika belum ada di tabel utama
                            $autoCode = $this->generateNextMaterialCode($assignedCodes);
                            $mat = Material::create([
                                'code' => $autoCode,
                                'name' => $namaMasuk,
                                'category' => $this->detectCategoryHelper($namaMasuk),
                                'unit' => $this->detectUnitHelper($namaMasuk),
                                'month' => $fileMonthName,
                                'year' => $fileYear,
                                'initial_stock' => 0,
                                'total_in' => $amt,
                                'total_out' => 0,
                                'stock' => $amt,
                                'min_stock' => 5,
                                'unit_price' => 0,
                            ]);
                            MaterialMonthlyStock::updateOrCreate(
                                [
                                    'material_id' => $mat->id,
                                    'month' => $fileMonthNumber,
                                    'year' => $fileYear,
                                ],
                                [
                                    'month_name' => $fileMonthName,
                                    'initial_stock' => 0,
                                    'total_in' => $amt,
                                    'total_out' => 0,
                                    'final_stock' => $amt,
                                ]
                            );
                            $materialMap[$autoCode] = $mat;
                            $cleanName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $namaMasuk));
                            $materialMap['name_' . $cleanName] = $mat;
                            $importedMaterials++;
                        }

                        // Parse tanggal dengan urutan prioritas tanggal-bulan-tahun
                        $dt = $this->parseDateTimeWithPeriod($tglMasuk, $fileMonthNumber, $fileYear, 9);
                        $dateStr = $dt->toDateString();

                        MaterialMutation::create([
                            'material_id' => $mat->id,
                            'user_id' => $adminId,
                            'type' => 'in',
                            'transaction_date' => $dateStr,
                            'amount' => $amt,
                            'stock_before' => (float)$mat->stock,
                            'stock_after' => (float)$mat->stock + $amt,
                            'notes' => $catatanMasuk ?: 'Barang Masuk',
                            'created_at' => $dt,
                            'updated_at' => $dt,
                        ]);

                        $materialInSums[$mat->id] = ($materialInSums[$mat->id] ?? 0) + $amt;
                        $importedIn++;
                    }
                }
            }

            // ATURAN 2: TABEL BARANG KELUAR
            // 1. Tanggal (urutan tanggal-bulan-tahun) -> 2. Nama Barang -> 3. Jumlah Keluar -> 4. Catatan
            if ($outCols['name'] !== null && $outCols['amount'] !== null) {
                // 1. Kolom Tanggal (urutan tanggal-bulan-tahun)
                $tglKeluar = $outCols['date'] !== null ? trim((string)($cols[$outCols['date']] ?? '')) : '';
                // 2. Kolom Nama Barang dan isinya
                $namaKeluar = trim((string)($cols[$outCols['name']] ?? ''));
                // 3. Kolom Jumlah Keluar dari barangnya
                $jmlKeluarStr = trim((string)($cols[$outCols['amount']] ?? ''));
                // 4. Kolom Catatan
                $catatanKeluar = $outCols['notes'] !== null ? trim((string)($cols[$outCols['notes']] ?? '')) : '';

                if (!empty($namaKeluar) && $jmlKeluarStr !== '' && !$isSummaryLabel($namaKeluar) && !$isMonthName($namaKeluar)) {
                    $amt = $this->parseNumberHelper($jmlKeluarStr);
                    if ($amt > 0) {
                        $mat = $this->findMaterialHelper($namaKeluar, $materialMap);
                        if (!$mat) {
                            // Otomatis buat master material jika belum ada di tabel utama
                            $autoCode = $this->generateNextMaterialCode($assignedCodes);
                            $mat = Material::create([
                                'code' => $autoCode,
                                'name' => $namaKeluar,
                                'category' => $this->detectCategoryHelper($namaKeluar),
                                'unit' => $this->detectUnitHelper($namaKeluar),
                                'month' => $fileMonthName,
                                'year' => $fileYear,
                                'initial_stock' => 0,
                                'total_in' => 0,
                                'total_out' => $amt,
                                'stock' => -$amt,
                                'min_stock' => 5,
                                'unit_price' => 0,
                            ]);
                            MaterialMonthlyStock::updateOrCreate(
                                [
                                    'material_id' => $mat->id,
                                    'month' => $fileMonthNumber,
                                    'year' => $fileYear,
                                ],
                                [
                                    'month_name' => $fileMonthName,
                                    'initial_stock' => 0,
                                    'total_in' => 0,
                                    'total_out' => $amt,
                                    'final_stock' => -$amt,
                                ]
                            );
                            $materialMap[$autoCode] = $mat;
                            $cleanName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $namaKeluar));
                            $materialMap['name_' . $cleanName] = $mat;
                            $importedMaterials++;
                        }

                        // Parse tanggal dengan urutan prioritas tanggal-bulan-tahun
                        $dt = $this->parseDateTimeWithPeriod($tglKeluar, $fileMonthNumber, $fileYear, 14);
                        $dateStr = $dt->toDateString();

                        MaterialMutation::create([
                            'material_id' => $mat->id,
                            'user_id' => $adminId,
                            'type' => 'out',
                            'transaction_date' => $dateStr,
                            'amount' => $amt,
                            'stock_before' => (float)$mat->stock,
                            'stock_after' => (float)$mat->stock - $amt,
                            'notes' => $catatanKeluar ?: 'Barang Keluar',
                            'created_at' => $dt,
                            'updated_at' => $dt,
                        ]);

                        $materialOutSums[$mat->id] = ($materialOutSums[$mat->id] ?? 0) + $amt;
                        $importedOut++;
                    }
                }
            }
        }
    }

    /**
     * Ekspor Data ke Berkas CSV Sesuai Format Template Asli Banterpool (Mendukung Pilihan Bulan)
     */
    public function exportCsv(Request $request)
    {
        $monthInput = $request->input('month', '10');
        $yearInput = (int) $request->input('year', 2026);
        $selectedMonth = $monthInput === 'all' ? 'all' : (int) $monthInput;
        $selectedYear = $yearInput ?: 2026;
        $isMonthly = $selectedMonth !== 'all';
        $monthName = $isMonthly ? (self::MONTHS[$selectedMonth] ?? 'Oktober') : 'Semua Periode';

        if ($isMonthly) {
            $startDate = Carbon::createFromDate($selectedYear, $selectedMonth, 1)->startOfMonth()->toDateString();
            $endDate = Carbon::createFromDate($selectedYear, $selectedMonth, 1)->endOfMonth()->toDateString();
            $materials = $this->getMonthlyCalculatedMaterials($startDate, $endDate, $selectedMonth, $selectedYear);

            $incoming = MaterialMutation::where('type', 'in')->with('material')
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('transaction_date', [$startDate, $endDate])
                      ->orWhere(function ($s) use ($startDate, $endDate) {
                          $s->whereNull('transaction_date')->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate]);
                      });
                })->orderBy('transaction_date', 'asc')->get();

            $outgoing = MaterialMutation::where('type', 'out')->with('material')
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('transaction_date', [$startDate, $endDate])
                      ->orWhere(function ($s) use ($startDate, $endDate) {
                          $s->whereNull('transaction_date')->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate]);
                      });
                })->orderBy('transaction_date', 'asc')->get();

            $tglAwal = Carbon::parse($startDate)->format('d/m/Y');
            $tglClosing = Carbon::parse($endDate)->format('d/m/Y');
        } else {
            $materials = MaterialMonthlyStock::with('material')
                ->orderBy('year', 'desc')
                ->orderBy('month', 'asc')
                ->get()
                ->map(function ($ms) {
                    $mat = $ms->material ?: new Material();
                    $item = clone $mat;
                    $item->id = $mat->id ?? $ms->id;
                    $item->computed_initial_stock = (float) $ms->initial_stock;
                    $item->computed_total_in = (float) $ms->total_in;
                    $item->computed_total_out = (float) $ms->total_out;
                    $item->computed_stock = (float) $ms->final_stock;
                    $item->display_month = $ms->month_name ?: (self::MONTHS[$ms->month] ?? 'Semua Periode');
                    $item->month = $item->display_month;
                    return $item;
                });
            if ($materials->isEmpty()) {
                $materials = Material::orderByRaw("LENGTH(code) ASC, code ASC")->get()->map(function ($m) {
                    $m->computed_initial_stock = (float) $m->initial_stock;
                    $m->computed_total_in = (float) $m->total_in;
                    $m->computed_total_out = (float) $m->total_out;
                    $m->computed_stock = (float) $m->stock;
                    return $m;
                });
            }
            $incoming = MaterialMutation::where('type', 'in')->with('material')->orderBy('transaction_date', 'asc')->get();
            $outgoing = MaterialMutation::where('type', 'out')->with('material')->orderBy('transaction_date', 'asc')->get();
            $tglAwal = 'Awal';
            $tglClosing = date('d/m/Y');
        }

        $maxRows = max($materials->count(), $incoming->count(), $outgoing->count());
        $filename = 'Laporan_Stok_Barang_' . Str::slug($monthName) . '_' . $selectedYear . '_' . date('His') . '.csv';

        return response()->streamDownload(function () use ($materials, $incoming, $outgoing, $maxRows, $monthName, $selectedYear, $tglAwal, $tglClosing) {
            echo "\xEF\xBB\xBF";
            $delimiter = ';';

            echo "LAPORAN STOK BARANG ({$monthName} {$selectedYear});;;;;;;BARANG MASUK;;;;;Barang Keluar;;;;;;\r\n";
            echo ";;;;;;;;;;;;;;;;;;\r\n";
            echo "Tgl Stok Awal;{$tglAwal};;;;;;;;;;;;;;;\r\n";
            echo "Tgl Closing;{$tglClosing};;;;;;;;;;;;;;;\r\n";
            echo ";;;;;;;;;;;;;;;;;;\r\n";
            echo "Kode Barang;Bulan;Nama Barang;Stok Awal;Barang Masuk;Barang Keluar;Stok Akhir;;Tgl;Nama Barang;Jumlah Masuk;Catatan;;Tgl;Nama Barang;Jumlah Keluar;Catatan;;;\r\n";

            for ($i = 0; $i < $maxRows; $i++) {
                $mat = $materials->get($i);
                $in = $incoming->get($i);
                $out = $outgoing->get($i);

                $matCols = $mat ? [
                    $mat->code,
                    $mat->display_month ?? $mat->month ?? $monthName,
                    $mat->name,
                    Material::formatQty($mat->computed_initial_stock ?? $mat->initial_stock),
                    Material::formatQty($mat->computed_total_in ?? $mat->total_in),
                    Material::formatQty($mat->computed_total_out ?? $mat->total_out),
                    Material::formatQty($mat->computed_stock ?? $mat->stock),
                ] : ['', '', '', '', '', '', ''];

                $inCols = $in ? [
                    $in->transaction_date ? $in->transaction_date->format('d/m/Y') : $in->created_at->format('d/m/Y'),
                    $in->material->name ?? '-',
                    Material::formatQty($in->amount),
                    str_replace([' (Barang Masuk)', ' [Import]'], '', $in->notes ?? ''),
                ] : ['', '', '', ''];

                $outCols = $out ? [
                    $out->transaction_date ? $out->transaction_date->format('d/m/Y') : $out->created_at->format('d/m/Y'),
                    $out->material->name ?? '-',
                    Material::formatQty($out->amount),
                    str_replace([' (Barang Keluar)', ' [Import]'], '', $out->notes ?? ''),
                ] : ['', '', '', ''];

                $row = array_merge(
                    $matCols,
                    [''],
                    $inCols,
                    [''],
                    $outCols,
                    ['', '', '']
                );

                echo implode($delimiter, $row) . "\r\n";
            }
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Ekspor Data Stok Material ke Excel (.xls Spreadsheet XML) Sesuai Pilihan Bulan
     */
    public function export(Request $request)
    {
        $monthInput = $request->input('month', '10');
        $yearInput = (int) $request->input('year', 2026);
        $selectedMonth = $monthInput === 'all' ? 'all' : (int) $monthInput;
        $selectedYear = $yearInput ?: 2026;
        $isMonthly = $selectedMonth !== 'all';
        $monthName = $isMonthly ? (self::MONTHS[$selectedMonth] ?? 'Oktober') : 'Semua Periode';

        if ($isMonthly) {
            $startDate = Carbon::createFromDate($selectedYear, $selectedMonth, 1)->startOfMonth()->toDateString();
            $endDate = Carbon::createFromDate($selectedYear, $selectedMonth, 1)->endOfMonth()->toDateString();
            $materials = $this->getMonthlyCalculatedMaterials($startDate, $endDate, $selectedMonth, $selectedYear);

            $incoming = MaterialMutation::where('type', 'in')->with('material')
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('transaction_date', [$startDate, $endDate])
                      ->orWhere(function ($s) use ($startDate, $endDate) {
                          $s->whereNull('transaction_date')->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate]);
                      });
                })->orderBy('transaction_date', 'asc')->get();

            $outgoing = MaterialMutation::where('type', 'out')->with('material')
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('transaction_date', [$startDate, $endDate])
                      ->orWhere(function ($s) use ($startDate, $endDate) {
                          $s->whereNull('transaction_date')->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate]);
                      });
                })->orderBy('transaction_date', 'asc')->get();

            $tglAwal = Carbon::parse($startDate)->format('d/m/Y');
            $tglClosing = Carbon::parse($endDate)->format('d/m/Y');
        } else {
            $materials = MaterialMonthlyStock::with('material')
                ->orderBy('year', 'desc')
                ->orderBy('month', 'asc')
                ->get()
                ->map(function ($ms) {
                    $mat = $ms->material ?: new Material();
                    $item = clone $mat;
                    $item->id = $mat->id ?? $ms->id;
                    $item->computed_initial_stock = (float) $ms->initial_stock;
                    $item->computed_total_in = (float) $ms->total_in;
                    $item->computed_total_out = (float) $ms->total_out;
                    $item->computed_stock = (float) $ms->final_stock;
                    $item->display_month = $ms->month_name ?: (self::MONTHS[$ms->month] ?? 'Semua Periode');
                    $item->month = $item->display_month;
                    return $item;
                });
            if ($materials->isEmpty()) {
                $materials = Material::orderByRaw("LENGTH(code) ASC, code ASC")->get()->map(function ($m) {
                    $m->computed_initial_stock = (float) $m->initial_stock;
                    $m->computed_total_in = (float) $m->total_in;
                    $m->computed_total_out = (float) $m->total_out;
                    $m->computed_stock = (float) $m->stock;
                    return $m;
                });
            }
            $incoming = MaterialMutation::where('type', 'in')->with('material')->orderBy('transaction_date', 'asc')->get();
            $outgoing = MaterialMutation::where('type', 'out')->with('material')->orderBy('transaction_date', 'asc')->get();
            $tglAwal = 'Awal';
            $tglClosing = date('d/m/Y');
        }

        $filename = 'Laporan_Stok_Barang_' . Str::slug($monthName) . '_' . $selectedYear . '_' . date('His') . '.xls';

        return response()->streamDownload(function () use ($materials, $incoming, $outgoing, $monthName, $selectedYear, $tglAwal, $tglClosing) {
            echo "\xEF\xBB\xBF";
            echo view('admin.exports.material_excel', compact('materials', 'incoming', 'outgoing', 'monthName', 'selectedYear', 'tglAwal', 'tglClosing'))->render();
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

        $query = MaterialMutation::with(['material', 'user'])->latest('id');

        if (!empty($materialId)) {
            $query->where('material_id', $materialId);
        }

        if ($type !== 'all') {
            $query->where('type', $type);
        }

        $mutations = $query->paginate(35)->withQueryString();
        $materials = Material::select('id', 'name', 'code', 'unit')->orderBy('name')->get();

        return view('admin.material.mutations', compact('mutations', 'materials', 'materialId', 'type'));
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================
    private function parseNumberHelper(string $val): float
    {
        $val = trim($val);
        if ($val === '' || $val === '-' || $val === 'null') return 0.0;

        // Hilangkan spasi dan non-breaking spaces
        $val = str_replace(["\xc2\xa0", "\u{00a0}", ' '], '', $val);

        // Format: "1.250,50" -> 1250.50
        if (preg_match('/^-?\d{1,3}(\.\d{3})+,\d+$/', $val)) {
            $val = str_replace('.', '', $val);
            $val = str_replace(',', '.', $val);
        }
        // Format: "1,250.50" -> 1250.50
        elseif (preg_match('/^-?\d{1,3}(,\d{3})+\.\d+$/', $val)) {
            $val = str_replace(',', '', $val);
        }
        // Format: "1.000" (pemisah ribuan Indonesia tanpa desimal)
        elseif (preg_match('/^-?\d{1,3}(\.\d{3})+$/', $val)) {
            $val = str_replace('.', '', $val);
        }
        // Format: "1,000" (pemisah ribuan internasional tanpa desimal)
        elseif (preg_match('/^-?\d{1,3}(,\d{3})+$/', $val)) {
            $val = str_replace(',', '', $val);
        }
        // Format: "0,5" atau "16,5" (desimal koma Indonesia)
        elseif (str_contains($val, ',') && !str_contains($val, '.')) {
            $val = str_replace(',', '.', $val);
        }

        $val = preg_replace('/[^0-9\.\-]/', '', $val);
        return is_numeric($val) ? (float) $val : 0.0;
    }

    /**
     * Otomatis membuat kode barang unik berikutnya (format A001, A002, ...) jika kode barang tidak lengkap / kosong.
     */
    private function generateNextMaterialCode(array &$assignedCodes): string
    {
        $allCodes = Material::pluck('code')->all();
        $allCodes = array_merge($allCodes, $assignedCodes);

        $maxNum = 0;
        foreach ($allCodes as $c) {
            if (preg_match('/^A(\d+)$/i', $c, $m)) {
                $num = (int)$m[1];
                if ($num > $maxNum) {
                    $maxNum = $num;
                }
            }
        }

        $nextNum = $maxNum + 1;
        $candidate = 'A' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
        while (in_array($candidate, $allCodes)) {
            $nextNum++;
            $candidate = 'A' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
        }

        $assignedCodes[] = $candidate;
        return $candidate;
    }

    /**
     * Mencari objek Material berdasarkan nama (case-insensitive, tanpa karakter khusus, atau kecocokan sebagian).
     */
    private function findMaterialHelper(string $name, array &$materialMap): ?Material
    {
        $clean = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name));
        if (empty($clean)) return null;

        // 1. Cek langsung dari in-memory cache
        if (isset($materialMap['name_' . $clean])) {
            return $materialMap['name_' . $clean];
        }

        // 2. Pencarian eksak di basis data
        $found = Material::whereRaw('LOWER(TRIM(name)) = ?', [strtolower(trim($name))])->first();
        if ($found) {
            $materialMap['name_' . $clean] = $found;
            $materialMap[$found->code] = $found;
            return $found;
        }

        // 3. Pencarian fuzzy di in-memory cache (substring)
        foreach ($materialMap as $k => $m) {
            if (str_starts_with($k, 'name_')) {
                $mClean = substr($k, 5);
                if ($mClean === $clean || str_contains($mClean, $clean) || str_contains($clean, $mClean)) {
                    return $m;
                }
            }
        }

        // 4. Pencarian LIKE di basis data
        $found = Material::where('name', 'like', '%' . trim($name) . '%')->first();
        if ($found) {
            $materialMap['name_' . $clean] = $found;
            $materialMap[$found->code] = $found;
            return $found;
        }

        return null;
    }

    /**
     * Mem-parse string tanggal ke Carbon dengan mempertimbangkan bulan & tahun periode file yang sedang diimpor.
     * Mampu mendeteksi secara akurat apakah tanggal berformat DD/MM/YYYY atau MM/DD/YYYY,
     * serta menangani angka serial Excel atau tanggal kosong (default ke tgl 1 bulan tersebut).
     */
    private function parseDateTimeWithPeriod($val, int $fileMonth, int $fileYear, int $defaultHour = 9): Carbon
    {
        if (empty($val)) {
            return Carbon::create($fileYear, $fileMonth, 1, $defaultHour, 0, 0);
        }

        if ($val instanceof \DateTimeInterface) {
            return Carbon::instance($val);
        }

        $str = trim(str_replace(["\xc2\xa0", "\u{00a0}"], ' ', (string)$val));
        if ($str === '' || $str === '-' || $str === '0') {
            return Carbon::create($fileYear, $fileMonth, 1, $defaultHour, 0, 0);
        }

        // 1. Angka serial Excel (misal: 46305 atau 46305.58)
        if (is_numeric($str) && (float)$str > 1000 && (float)$str < 60000) {
            try {
                if (class_exists(\PhpOffice\PhpSpreadsheet\Shared\Date::class)) {
                    $dt = Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$str));
                    $fraction = (float)$str - floor((float)$str);
                    if ($fraction <= 0) {
                        $dt->setTime($defaultHour, 0, 0);
                    }
                    return $dt;
                }
                $num = (float)$str;
                $days = (int) floor($num);
                $fraction = $num - $days;
                $dt = Carbon::create(1899, 12, 30, 0, 0, 0)->addDays($days);
                if ($fraction > 0) {
                    $seconds = (int) round($fraction * 86400);
                    $dt->addSeconds($seconds);
                } else {
                    $dt->setTime($defaultHour, 0, 0);
                }
                return $dt;
            } catch (\Exception $e) {}
        }

        // 2. Hanya angka hari (1 s.d. 31), misal pada laporan harian di mana kolom tanggal hanya berisi angka hari
        if (preg_match('/^(\d{1,2})$/', $str, $dayMatch)) {
            $dayOnly = (int)$dayMatch[1];
            if (checkdate($fileMonth, $dayOnly, $fileYear)) {
                return Carbon::create($fileYear, $fileMonth, $dayOnly, $defaultHour, 0, 0);
            }
        }

        // 3. Format tanggal numerik: A/B/C, A-B-C, atau A.B.C dengan urutan membaca TANGGAL-BULAN-TAHUN
        if (preg_match('/^(\d{1,2})[\/\.-](\d{1,2})[\/\.-](\d{2,4})(?:[ \tT]+(\d{1,2}):(\d{1,2})(?::(\d{1,2}))?)?$/', $str, $m)) {
            $a = (int)$m[1];
            $b = (int)$m[2];
            $year = (int)$m[3];
            if ($year < 100) {
                $year += ($year < 70 ? 2000 : 1900);
            }
            $hour = isset($m[4]) ? (int)$m[4] : $defaultHour;
            $min = isset($m[5]) ? (int)$m[5] : 0;
            $sec = isset($m[6]) ? (int)$m[6] : 0;

            if ($a > 12) {
                $day = $a;
                $month = $b;
            } elseif ($b > 12) {
                $month = $a;
                $day = $b;
            } elseif ($b === $fileMonth) {
                $day = $a;
                $month = $b;
            } elseif ($a === $fileMonth) {
                $month = $a;
                $day = $b;
            } else {
                $day = $a;
                $month = $b;
            }

            if (checkdate($month, $day, $year)) {
                return Carbon::create($year, $month, $day, $hour, $min, $sec);
            }

            // Fallback swap jika tanggal tidak valid
            if (checkdate($day, $month, $year)) {
                return Carbon::create($year, $day, $month, $hour, $min, $sec);
            }
        }

        // 4. Format tanggal dan bulan saja (tanpa tahun): DD/MM atau DD-MM
        if (preg_match('/^(\d{1,2})[\/\.-](\d{1,2})$/', $str, $m)) {
            $a = (int)$m[1];
            $b = (int)$m[2];
            if ($a > 12) {
                $day = $a;
                $month = $b;
            } elseif ($b > 12) {
                $month = $a;
                $day = $b;
            } elseif ($b === $fileMonth) {
                $day = $a;
                $month = $b;
            } elseif ($a === $fileMonth) {
                $month = $a;
                $day = $b;
            } else {
                $day = $a;
                $month = $b;
            }
            if (checkdate($month, $day, $fileYear)) {
                return Carbon::create($fileYear, $month, $day, $defaultHour, 0, 0);
            }
        }

        // 5. Fallback ke helper tanggal bawaan
        return $this->parseDateTimeHelper($val, $defaultHour);
    }

    /**
     * Parse string / nilai waktu dari berkas Excel atau CSV dengan prioritas format tanggal-bulan-tahun (d-m-Y).
     */
    private function parseDateTimeHelper($val, int $defaultHour = 9): Carbon
    {
        if (empty($val)) {
            return Carbon::now()->setTime($defaultHour, 0, 0);
        }

        if ($val instanceof \DateTimeInterface) {
            return Carbon::instance($val);
        }

        $str = trim(str_replace(["\xc2\xa0", "\u{00a0}"], ' ', (string)$val));
        if ($str === '' || $str === '-' || $str === '0') {
            return Carbon::now()->setTime($defaultHour, 0, 0);
        }

        // 1. Angka serial Excel (misal: 46305 atau 46305.58)
        if (is_numeric($str) && (float)$str > 1000 && (float)$str < 60000) {
            try {
                $num = (float)$str;
                $days = (int) floor($num);
                $fraction = $num - $days;
                $dt = Carbon::create(1899, 12, 30, 0, 0, 0)->addDays($days);
                if ($fraction > 0) {
                    $seconds = (int) round($fraction * 86400);
                    $dt->addSeconds($seconds);
                } else {
                    $dt->setTime($defaultHour, 0, 0);
                }
                return $dt;
            } catch (\Exception $e) {}
        }

        // 2. Format tanggal-bulan-tahun: DD/MM/YYYY, DD-MM-YYYY, DD.MM.YYYY beserta jam opsional
        // Urutan: $1 = Tanggal (d), $2 = Bulan (m), $3 = Tahun (Y)
        if (preg_match('/^(\d{1,2})[\/\.-](\d{1,2})[\/\.-](\d{2,4})(?:[ \tT]+(\d{1,2}):(\d{1,2})(?::(\d{1,2}))?)?$/', $str, $m)) {
            $a = (int)$m[1];
            $b = (int)$m[2];
            $year = (int)$m[3];
            if ($year < 100) {
                $year += ($year < 70 ? 2000 : 1900);
            }
            $hour = isset($m[4]) ? (int)$m[4] : $defaultHour;
            $min = isset($m[5]) ? (int)$m[5] : 0;
            $sec = isset($m[6]) ? (int)$m[6] : 0;

            if ($b > 12) {
                $month = $a;
                $day = $b;
            } else {
                $day = $a;
                $month = $b;
            }

            if (checkdate($month, $day, $year)) {
                return Carbon::create($year, $month, $day, $hour, $min, $sec);
            }
        }

        // 3. Format dengan nama bulan (Indonesia atau Inggris) diawali tanggal:
        // Contoh: "19 Oktober 2026", "19 Okt 2026 14:30", "19-Okt-2026", "19 Oktober"
        $indoMonths = [
            'januari' => 1, 'jan' => 1,
            'februari' => 2, 'pebruari' => 2, 'feb' => 2,
            'maret' => 3, 'mar' => 3,
            'april' => 4, 'apr' => 4,
            'mei' => 5, 'may' => 5,
            'juni' => 6, 'jun' => 6,
            'juli' => 7, 'jul' => 7,
            'agustus' => 8, 'ags' => 8, 'agt' => 8, 'aug' => 8,
            'september' => 9, 'sep' => 9, 'sept' => 9,
            'oktober' => 10, 'okt' => 10, 'oct' => 10,
            'november' => 11, 'nopember' => 11, 'nov' => 11, 'nop' => 11,
            'desember' => 12, 'des' => 12, 'dec' => 12,
        ];

        if (preg_match('/^(\d{1,2})[ \t\/\.-]+([a-zA-Z]+)(?:[ \t\/\.-]+(\d{2,4}))?(?:[ \tT]+(\d{1,2}):(\d{1,2})(?::(\d{1,2}))?)?$/', $str, $m)) {
            $day = (int)$m[1];
            $monthStr = strtolower($m[2]);
            if (isset($indoMonths[$monthStr])) {
                $month = $indoMonths[$monthStr];
                $year = !empty($m[3]) ? (int)$m[3] : (int)date('Y');
                if ($year < 100) {
                    $year += ($year < 70 ? 2000 : 1900);
                }
                $hour = isset($m[4]) ? (int)$m[4] : $defaultHour;
                $min = isset($m[5]) ? (int)$m[5] : 0;
                $sec = isset($m[6]) ? (int)$m[6] : 0;
                if (checkdate($month, $day, $year)) {
                    return Carbon::create($year, $month, $day, $hour, $min, $sec);
                }
            }
        }

        // 4. Format ISO jika berkas menggunakan tahun-bulan-tanggal (YYYY-MM-DD)
        if (preg_match('/^(\d{4})[\/\.-](\d{1,2})[\/\.-](\d{1,2})(?:[ \tT]+(\d{1,2}):(\d{1,2})(?::(\d{1,2}))?)?$/', $str, $m)) {
            $year = (int)$m[1];
            $month = (int)$m[2];
            $day = (int)$m[3];
            $hour = isset($m[4]) ? (int)$m[4] : $defaultHour;
            $min = isset($m[5]) ? (int)$m[5] : 0;
            $sec = isset($m[6]) ? (int)$m[6] : 0;

            if (checkdate($month, $day, $year)) {
                return Carbon::create($year, $month, $day, $hour, $min, $sec);
            }
        }

        // 5. Fallback ke terjemahan nama bulan dan Carbon::parse
        $enMonths = [
            'januari' => 'january', 'pebruari' => 'february', 'februari' => 'february',
            'maret' => 'march', 'mei' => 'may', 'juni' => 'june', 'juli' => 'july',
            'agustus' => 'august', 'ags' => 'aug', 'agt' => 'aug',
            'oktober' => 'october', 'okt' => 'oct',
            'nopember' => 'november', 'nop' => 'nov', 'desember' => 'december',
        ];
        $translated = strtolower($str);
        foreach ($enMonths as $idName => $enName) {
            $translated = preg_replace('/\b' . $idName . '\b/i', $enName, $translated);
        }

        try {
            return Carbon::parse($translated);
        } catch (\Exception $e) {
            return Carbon::now()->setTime($defaultHour, 0, 0);
        }
    }

    private function parseDateHelper($val): string
    {
        return $this->parseDateTimeHelper($val)->toDateString();
    }

    private function detectCategoryHelper(string $name): string
    {
        $lower = strtolower($name);
        if (str_contains($lower, 'tiang')) return 'Tiang & Bracket';
        if (str_contains($lower, 'slack')) return 'Aksesoris & Bracket';
        if (str_contains($lower, 'clamp') || str_contains($lower, 'klamp') || str_contains($lower, 'helical') || str_contains($lower, 'helocal')) return 'Clamp & Aksesoris';
        if (str_contains($lower, 'joint closure') || str_contains($lower, 'closure')) return 'Joint Closure';
        if (str_contains($lower, 'odp')) return 'ODP & Box Pasif';
        if (str_contains($lower, 'dc ') || str_contains($lower, 'jellytube') || str_contains($lower, 'fig8') || str_contains($lower, 'armore') || str_contains($lower, 'mini adss') || str_contains($lower, 'ku ')) return 'Kabel Drop & Fiber Optik';
        if (str_contains($lower, 'kabel lan')) return 'Kabel LAN & UTP';
        if (str_contains($lower, 'pigtail') || str_contains($lower, 'patchcore') || str_contains($lower, 'patchcord')) return 'Patchcord & Pigtail';
        if (str_contains($lower, 'adapter')) return 'Adapter & Konektor Optik';
        if (str_contains($lower, 'spliter') || str_contains($lower, 'splitter')) return 'Splitter Optik';
        if (str_contains($lower, 'router') || str_contains($lower, 'ap outdoor')) return 'Router & AP Wireless';
        if (str_contains($lower, 'modem')) return 'Modem & ONT GPON';
        if (str_contains($lower, 'mikrotik') || str_contains($lower, 'ubnt') || str_contains($lower, 'switch') || str_contains($lower, 'ups')) return 'Perangkat Jaringan & Switch';
        if (str_contains($lower, 'htb')) return 'Media Converter (HTB)';
        if (str_contains($lower, 'adaptor') || str_contains($lower, 'poe')) return 'Power & Adaptor';
        if (str_contains($lower, 'smuft')) return 'Protection Sleeve (Smuft)';
        if (str_contains($lower, 'tist')) return 'Kabel Ties';
        if (str_contains($lower, 'solasi') || str_contains($lower, 'double foam') || str_contains($lower, 'raber') || str_contains($lower, 'preco') || str_contains($lower, 'pelindung') || str_contains($lower, 'roset') || str_contains($lower, 'rg45') || str_contains($lower, 'tray kaset') || str_contains($lower, 'tutup tiang')) return 'Material Pembantu & Instalasi';
        return 'Material Optik & Jaringan';
    }

    private function detectUnitHelper(string $name): string
    {
        $lower = strtolower($name);
        if (str_contains($lower, 'tiang')) return 'Batang';
        if (str_contains($lower, 'dc ') || str_contains($lower, 'jellytube') || str_contains($lower, 'fig8') || str_contains($lower, 'armore') || str_contains($lower, 'mini adss') || str_contains($lower, 'ku ')) return 'Meter';
        if (str_contains($lower, 'kabel lan')) return 'Meter';
        if (str_contains($lower, 'patchcore') || str_contains($lower, 'pigtail')) return 'Pcs';
        if (str_contains($lower, 'solasi') || str_contains($lower, 'raber') || str_contains($lower, 'double foam')) return 'Roll';
        if (str_contains($lower, 'router') || str_contains($lower, 'modem') || str_contains($lower, 'switch') || str_contains($lower, 'htb') || str_contains($lower, 'adaptor') || str_contains($lower, 'ap ') || str_contains($lower, 'ups') || str_contains($lower, 'ubnt') || str_contains($lower, 'mikrotik')) return 'Unit';
        return 'Pcs';
    }
}
