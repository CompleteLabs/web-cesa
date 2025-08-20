# HMVC Implementation with Plugin Manager & Permission System

## Overview
Web-cesa telah diimplementasikan dengan arsitektur HMVC (Hierarchical Model-View-Controller) yang memungkinkan modularitas aplikasi melalui plugin system, mirip dengan implementasi di aureuserp. Sistem ini juga dilengkapi dengan permission system 3 tingkat: Individual, Group, dan Global.

## Struktur Direktori

```
web-cesa/
├── app/                    # Core application
├── plugins/               # HMVC Modules/Plugins
│   └── cesa/
│       ├── Support/       # Support module with PluginManager
│       │   └── PluginManager.php
│       ├── Security/      # Security module with permission system
│       │   ├── Enums/
│       │   │   └── PermissionType.php
│       │   ├── Models/
│       │   │   └── Scopes/
│       │   │       └── UserPermissionScope.php
│       │   └── Traits/
│       │       └── HasScopedPermissions.php
│       └── Contact/       # Example plugin module
│           ├── ContactPlugin.php
│           ├── Models/
│           ├── Filament/Resources/
│           └── Database/Migrations/
└── bootstrap/
    └── plugins.php        # Plugin registration file
```

## 1. Plugin Manager System

### PluginManager Class
Located at: `plugins/cesa/Support/PluginManager.php`

Plugin Manager berfungsi untuk:
- Auto-discovery dan loading plugins
- Register plugins ke Filament Panel
- Centralized plugin management

### Cara Kerja:
1. PluginManager membaca list plugins dari `bootstrap/plugins.php`
2. Setiap plugin di-instantiate dan di-register ke Filament Panel
3. Plugin dapat mendefinisikan resources, pages, widgets sendiri

### Registrasi Plugin:
Edit file `bootstrap/plugins.php`:
```php
return [
    Cesa\Contact\ContactPlugin::class,
    // Tambahkan plugin lain di sini
];
```

## 2. Membuat Plugin Baru

### Struktur Plugin:
```
plugins/cesa/NamaModule/
├── NamaModulePlugin.php          # Main plugin class
├── Models/                        # Eloquent models
├── Filament/
│   ├── Resources/                # Filament resources
│   ├── Pages/                    # Custom pages
│   └── Widgets/                  # Dashboard widgets
├── Database/
│   └── Migrations/               # Plugin migrations
├── Http/
│   ├── Controllers/              # Controllers (if needed)
│   └── Requests/                 # Form requests
└── Config/                       # Plugin configuration
```

### Contoh Plugin Class:
```php
<?php

namespace Cesa\NamaModule;

use Filament\Contracts\Plugin;
use Filament\Panel;

class NamaModulePlugin implements Plugin
{
    public function getId(): string
    {
        return 'nama-module';
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public function register(Panel $panel): void
    {
        $panel
            ->when($panel->getId() == 'admin', function (Panel $panel) {
                $panel
                    ->discoverResources(
                        in: $this->getPluginBasePath('/Filament/Resources'), 
                        for: 'Cesa\\NamaModule\\Filament\\Resources'
                    );
            });
    }

    public function boot(Panel $panel): void
    {
        // Boot logic here
    }

    protected function getPluginBasePath($path = null): string
    {
        $reflector = new \ReflectionClass(get_class($this));
        return dirname($reflector->getFileName()).($path ?? '');
    }
}
```

## 3. Permission System

### 3 Kategori Permission:

#### 1. **Individual Permission**
- User hanya bisa melihat dan mengelola data miliknya sendiri
- Ideal untuk: Sales person, customer service individual

#### 2. **Group Permission**
- User bisa melihat dan mengelola data dari anggota team yang sama
- Ideal untuk: Team leader, department head

#### 3. **Global Permission**
- User bisa melihat dan mengelola semua data
- Ideal untuk: Admin, manager, C-level

### Implementasi pada Model:

