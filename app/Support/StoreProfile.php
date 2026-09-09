<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Profil toko yang dipakai seluruh halaman publik dan struk.
 *
 * Seluruh pengaturan dibaca sekali per request lalu disimpan di memori,
 * supaya halaman yang memanggilnya berkali-kali tidak menembak database
 * berulang kali.
 */
class StoreProfile
{
    /** @var array<string, string|null>|null */
    private static ?array $cache = null;

    /**
     * @return array<string, string|null>
     */
    private static function all(): array
    {
        return self::$cache ??= Setting::query()->pluck('value', 'key')->all();
    }

    /**
     * Kosongkan memo — dipakai di tes dan setelah pengaturan disimpan.
     */
    public static function flush(): void
    {
        self::$cache = null;
    }

    private static function value(string $key): ?string
    {
        $value = self::all()[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    public static function name(): string
    {
        return self::value('store_name') ?? (string) config('app.name');
    }

    public static function address(): ?string
    {
        return self::value('store_address');
    }

    public static function hours(): ?string
    {
        return self::value('store_hours');
    }

    public static function receiptFooter(): ?string
    {
        return self::value('receipt_footer');
    }

    /**
     * Nomor WhatsApp dalam format internasional (628xx).
     */
    public static function whatsappNumber(): ?string
    {
        return WhatsApp::normalize(self::value('store_whatsapp'));
    }

    public static function whatsappLink(string $message): ?string
    {
        return WhatsApp::link(self::value('store_whatsapp'), $message);
    }

    /**
     * Link pencarian alamat toko di Google Maps.
     */
    public static function mapsLink(): ?string
    {
        $address = self::address();

        if ($address === null) {
            return null;
        }

        return 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($address);
    }
}
