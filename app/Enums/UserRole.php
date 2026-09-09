<?php

namespace App\Enums;

enum UserRole: string
{
    case Owner = 'owner';
    case Kasir = 'kasir';

    /**
     * Label tampilan untuk role ini.
     */
    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Kasir => 'Kasir',
        };
    }

    /**
     * Apakah role ini boleh membatalkan (void) transaksi dan melihat laporan.
     *
     * Lihat §3 dan §7.1 dokumentasi teknis.
     */
    public function canManageStore(): bool
    {
        return $this === self::Owner;
    }
}
