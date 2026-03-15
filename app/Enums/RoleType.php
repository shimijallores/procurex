<?php

declare(strict_types=1);

namespace App\Enums;

enum RoleType: string
{
    case SUPERADMIN = 'SuperAdmin';
    case CHECKING_ADMIN = 'Checking Admin';
    case CANVASSING_ADMIN = 'Canvassing Admin';
    case PR_ADMIN = 'PR Admin';
    case RFQ_ADMIN = 'RFQ Admin';
    case ABSTRACT_ADMIN = 'Abstract Admin';
    case RESOLUTION_ADMIN = 'Resolution Admin';
    case NOA_ADMIN = 'NOA Admin';
    case PO_ADMIN = 'PO Admin';
    case INSPECTION_ADMIN = 'Inspection Admin';

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

    /**
     * @return array<string>
     */
    public static function officeSubmissionRoles(): array
    {
        return [
            self::SUPERADMIN->value,
            self::CHECKING_ADMIN->value,
            'office',
        ];
    }
}
