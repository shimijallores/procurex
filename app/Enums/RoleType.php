<?php

declare(strict_types=1);

namespace App\Enums;

enum RoleType: string
{
    case SUPERADMIN = 'Superadmin';
    case BAC_RESO_ADMIN = 'BAC Reso Admin';
    case CANVASSING_ADMIN = 'Canvassing Admin';
    case DOCUMENT_ADMIN = 'Document Admin';
    case PR_ADMIN = 'PR Admin';
    case QUOTATION_ADMIN = 'Quotation Admin';

    public static function isSystemRole(string $roleName): bool
    {
        return in_array($roleName, array_column(self::cases(), 'value'), true);
    }

    /**
     * @return array<string>
     */
    public static function systemRoles(): array
    {
        return array_column(self::cases(), 'value');
    }
}
