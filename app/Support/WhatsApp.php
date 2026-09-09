<?php

namespace App\Support;

/**
 * Helper link wa.me (§4.2).
 *
 * Bukan pengiriman otomatis — link ini dibuka manual oleh kasir/pelanggan.
 */
class WhatsApp
{
    /**
     * Ubah nomor lokal (08xx) menjadi format internasional (628xx).
     */
    public static function normalize(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $phone) ?? '';

        if ($digits === '') {
            return null;
        }

        return str_starts_with($digits, '0') ? '62'.substr($digits, 1) : $digits;
    }

    /**
     * Bangun link wa.me lengkap dengan pesan yang sudah terisi.
     */
    public static function link(?string $phone, string $message): ?string
    {
        $normalized = self::normalize($phone);

        if ($normalized === null) {
            return null;
        }

        return 'https://wa.me/'.$normalized.'?text='.rawurlencode($message);
    }
}
