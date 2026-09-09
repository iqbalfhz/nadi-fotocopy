<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Transaction;
use App\Support\Rupiah;
use Illuminate\Contracts\View\View;

class ReceiptController extends Controller
{
    public function show(Transaction $transaction): View
    {
        $transaction->load(['items', 'user']);

        $storeName = Setting::get('store_name', config('app.name'));
        $storeWhatsapp = Setting::get('store_whatsapp');

        return view('pos.struk', [
            'transaction' => $transaction,
            'storeName' => $storeName,
            'storeAddress' => Setting::get('store_address'),
            'storeWhatsapp' => $storeWhatsapp,
            'receiptFooter' => Setting::get('receipt_footer', 'Terima kasih!'),
            'waLink' => $this->whatsappLink($transaction, $storeName),
        ]);
    }

    /**
     * Link wa.me berisi ringkasan struk sebagai teks (§4.2).
     *
     * Bukan pengiriman otomatis — kasir yang menekan kirim di WhatsApp.
     */
    private function whatsappLink(Transaction $transaction, ?string $storeName): ?string
    {
        if (! $transaction->customer_phone) {
            return null;
        }

        $lines = [
            "*{$storeName}*",
            "Struk {$transaction->transaction_number}",
            $transaction->created_at->format('d/m/Y H:i'),
            '',
        ];

        foreach ($transaction->items as $item) {
            $lines[] = "{$item->product_name} {$item->qty} x ".Rupiah::plain($item->price).' = '.Rupiah::plain($item->subtotal);
        }

        $lines[] = '';
        $lines[] = 'Subtotal: '.Rupiah::format($transaction->subtotal);

        if ($transaction->discount_amount > 0) {
            $lines[] = 'Diskon: -'.Rupiah::format($transaction->discount_amount);
        }

        $lines[] = 'Total: '.Rupiah::format($transaction->total);
        $lines[] = 'Bayar ('.$transaction->payment_method->label().'): '.Rupiah::format($transaction->paid_amount);

        if ($transaction->change_amount > 0) {
            $lines[] = 'Kembali: '.Rupiah::format($transaction->change_amount);
        }

        $lines[] = '';
        $lines[] = 'Terima kasih!';

        return 'https://wa.me/'.$this->normalizePhone($transaction->customer_phone)
            .'?text='.rawurlencode(implode("\n", $lines));
    }

    /**
     * Ubah 08xx menjadi format internasional 628xx.
     */
    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';

        if (str_starts_with($digits, '0')) {
            return '62'.substr($digits, 1);
        }

        return $digits;
    }
}
