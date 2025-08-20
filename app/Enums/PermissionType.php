<?php

namespace App\Enums;

enum PermissionType: string
{
    case GROUP = 'group';

    case INDIVIDUAL = 'individual';

    case GLOBAL = 'global';

    public static function options(): array
    {
        return [
            self::GROUP->value      => 'Group',
            self::INDIVIDUAL->value => 'Individual',
            self::GLOBAL->value     => 'Global',
        ];
    }
    
    public function label(): string
    {
        return match($this) {
            self::GROUP => 'Group - Access to all company members data',
            self::INDIVIDUAL => 'Individual - Access to own data only',
            self::GLOBAL => 'Global - Access to all data',
        };
    }
}
