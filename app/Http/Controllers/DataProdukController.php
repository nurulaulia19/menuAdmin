<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use App\Models\DataProduk;
use App\Models\Kategori;
use Illuminate\Http\Request;

class DataProdukController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index() {
        $dataProduk = DataProduk::with('kategori')->orderBy('id_produk', 'DESC')->paginate(10);
        // $dataProduk = DataProduk::with('kategori')->get();
        return view('produk.index', compact('dataProduk'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $dataKategori = Kategori::all();
        $dataProduk = DataProduk::all();
        return view('produk.create', compact('dataKategori','dataProduk'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        
        $validator = Validator::make($request->all(), [
            'gambar_produk' => 'required|file|mimes:jpeg,jpg,png',
            // Tambahkan aturan validasi lainnya sesuai kebutuhan
        ]);
    
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
    
        if ($request->hasFile('gambar_produk')) {
            $file = $request->file('gambar_produk'); // phpcs:ignore ..DetectUploadFil.Found
            $extension = $file->getClientOriginalExtension();
            $fileName = Str::random(40) . '.' . $extension;
            // $fileName = $file->getClientOriginalName();
            $file->storeAs('public/photos', $fileName); 
        } else {
            $fileName = null;
        }

        $dataProduk = new DataProduk;
        $dataProduk->id_produk = $request->id_produk;
        $dataProduk->id_kategori = $request->id_kategori;
        $dataProduk->nama_produk = $request->nama_produk;
        $dataProduk->harga_produk = $request->harga_produk;
        $dataProduk->gambar_produk = $fileName;
        $dataProduk->diskon_produk = $request->diskon_produk;
        $dataProduk->save();
    
        return redirect()->route('produk.index')->with('success', 'Produk inserted successfully');

       
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id_produk)
    {
        $dataKategori = Kategori::all();
        $dataProduk = DataProduk::where('id_produk', $id_produk)->first();
        return view('produk.update', compact('dataKategori','dataProduk'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id_produk)
    {
        $validator = Validator::make($request->all(), [
            'gambar_produk' => 'file|mimes:jpeg,jpg,png' // phpcs:ignore ..DetectWeakValidation.Found,..DetectWeakValidation.Found
            // Tambahkan aturan validasi lainnya sesuai kebutuhan
        ]);
    
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
    
        $dataProduk = DataProduk::find($id_produk);
    
        if (!$dataProduk) {
            // Produk dengan 'id_produk' yang dimaksud tidak ditemukan
            // Lakukan tindakan error handling atau tampilkan pesan kesalahan
            return redirect()->back()->with('error', 'Produk tidak ditemukan.');
        }
    
        $dataProduk->id_kategori = $request->id_kategori;
        $dataProduk->nama_produk = $request->nama_produk;
        $dataProduk->harga_produk = $request->harga_produk;
        $dataProduk->diskon_produk = $request->diskon_produk;
    
        if ($request->hasFile('gambar_produk')) {
            $file = $request->file('gambar_produk');
            $extension = $file->getClientOriginalExtension();
            $fileName = Str::random(40) . '.' . $extension;
            $file->storeAs('public/photos', $fileName);
            $dataProduk->gambar_produk = $fileName;
        }
    
        $dataProduk->save();
    
        return redirect()->route('produk.index')->with('success', 'Produk berhasil diubah.');
    }
    

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id_produk)
    {
        $dataProduk = DataProduk::where('id_produk', $id_produk);
        $dataProduk->delete();
        return redirect()->route('produk.index')->with('success', 'Terdelet');
    }

    public function laporanProduk(Request $request) {
        $query = DataProduk::with('kategori', 'transaksiDetail.transaksi')
            ->orderBy('id_produk', 'DESC');
    
        // Ambil data tanggal dan status dari request
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $status = $request->input('ket_makanan');
    
        // Simpan data tanggal dan status ke dalam session jika diberikan dalam request
        if ($startDate && $endDate) {
            session(['start_date' => $startDate]);
            session(['end_date' => $endDate]);
        } else {
            // Hapus data tanggal dari session jika tidak diberikan dalam request
            session()->forget('start_date');
            session()->forget('end_date');
        }
    
        if ($status) {
            // Simpan data status ke dalam session jika diberikan dalam request
            session(['status' => $status]);
        } else {
            // Hapus data status dari session jika tidak diberikan dalam request
            session()->forget('status');
        }
    
        // Terapkan filter jika ada data tanggal dan status dalam session
        if (session()->has('start_date') && session()->has('end_date')) {
            $query->whereHas('transaksiDetail.transaksi', function ($query) {
                $query->whereBetween('tanggal_transaksi', [session('start_date'), session('end_date')]);
            });
        }
    
        if (session()->has('status')) {
            $query->whereHas('transaksiDetail.transaksi', function ($query) {
                $query->where('ket_makanan', session('status'));
            });
        }
    
        $dataProduk = $query->paginate(10);
    
        return view('laporan.laporanProduk', compact('dataProduk'));
    }
}