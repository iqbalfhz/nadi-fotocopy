<?php

use App\Models\Setting;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Pengaturan Toko')] class extends Component {
    public string $storeName = '';

    public string $storeAddress = '';

    public string $storeHours = '';

    public string $storeWhatsapp = '';

    public string $receiptFooter = '';

    public function mount(): void
    {
        $this->storeName = Setting::get('store_name', '') ?? '';
        $this->storeAddress = Setting::get('store_address', '') ?? '';
        $this->storeHours = Setting::get('store_hours', '') ?? '';
        $this->storeWhatsapp = Setting::get('store_whatsapp', '') ?? '';
        $this->receiptFooter = Setting::get('receipt_footer', '') ?? '';
    }

    public function save(): void
    {
        $validated = $this->validate([
            'storeName' => ['required', 'string', 'max:255'],
            'storeAddress' => ['nullable', 'string', 'max:500'],
            'storeHours' => ['nullable', 'string', 'max:255'],
            'storeWhatsapp' => ['nullable', 'string', 'max:30'],
            'receiptFooter' => ['nullable', 'string', 'max:255'],
        ]);

        Setting::put('store_name', $validated['storeName']);
        Setting::put('store_address', $validated['storeAddress']);
        Setting::put('store_hours', $validated['storeHours']);
        Setting::put('store_whatsapp', $validated['storeWhatsapp']);
        Setting::put('receipt_footer', $validated['receiptFooter']);

        Flux::toast(variant: 'success', text: 'Pengaturan toko disimpan.');
    }
}; ?>

<div class="flex max-w-lg flex-col gap-4">
    <div>
        <flux:heading size="lg">Pengaturan toko</flux:heading>
        <flux:text class="mt-1">Dipakai di struk dan halaman katalog publik.</flux:text>
    </div>

    <flux:input wire:model="storeName" label="Nama toko" />
    <flux:input wire:model="storeAddress" label="Alamat" />
    <flux:input wire:model="storeHours" label="Jam operasional" placeholder="Senin–Sabtu, 08.00–20.00 WIB" />
    <flux:input
        wire:model="storeWhatsapp"
        label="Nomor WhatsApp"
        placeholder="628123456789"
        description="Format internasional tanpa tanda +. Dipakai tombol WA di katalog."
    />
    <flux:input wire:model="receiptFooter" label="Catatan bawah struk" />

    <div>
        <flux:button variant="primary" wire:click="save">Simpan</flux:button>
    </div>
</div>
