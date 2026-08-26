<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component {
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public function mount(): void
    {
        if (Auth::check()) {
            $this->redirectRoute('dashboard', navigate: true);
        }
    }

    public function login(): void
    {
        $credentials = $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::attempt($credentials, $this->remember)) {
            $this->addError('email', 'Email atau password yang kamu masukkan tidak sesuai.');
            session()->flash('toast', ['type' => 'error', 'message' => 'Login gagal. Periksa kembali email dan password kamu.']);

            return;
        }

        request()->session()->regenerate();
        session()->flash('toast', ['type' => 'success', 'message' => 'Login berhasil. Selamat datang kembali!']);
        $this->redirectRoute('dashboard', navigate: true);
    }
};
?>

<div
    class="relative flex min-h-screen items-center justify-center overflow-hidden bg-slate-50 px-4 py-8 sm:px-6 lg:px-8">
    <div class="absolute inset-0">
        <img src="https://picsum.photos/seed/transgo-login/1600/1000" alt=""
            class="h-full w-full object-cover opacity-15">
        <div class="absolute inset-0 bg-linear-to-br from-brand-50/95 via-white/90 to-orange-100/80"></div>
    </div>

    <div
        class="relative grid w-full max-w-5xl overflow-hidden rounded-3xl bg-white shadow-2xl shadow-orange-900/10 lg:grid-cols-2">
        <div class="relative hidden min-h-150 overflow-hidden bg-brand-600 p-10 lg:block">
            <img src="https://picsum.photos/seed/transgo-travel/900/1200" alt="Pemandangan perjalanan"
                class="absolute inset-0 h-full w-full object-cover opacity-45 mix-blend-multiply">
            <div class="relative flex h-full flex-col justify-between text-white">
                <a href="{{ route('home') }}" wire:navigate class="flex items-center gap-2">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/20 backdrop-blur">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 12h18M3 12l4-4M3 12l4 4M21 12l-4-4M21 12l-4 4" />
                        </svg>
                    </span>
                    <span class="text-xl font-extrabold">Trans<span class="text-orange-200">Go</span></span>
                </a>
                <div>
                    <p class="text-sm font-bold uppercase tracking-[0.2em] text-orange-100">Selamat datang</p>
                    <h1 class="mt-4 text-4xl font-extrabold leading-tight">Perjalanan nyaman dimulai dari sini.</h1>
                    <p class="mt-5 max-w-sm text-sm leading-6 text-white/80">Kelola pesanan tiket, pantau perjalanan,
                        dan nikmati layanan shuttle yang lebih praktis.</p>
                </div>
                <p class="text-xs text-white/60">Aman &middot; Nyaman &middot; Tepat waktu</p>
            </div>
        </div>

        <div class="p-6 sm:p-10 lg:p-14">
            <a href="{{ route('home') }}" wire:navigate class="flex items-center gap-2 lg:hidden">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-500 text-white">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 12h18M3 12l4-4M3 12l4 4M21 12l-4-4M21 12l-4 4" />
                    </svg>
                </span>
                <span class="text-lg font-extrabold text-slate-900">Trans<span class="text-brand-500">Go</span></span>
            </a>

            <div class="mt-10 lg:mt-0">
                <p class="text-sm font-bold uppercase tracking-wide text-brand-600">Akun kamu</p>
                <h2 class="mt-2 text-3xl font-extrabold text-slate-900">Masuk ke TransGo</h2>
                <p class="mt-3 text-sm text-slate-500">Gunakan akun yang sudah terdaftar untuk melanjutkan.</p>
            </div>

            @if (session('toast'))
                <div
                    class="mt-6 rounded-xl border px-4 py-3 text-sm font-medium {{ session('toast.type') === 'success' ? 'border-green-200 bg-green-50 text-green-700' : 'border-red-200 bg-red-50 text-red-700' }}">
                    {{ session('toast.message') }}
                </div>
            @endif

            <form wire:submit="login" class="mt-8 space-y-5">
                <div>
                    <label for="email" class="mb-2 block text-sm font-semibold text-slate-700">Email</label>
                    <div class="relative">
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round">
                            <rect width="20" height="16" x="2" y="4" rx="2" />
                            <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" />
                        </svg>
                        <input id="email" type="email" wire:model="email" autocomplete="email"
                            placeholder="nama@email.com"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 py-3 pl-11 pr-4 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-brand-500 focus:bg-white focus:ring-4 focus:ring-brand-100">
                    </div>
                    @error('email')
                        <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="mb-2 block text-sm font-semibold text-slate-700">Password</label>
                    <div class="relative">
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round">
                            <rect width="18" height="11" x="3" y="11" rx="2" />
                            <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                        </svg>
                        <input id="password" type="password" wire:model="password" autocomplete="current-password"
                            placeholder="Masukkan password"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 py-3 pl-11 pr-4 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-brand-500 focus:bg-white focus:ring-4 focus:ring-brand-100">
                    </div>
                    @error('password')
                        <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-between gap-4 text-sm">
                    <label class="flex items-center gap-2 text-slate-600">
                        <input type="checkbox" wire:model="remember"
                            class="h-4 w-4 rounded border-slate-300 text-brand-500 focus:ring-brand-500">
                        Ingat saya
                    </label>
                    <a href="#" class="font-semibold text-brand-600 hover:text-brand-700">Lupa password?</a>
                </div>

                <button type="submit" wire:loading.attr="disabled"
                    class="flex w-full items-center justify-center gap-2 rounded-xl bg-brand-500 py-3.5 text-sm font-bold text-white shadow-lg shadow-brand-500/25 transition hover:bg-brand-600 disabled:cursor-not-allowed disabled:opacity-70">
                    <span wire:loading.remove wire:target="login">Masuk</span>
                    <span wire:loading wire:target="login" class="flex items-center gap-2"><svg
                            class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4" />
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 0 1 8-8V0C5.4 0 0 5.4 0 12h4Z" />
                        </svg>Memproses...</span>
                </button>
            </form>

            <p class="mt-8 text-center text-xs text-slate-400">Dengan masuk, kamu menyetujui ketentuan layanan TransGo.
            </p>
        </div>
    </div>
</div>
