<?php

use App\Actions\Pos\BuildSalesReport;
use App\Enums\PaymentMethod;
use App\Support\Rupiah;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Laporan Penjualan')] class extends Component {
    public string $period = 'harian';

    public string $from = '';

    public string $to = '';

    public function mount(): void
    {
        $this->applyPeriod();
    }

    public function updatedPeriod(): void
    {
        $this->applyPeriod();
    }

    /**
     * Rentang mengikuti timezone aplikasi (Asia/Jakarta), bukan UTC (§7.1).
     */
    private function applyPeriod(): void
    {
        $today = Carbon::today();

        [$from, $to] = match ($this->period) {
            'mingguan' => [$today->copy()->startOfWeek(), $today->copy()->endOfWeek()],
            'bulanan' => [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()],
            default => [$today->copy(), $today->copy()],
        };

        $this->from = $from->toDateString();
        $this->to = $to->toDateString();
    }

    private function fromDate(): Carbon
    {
        return Carbon::parse($this->from);
    }

    private function toDate(): Carbon
    {
        return Carbon::parse($this->to);
    }

    #[Computed]
    public function summary(): array
    {
        return app(BuildSalesReport::class)->summary($this->fromDate(), $this->toDate());
    }

    #[Computed]
    public function byPaymentMethod()
    {
        return app(BuildSalesReport::class)->byPaymentMethod($this->fromDate(), $this->toDate());
    }

    #[Computed]
    public function topProducts()
    {
        return app(BuildSalesReport::class)->topProducts($this->fromDate(), $this->toDate());
    }

    #[Computed]
    public function daily()
    {
        return app(BuildSalesReport::class)->daily($this->fromDate(), $this->toDate());
    }

    public function rupiah(int $amount): string
    {
        return Rupiah::format($amount);
    }

    /**
     * Query byPaymentMethod mengembalikan model Transaction, jadi kolomnya
     * sudah ter-cast jadi enum. String tetap diterima untuk jaga-jaga.
     */
    public function methodLabel(PaymentMethod|string $value): string
    {
        return $value instanceof PaymentMethod
            ? $value->label()
            : PaymentMethod::from($value)->label();
    }
}; ?>

<div class="flex flex-col gap-5">
    <div class="flex flex-wrap items-end gap-3">
        <flux:radio.group wire:model.live="period" variant="segmented">
            <flux:radio value="harian">Harian</flux:radio>
            <flux:radio value="mingguan">Mingguan</flux:radio>
            <flux:radio value="bulanan">Bulanan</flux:radio>
        </flux:radio.group>

        <flux:input type="date" wire:model.live="from" label="Dari" class="w-44" />
        <flux:input type="date" wire:model.live="to" label="Sampai" class="w-44" />
    </div>

    {{-- Ringkasan --}}
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
            <div class="text-xs text-zinc-500">Omzet</div>
            <div class="mt-1 text-2xl font-semibold tabular-nums">{{ $this->rupiah($this->summary()['total']) }}</div>
        </div>
        <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
            <div class="text-xs text-zinc-500">Jumlah transaksi</div>
            <div class="mt-1 text-2xl font-semibold tabular-nums">{{ $this->summary()['transaction_count'] }}</div>
        </div>
        <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
            <div class="text-xs text-zinc-500">Rata-rata / transaksi</div>
            <div class="mt-1 text-2xl font-semibold tabular-nums">{{ $this->rupiah($this->summary()['average']) }}</div>
        </div>
        <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
            <div class="text-xs text-zinc-500">Item terjual</div>
            <div class="mt-1 text-2xl font-semibold tabular-nums">{{ $this->summary()['item_count'] }}</div>
        </div>
    </div>

    @if ($this->summary()['voided_count'] > 0 || $this->summary()['discount_total'] > 0)
        <div class="flex flex-wrap gap-6 rounded-xl border border-zinc-200 px-4 py-3 text-sm dark:border-zinc-700">
            <div>
                <span class="text-zinc-500">Total diskon diberikan:</span>
                <span class="font-medium tabular-nums">{{ $this->rupiah($this->summary()['discount_total']) }}</span>
            </div>
            <div>
                <span class="text-zinc-500">Transaksi dibatalkan (tidak dihitung):</span>
                <span class="font-medium tabular-nums">{{ $this->summary()['voided_count'] }}</span>
            </div>
        </div>
    @endif

    <div class="grid gap-4 lg:grid-cols-2">
        {{-- Per metode pembayaran --}}
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700">
            <div class="border-b border-zinc-200 p-3 dark:border-zinc-700">
                <flux:heading size="lg">Per metode pembayaran</flux:heading>
            </div>
            <table class="w-full text-sm">
                <tbody>
                    @forelse ($this->byPaymentMethod() as $row)
                        <tr wire:key="pm-{{ $loop->index }}" class="border-b border-zinc-100 last:border-0 dark:border-zinc-800">
                            <td class="p-3">{{ $this->methodLabel($row->payment_method) }}</td>
                            <td class="p-3 text-zinc-500">{{ $row->jumlah }} transaksi</td>
                            <td class="p-3 text-end tabular-nums">{{ $this->rupiah((int) $row->total) }}</td>
                        </tr>
                    @empty
                        <tr><td class="p-6 text-center text-zinc-500">Belum ada penjualan pada periode ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Produk terlaris --}}
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700">
            <div class="border-b border-zinc-200 p-3 dark:border-zinc-700">
                <flux:heading size="lg">Produk terlaris</flux:heading>
            </div>
            <table class="w-full text-sm">
                <tbody>
                    @forelse ($this->topProducts() as $row)
                        <tr wire:key="tp-{{ $loop->index }}" class="border-b border-zinc-100 last:border-0 dark:border-zinc-800">
                            <td class="p-3">{{ $row->product_name }}</td>
                            <td class="p-3 text-zinc-500 tabular-nums">{{ $row->qty }} terjual</td>
                            <td class="p-3 text-end tabular-nums">{{ $this->rupiah((int) $row->total) }}</td>
                        </tr>
                    @empty
                        <tr><td class="p-6 text-center text-zinc-500">Belum ada produk terjual.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Rincian harian --}}
    @if ($this->daily()->count() > 1)
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700">
            <div class="border-b border-zinc-200 p-3 dark:border-zinc-700">
                <flux:heading size="lg">Rincian per tanggal</flux:heading>
            </div>
            <table class="w-full text-sm">
                <tbody>
                    @foreach ($this->daily() as $row)
                        <tr wire:key="d-{{ $row->tanggal }}" class="border-b border-zinc-100 last:border-0 dark:border-zinc-800">
                            <td class="p-3">{{ \Illuminate\Support\Carbon::parse($row->tanggal)->translatedFormat('d M Y') }}</td>
                            <td class="p-3 text-zinc-500">{{ $row->jumlah }} transaksi</td>
                            <td class="p-3 text-end tabular-nums">{{ $this->rupiah((int) $row->total) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