```php
use Cesa\Security\Models\Scopes\UserPermissionScope;
use Cesa\Security\Traits\HasScopedPermissions;

class Contact extends Model
{
    use HasScopedPermissions;

    protected static function booted(): void
    {
        // Auto apply permission scope
        static::addGlobalScope(new UserPermissionScope('user'));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

### Checking Permissions:

```php
// Dalam Filament Resource
Forms\Components\Select::make('user_id')
    ->visible(fn () => auth()->user()->hasGlobalPermission());

// Dalam table actions
Tables\Actions\EditAction::make()
    ->visible(fn (Model $record) => 
        auth()->user()->hasGlobalPermission() || 
        $record->user_id === auth()->id()
    );

// Dalam controller atau service
if ($user->hasGlobalPermission()) {
    // Global access logic
} elseif ($user->hasGroupPermission()) {
    // Group access logic  
} else {
    // Individual access logic
}
```

## 4. Teams Management

### Team Model:
Teams digunakan untuk grouping users dalam permission system.

```php
// Relasi User <-> Team (Many to Many)
$user->teams(); // Get user's teams
$team->users(); // Get team's users

// Check membership
$user->belongsToTeam($team);
$user->isTeamLeader($team);
```

### Database Structure:
- `teams` table: menyimpan data team
- `team_user` pivot table: relasi many-to-many dengan role field
- `users.resource_permission`: enum field untuk permission type

## 5. Migration & Setup

### Run Migrations:
```bash
# Run core migrations
php artisan migrate

# Plugin migrations akan auto-load jika ada
```

### Assign Permission to User:
```php
// Via tinker atau seeder
$user = User::find(1);
$user->resource_permission = 'global'; // atau 'group' atau 'individual'
$user->save();

// Assign to team
$user->teams()->attach($teamId, ['role' => 'member']);
```

## 6. Best Practices

### 1. **Plugin Naming Convention:**
- Namespace: `Cesa\NamaModule`
- Plugin class: `NamaModulePlugin`
- Gunakan PascalCase untuk nama module

### 2. **Model dengan Permission:**
- Selalu gunakan trait `HasScopedPermissions`
- Apply `UserPermissionScope` di method `booted()`
- Definisikan relasi owner (biasanya `user()`)

### 3. **Filament Resources:**
- Check permission di form fields visibility
- Check permission di table actions
- Gunakan `mutateFormDataBeforeCreate()` untuk auto-assign user_id

### 4. **Security:**
- Jangan bypass permission scope tanpa alasan yang jelas
- Log semua akses ke data sensitive
- Regular audit permission assignments

## 7. Troubleshooting

### Plugin tidak muncul di menu:
1. Pastikan plugin terdaftar di `bootstrap/plugins.php`
2. Run `composer dump-autoload`
3. Clear cache: `php artisan cache:clear`

### Permission scope tidak bekerja:
1. Pastikan model menggunakan trait `HasScopedPermissions`
2. Check apakah global scope sudah di-apply
3. Verify user memiliki field `resource_permission`

### Migration plugin tidak jalan:
1. Check path migration di plugin
2. Pastikan method `loadMigrationsFrom()` dipanggil di plugin boot()
3. Run `php artisan migrate:status` untuk check status

## 8. Contoh Implementasi Lengkap

Lihat plugin `Contact` sebagai contoh implementasi lengkap:
- Plugin class: `plugins/cesa/Contact/ContactPlugin.php`
- Model dengan permission: `plugins/cesa/Contact/Models/Contact.php`
- Filament Resource: `plugins/cesa/Contact/Filament/Resources/ContactResource.php`

## Summary

Dengan implementasi HMVC dan Permission System ini, web-cesa sekarang memiliki:

✅ **Modular Architecture** - Setiap fitur bisa dibuat sebagai plugin terpisah
✅ **Plugin Manager** - Centralized plugin loading dan management
✅ **3-Level Permission** - Individual, Group, dan Global access control
✅ **Team Management** - Support untuk organizational structure
✅ **Auto-scoped Queries** - Data automatically filtered based on user permission
✅ **Flexible & Scalable** - Easy to add new modules tanpa mengubah core

Sistem ini memungkinkan pengembangan yang lebih terstruktur, maintainable, dan secure untuk aplikasi enterprise-level.
