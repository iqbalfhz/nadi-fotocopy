<?php

use App\Models\Category;
use Flux\Flux;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Kelola Kategori')] class extends Component {
    public ?int $editingId = null;

    public string $name = '';

    public string $description = '';

    public int $sortOrder = 0;

    #[Computed]
    public function categories()
    {
        return Category::query()
            ->withCount('products')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function create(): void
    {
        $this->resetForm();
        $this->sortOrder = (int) Category::query()->max('sort_order') + 1;

        Flux::modal('form-kategori')->show();
    }

    public function edit(int $categoryId): void
    {
        $category = Category::query()->findOrFail($categoryId);

        $this->editingId = $category->id;
        $this->name = $category->name;
        $this->description = $category->description ?? '';
        $this->sortOrder = $category->sort_order;

        Flux::modal('form-kategori')->show();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'sortOrder' => ['integer', 'min:0'],
        ]);

        $payload = [
            'name' => $validated['name'],
            'slug' => $this->uniqueSlug($validated['name'], $this->editingId),
            'description' => $validated['description'] !== '' ? $validated['description'] : null,
            'sort_order' => $validated['sortOrder'],
        ];

        if ($this->editingId) {
            Category::query()->findOrFail($this->editingId)->update($payload);
        } else {
            Category::create($payload);
        }

        Flux::modal('form-kategori')->close();
        Flux::toast(variant: 'success', text: $this->editingId ? 'Kategori diperbarui.' : 'Kategori ditambahkan.');

        $this->resetForm();
        unset($this->categories);
    }

    public function delete(int $categoryId): void
    {
        $category = Category::query()->withCount('products')->findOrFail($categoryId);

        // Produk memakai restrictOnDelete — cegah lebih awal dengan pesan yang jelas.
        if ($category->products_count > 0) {
            Flux::toast(
                variant: 'danger',
                text: "Kategori \"{$category->name}\" masih punya {$category->products_count} produk.",
            );

            return;
        }

        $category->delete();

        Flux::toast(variant: 'success', text: 'Kategori dihapus.');
        unset($this->categories);
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 2;

        while (Category::query()->where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->description = '';
        $this->sortOrder = 0;
        $this->resetValidation();
    }
}; ?>

<div class="flex flex-col gap-4">
    <div class="flex items-center justify-between">
        <flux:heading size="lg">Kategori</flux:heading>
        <flux:button variant="primary" icon="plus" wire:click="create">Kategori baru</flux:button>
    </div>

    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-sm">
            <thead class="border-b border-zinc-200 dark:border-zinc-700">
                <tr class="text-zinc-500">
                    <th class="p-3 text-start font-medium">Urutan</th>
                    <th class="p-3 text-start font-medium">Nama</th>
                    <th class="p-3 text-start font-medium">Jumlah produk</th>
                    <th class="p-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->categories() as $category)
                    <tr wire:key="c-{{ $category->id }}" class="border-b border-zinc-100 last:border-0 dark:border-zinc-800">
                        <td class="p-3 tabular-nums text-zinc-500">{{ $category->sort_order }}</td>
                        <td class="p-3">
                            <div class="font-medium">{{ $category->name }}</div>
                            @if ($category->description)
                                <div class="text-xs text-zinc-500">{{ $category->description }}</div>
                            @endif
                        </td>
                        <td class="p-3 tabular-nums">{{ $category->products_count }}</td>
                        <td class="p-3">
                            <div class="flex justify-end gap-2">
                                <flux:button size="sm" variant="subtle" wire:click="edit({{ $category->id }})">Ubah</flux:button>
                                <flux:button size="sm" variant="danger" wire:click="delete({{ $category->id }})">Hapus</flux:button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="p-8 text-center text-zinc-500">Belum ada kategori.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <flux:modal name="form-kategori" class="md:w-96">
        <div class="space-y-4">
            <flux:heading size="lg">{{ $editingId ? 'Ubah kategori' : 'Kategori baru' }}</flux:heading>

            <flux:input wire:model="name" label="Nama" placeholder="Contoh: Layanan Fotocopy" />
            <flux:input wire:model="description" label="Keterangan (opsional)" />
            <flux:input type="number" min="0" wire:model="sortOrder" label="Urutan tampil" />

            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Batal</flux:button></flux:modal.close>
                <flux:button variant="primary" wire:click="save">Simpan</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
