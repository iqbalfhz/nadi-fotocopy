<?php

use App\Actions\Pos\VoidTransaction;
use App\Enums\TransactionStatus;
use App\Exceptions\PosException;
use App\Models\Transaction;
use App\Support\Rupiah;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Riwayat Transaksi')] class extends Component {
    use WithPagination;

    public string $date = '';

    public ?int $voidingId = null;

    public string $voidReason = '';

    public function mount(): void
    {
        $this->date = now()->toDateString();
    }

    public function updatedDate(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function transactions()
    {
        return Transaction::query()
            ->with('user')
            ->whereDate('created_at', $this->date)
            ->latest('id')
            ->paginate(15);
    }

    #[Computed]
    public function omzet(): int
    {
        return (int) Transaction::query()
            ->completed()
            ->whereDate('created_at', $this->date)
            ->sum('total');
    }

    public function confirmVoid(int $transactionId): void
    {
        $this->voidingId = $transactionId;
        $this->voidReason = '';

        Flux::modal('void-transaksi')->show();
    }

    public function void(VoidTransaction $voider): void
    {
        $transaction = Transaction::query()->findOrFail($this->voidingId);

        try {
            $voider->handle($transaction, Auth::user(), $this->voidReason);
        } catch (PosException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());

            return;
        }

        Flux::modal('void-transaksi')->close();
        Flux::toast(variant: 'success', text: 'Transaksi dibatalkan, stok dikembalikan.');

        $this->voidingId = null;
        $this->voidReason = '';
        unset($this->transactions);
    }

    public function rupiah(int $amount): string
    {
        return Rupiah::format($amount);
    }
}; ?>

<div class="flex flex-col gap-4">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <flux:input type="date" wire:model.live="date" label="Tanggal" class="w-52" />

        <div class="rounded-lg border border-zinc-200 px-4 py-2 dark:border-zinc-700">
            <div class="text-xs text-zinc-500">Omzet hari ini (tanpa yang dibatalkan)</div>
            <div class="text-lg font-semibold tabular-nums">{{ $this->rupiah($this->omzet()) }}</div>
        </div>
    </div>

    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-sm">
            <thead class="border-b border-zinc-200 text-start dark:border-zinc-700">
                <tr class="text-zinc-500">
                    <th class="p-3 text-start font-medium">No. Transaksi</th>
                    <th class="p-3 text-start font-medium">Waktu</th>
                    <th class="p-3 text-start font-medium">Bayar</th>
                    <th class="p-3 text-end font-medium">Total</th>
                    <th class="p-3 text-start font-medium">Status</th>
                    <th class="p-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->transactions() as $transaction)
                    <tr wire:key="trx-{{ $transaction->id }}" class="border-b border-zinc-100 last:border-0 dark:border-zinc-800">
                        <td class="p-3 font-medium">{{ $transaction->transaction_number }}</td>
                        <td class="p-3">{{ $transaction->created_at->format('H:i') }}</td>
                        <td class="p-3">{{ $transaction->payment_method->label() }}</td>
                        <td class="p-3 text-end tabular-nums">{{ $this->rupiah($transaction->total) }}</td>
                        <td class="p-3">
                            <flux:badge size="sm" :color="$transaction->isVoided() ? 'red' : 'green'">
                                {{ $transaction->status->label() }}
                            </flux:badge>
                        </td>
                        <td class="p-3">
                            <div class="flex justify-end gap-2">
                                <flux:button size="sm" variant="subtle" :href="route('pos.struk', $transaction)" target="_blank">
                                    Struk
                                </flux:button>
                                @if (! $transaction->isVoided() && auth()->user()->canManageStore())
                                    <flux:button size="sm" variant="danger" wire:click="confirmVoid({{ $transaction->id }})">
                                        Batalkan
                                    </flux:button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-8 text-center text-zinc-500">Belum ada transaksi pada tanggal ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $this->transactions()->links() }}

    <flux:modal name="void-transaksi" class="md:w-96">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">Batalkan transaksi</flux:heading>
                <flux:text class="mt-1">
                    Transaksi tetap tersimpan di riwayat dan stok akan dikembalikan.
                </flux:text>
            </div>

            <flux:input wire:model="voidReason" label="Alasan pembatalan" placeholder="Contoh: salah input" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Batal</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="void">Batalkan transaksi</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
