<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Helpers\ImageHelper;

class CustomerController extends Controller
{
    // Redirect ke Google
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    // Callback dari Google
    public function callback()
    {
        try {
            $socialUser = Socialite::driver('google')->user();

            // Cek apakah email sudah terdaftar
            $registeredUser = User::where('email', $socialUser->email)->first();

            if (!$registeredUser) {
                // Buat user baru
                $user = User::create([
                    'nama' => $socialUser->name,
                    'email' => $socialUser->email,
                    'role' => '2', // Role customer
                    'status' => 1, // Status aktif
                    'password' => Hash::make(uniqid()), // Password default
                    'hp' => '-', // Default value
                ]);

                // Buat data customer
                Customer::create([
                    'user_id' => $user->id,
                    'google_id' => $socialUser->id,
                    'google_token' => $socialUser->token
                ]);

                // Login pengguna baru
                Auth::login($user);
            } else {
                // Jika email sudah terdaftar, langsung login
                Auth::login($registeredUser);
            }

            // Redirect ke halaman utama
            return redirect()->intended('beranda');
        } catch (\Exception $e) {
            Log::error('Google login error: ' . $e->getMessage());
            return redirect('/')->with('error', 'Terjadi kesalahan saat login dengan Google: ' . $e->getMessage());
        }
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/')->with('success', 'Anda telah berhasil logout.');
    }

    public function index()
    {
        $customer = Customer::orderBy('id', 'desc')->get();
        return view('backend.v_customer.index', [
            'judul' => 'Customer',
            'sub' => 'Halaman Customer',
            'index' => $customer
        ]);
    }

    public function show($id)
    {
        $customer = Customer::findOrFail($id);
        return view('backend.v_customer.show', [
            'judul' => 'Detail Customer',
            'sub' => 'Halaman Detail Customer',
            'customer' => $customer
        ]);
    }

    public function edit($id)
    {
        $customer = Customer::findOrFail($id);
        return view('backend.v_customer.edit', [
            'judul' => 'Ubah Customer',
            'sub' => 'Halaman Ubah Customer',
            'customer' => $customer
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nama' => 'required',
            'email' => 'required|email',
            'alamat' => 'nullable',
            'pos' => 'nullable'
        ]);

        $customer = Customer::findOrFail($id);
        $user = $customer->user;

        $user->update([
            'nama' => $request->nama,
            'email' => $request->email
        ]);

        $customer->update([
            'alamat' => $request->alamat,
            'pos' => $request->pos
        ]);

        return redirect()->route('backend.customer.index')->with('success', 'Data customer berhasil diubah');
    }

    public function destroy($id)
    {
        $customer = Customer::findOrFail($id);
        $customer->delete();

        return redirect()->route('backend.customer.index')->with('success', 'Data customer berhasil dihapus');
    }
    
    public function akun($id)
    {
        $loggedInCustomerId = Auth::user()->id;
        // Cek apakah ID yang diberikan sama dengan ID customer yang sedang login
        if ($id != $loggedInCustomerId) {
            // Redirect atau tampilkan pesan error
            return redirect()->route('customer.akun', ['id' => $loggedInCustomerId])->with('msgError', 'Anda tidak berhak mengakses akun ini.');
        }
        $customer = Customer::where('user_id', $id)->firstOrFail();
        return view('v_customer.edit', [
            'judul' => 'Customer',
            'subJudul' => 'Akun Customer', 
            'edit' => $customer
        ]);
    }

    public function updateAkun(Request $request, $id)
    {
        $customer = Customer::where('user_id', $id)->firstOrFail();
        
        // Atur rules validasi dasar
        $rules = [
            'nama' => 'required|max:255',
            'email' => 'required|email|max:255',
            'hp' => 'required|min:10|max:13',
            'alamat' => 'required',
            'pos' => 'required',
            'foto' => 'nullable|image|mimes:jpeg,jpg,png,gif|file|max:1024',
        ];

        // Tambahkan validasi unique untuk email jika email berubah
        if ($request->email != $customer->user->email) {
            $rules['email'] = 'required|email|max:255|unique:users,email,' . $customer->user->id;
        }

        $messages = [
            'nama.required' => 'Nama harus diisi',
            'email.required' => 'Email harus diisi',
            'email.email' => 'Format email tidak valid',
            'hp.required' => 'Nomor HP harus diisi',
            'hp.min' => 'Nomor HP minimal 10 digit',
            'hp.max' => 'Nomor HP maksimal 13 digit',
            'alamat.required' => 'Alamat harus diisi',
            'pos.required' => 'Kode pos harus diisi',
            'foto.image' => 'Format gambar harus jpeg, jpg, png, atau gif',
            'foto.max' => 'Ukuran file gambar maksimal 1024 KB'
        ];

        $validatedData = $request->validate($rules, $messages);

        // Handling foto
        if ($request->file('foto')) {
            // Hapus foto lama jika ada
            if ($customer->user->foto) {
                $oldImagePath = public_path('storage/img-customer/') . $customer->user->foto;
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }

            $file = $request->file('foto');
            $extension = $file->getClientOriginalExtension();
            $originalFileName = date('YmdHis') . '_' . uniqid() . '.' . $extension;
            $directory = 'storage/img-customer/';

            // Simpan gambar dengan ukuran yang ditentukan
            ImageHelper::uploadAndResize($file, $directory, $originalFileName, 385, 400);

            // Update foto di user
            $customer->user->update([
                'foto' => $originalFileName
            ]);
        }

        // Update data user
        $customer->user->update([
            'nama' => $validatedData['nama'],
            'email' => $validatedData['email'],
            'hp' => $validatedData['hp']
        ]);

        // Update data customer
        $customer->update([
            'alamat' => $validatedData['alamat'],
            'pos' => $validatedData['pos']
        ]);

        return redirect()->route('customer.akun', $id)->with('success', 'Data berhasil diperbarui');
    }
}
