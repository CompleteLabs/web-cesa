<?php

namespace App\Filament\Resources\Activities;

use App\Filament\Resources\Activities\Pages\ListActivities;
use App\Filament\Resources\Activities\Pages\ViewActivity;
use App\Filament\Resources\Activities\Schemas\ActivityInfolist;
use App\Filament\Resources\Activities\Tables\ActivitiesTable;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Spatie\Activitylog\Models\Activity as SpatieActivity;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Illuminate\Support\Facades\Auth;

class ActivityResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = SpatieActivity::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Log Aktivitas';

    protected static ?string $modelLabel = 'Log Aktivitas';

    protected static ?string $pluralModelLabel = 'Log Aktivitas';

    protected static string|UnitEnum|null $navigationGroup = 'System Tools';

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'description';

    // Shield Permissions
    public static function getPermissionPrefixes(): array
    {
        return [
            'view',
            'view_any',
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereDate('created_at', today())->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        $todayCount = static::getModel()::whereDate('created_at', today())->count();

        return match(true) {
            $todayCount > 100 => 'danger',
            $todayCount > 50 => 'warning',
            $todayCount > 0 => 'success',
            default => 'gray'
        };
    }

    public static function infolist(Schema $schema): Schema
    {
        return ActivityInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ActivitiesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActivities::route('/'),
            'view' => ViewActivity::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function canView($record): bool
    {
        $user = Auth::user();
        return $user && $user->can('view_activities::activity');
    }

    public static function canViewAny(): bool
    {
        $user = Auth::user();
        return $user && $user->can('view_any_activities::activity');
    }

    // Global search support
    public static function getGlobalSearchEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['causer', 'subject']);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['description', 'causer.name', 'causer.email'];
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return [
            'Event' => $record->event,
            'User' => $record->causer?->name ?? 'System',
            'When' => $record->created_at->diffForHumans(),
        ];
    }
}
