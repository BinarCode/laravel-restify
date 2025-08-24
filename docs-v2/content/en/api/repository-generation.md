---
title: Repository Generation 
menuTitle: Repository Generation 
category: API 
position: 11
---

# Repository Generation

Laravel Restify provides powerful repository generation commands for both individual and bulk repository creation, with intelligent path detection and automatic relationship generation.

## Intelligent Path Detection

The repository generator now automatically detects your project's repository organization pattern and creates new repositories in the appropriate location.

### Supported Patterns

1. **Grouped by Model** - `App/Restify/Users/UserRepository.php`
2. **Domain Driven** - `App/Restify/Domains/User/UserRepository.php`
3. **Module Based** - `App/Restify/Admin/UserRepository.php`
4. **Flat Structure** - `App/Restify/UserRepository.php` (default)

### How It Works

When you run:
```bash
php artisan restify:repository PostRepository
```

The command will:
1. First check the `app/Restify` directory for existing repositories
2. If none found in `app/Restify`, scan the entire `app/` directory
3. Analyze the location patterns of found repositories
4. Apply the same pattern to the new repository
5. Display the detected pattern and target location

This prioritization ensures that repositories in the standard `App/Restify` location are preferred over other locations.

### Example Output

```bash
$ php artisan restify:repository PostRepository
Detected repository pattern: grouped-by-model
Repository will be created in: App\Restify
Repository created successfully.
```

If your project has `UserRepository` in `App/Restify/Users/`, the new `PostRepository` will be created in `App/Restify/Posts/`.

## Automatic Relationship Detection

When you run the repository generation command:

```bash
php artisan restify:repository PostRepository
```

The command will:
1. Analyze your database schema for foreign key columns
2. Generate regular fields in the `fields()` method
3. Generate BelongsTo and HasMany relationships in a separate `static include()` method

## Generated Structure

For a `posts` table with `user_id` and `category_id` columns, and a `comments` table with `post_id`, the generated repository will look like:

```php
<?php

namespace App\Restify;

use App\Models\Post;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Fields\BelongsTo;
use Binaryk\LaravelRestify\Fields\HasMany;

class PostRepository extends Repository
{
    public static string $model = Post::class;

    public static function include(): array
    {
        return [
            BelongsTo::make('user', UserRepository::class),
            BelongsTo::make('category', CategoryRepository::class),
            HasMany::make('comments', CommentRepository::class),
        ];
    }

    public function fields(RestifyRequest $request): array
    {
        return [
            id(),
            field('title')->required(),
            field('content')->textarea()->required(),
            field('created_at')->datetime()->readonly(),
            field('updated_at')->datetime()->readonly(),
        ];
    }
}
```

## How It Works

### BelongsTo Detection
- Columns ending with `_id` (except `id` itself) are detected as BelongsTo relationships
- The relationship name is derived from the column name (e.g., `user_id` → `user`)
- The command attempts to find the related repository class automatically

### HasMany Detection
- The command scans other tables for foreign keys pointing to the current model
- For example, if `comments` table has `post_id`, it generates `HasMany::make('comments')`
- Repository classes are automatically resolved when possible

### Repository Resolution
The command searches for repository classes in these locations:
- `App\Restify\{Model}Repository`
- `App\Http\Restify\{Model}Repository`

If a repository isn't found, the relationship is still generated without the repository parameter, allowing Laravel Restify to auto-resolve it.

## Benefits

1. **Separation of Concerns**: Fields and relationships are kept in separate methods
2. **Clean Code**: Foreign key fields are not duplicated in the fields array
3. **Automatic Detection**: Reduces manual work when setting up repositories
4. **Follows Best Practices**: Uses the `static include()` method as recommended in Laravel Restify documentation

## Customization

You can always modify the generated relationships after creation:

```php
public static function include(): array
{
    return [
        BelongsTo::make('user', UserRepository::class)->searchable('name'),
        BelongsTo::make('category')->nullable(),
        HasMany::make('comments')->sortable('created_at'),
        
        // Add more relationships manually
        MorphMany::make('tags'),
        BelongsToMany::make('subscribers')->withPivot('subscribed_at'),
    ];
}
```

## Override Confirmation

If a repository already exists at the target location, the command will ask for confirmation before overriding:

```bash
$ php artisan restify:repository UserRepository
Detected repository pattern: flat
Repository will be created in: App\Restify
Repository already exists at: /path/to/app/Restify/UserRepository.php
Do you want to override it? (yes/no) [no]:
```

You can skip this confirmation by using the `--force` option:

```bash
php artisan restify:repository UserRepository --force
```

## Disabling Automatic Generation

If you prefer to handle relationships manually, use the `--no-fields` option:

```bash
php artisan restify:repository PostRepository --no-fields
```

This will generate a repository with only the `id()` field and no relationships.

## Bulk Repository Generation

For new projects or when you need to generate repositories for multiple models at once, Laravel Restify provides a bulk generation command that can analyze all your models and create repositories automatically.

### Basic Usage

```bash
php artisan restify:generate:repositories
```

This command will:

1. **Discover all models** in your application automatically
2. **Analyze database schema** to generate appropriate field definitions
3. **Show a detailed preview** of what will be generated
4. **Ask for confirmation** before creating any files
5. **Generate repositories** with proper field mappings

### Interactive Preview

Before generating any files, the command shows a comprehensive preview:

```
📋 Preview of repositories to be generated:
═══════════════════════════════════════════════════════

🔍 Found 3 models:
   • User (table: users, 8 fields)
   • Post (table: posts, 6 fields)
   • Comment (table: comments, 4 fields)

📂 Repository configuration:
   Structure: flat
   Base namespace: App\Restify
   Force overwrite: No

📄 Repositories that will be generated:
   1. app/Restify/UserRepository.php
   2. app/Restify/PostRepository.php
   3. app/Restify/CommentRepository.php

📝 Sample repository preview:
   ┌─────────────────────────────────────────────────────┐
   │ class UserRepository extends Repository             │
   │ {                                                   │
   │     public static string $model = User::class;     │
   │                                                     │
   │     public function fields(RestifyRequest $request) │
   │     {                                               │
   │         return [                                    │
   │             id(),                                   │
   │             field('name'),                          │
   │             field('email')->email(),                │
   │             field('created_at')->datetime()->readonly(), │
   │             # ... 5 more fields                     │
   │         ];                                          │
   │     }                                               │
   │ }                                                   │
   └─────────────────────────────────────────────────────┘
```

### Command Options

| Option | Description | Example |
|--------|-------------|---------|
| `--force` | Overwrite existing repositories | `--force` |
| `--skip-preview` | Skip preview and generate immediately | `--skip-preview` |
| `--structure=flat\|domains` | Choose repository structure | `--structure=domains` |
| `--only=Model1,Model2` | Only generate for specific models | `--only=User,Post` |
| `--except=Model1,Model2` | Exclude specific models | `--except=User,Post` |

### Repository Structures

The command supports two organizational patterns:

#### Flat Structure (Default)

```
app/Restify/
├── UserRepository.php
├── PostRepository.php
└── CommentRepository.php
```

#### Domains Structure

```
app/Restify/Domains/
├── User/
│   └── UserRepository.php
├── Post/
│   └── PostRepository.php
└── Comment/
    └── CommentRepository.php
```

Choose the structure interactively or specify with `--structure`:

```bash
# Interactive structure selection
php artisan restify:generate:repositories

# Force domains structure
php artisan restify:generate:repositories --structure=domains
```

### Field Type Detection

The command automatically maps database columns to appropriate Restify fields:

| Database Type | Restify Field | Example |
|---------------|---------------|---------|
| `string`, `varchar` | `field()` | `field('name')` |
| Email columns | `field()->email()` | `field('email')->email()` |
| Password columns | `field()->password()->storable()` | `field('password')->password()->storable()` |
| `text`, `longtext` | `field()->textarea()` | `field('description')->textarea()` |
| `integer`, `bigint` | `field()->number()` | `field('count')->number()` |
| `boolean`, `tinyint` | `field()->boolean()` | `field('is_active')->boolean()` |
| `date` | `field()->date()` | `field('birth_date')->date()` |
| `datetime`, `timestamp` | `field()->datetime()` | `field('created_at')->datetime()` |
| `decimal`, `float` | `field()->number()` | `field('price')->number()` |
| `json` | `field()->json()` | `field('metadata')->json()` |

### Special Field Handling

- **Timestamps**: `created_at`, `updated_at`, `deleted_at` → automatically marked as `readonly()`
- **Foreign Keys**: Columns ending with `_id` are excluded (handled as relationships)
- **Email Fields**: Columns containing "email" → mapped to `email()` field
- **Password Fields**: Columns containing "password" → mapped to `password()->storable()`

### Filtering Models

#### Generate Only Specific Models

```bash
php artisan restify:generate:repositories --only=User,Post
```

#### Exclude Specific Models

```bash
php artisan restify:generate:repositories --except=User,Post
```

This is useful when you want to exclude certain models like:
- Third-party package models (Spatie permissions, etc.)
- Internal system models
- Models that don't need API endpoints

### Examples

#### Quick Setup for New Project

```bash
# Generate all repositories with preview
php artisan restify:generate:repositories
```

#### Production Setup with Domains Structure

```bash
# Generate with domains structure, skip preview
php artisan restify:generate:repositories \
  --structure=domains \
  --skip-preview \
  --force
```

#### Selective Generation

```bash
# Only generate for core models
php artisan restify:generate:repositories \
  --only=User,Post,Comment,Category \
  --structure=flat
```

#### Excluding System Models

```bash
# Generate all except system models
php artisan restify:generate:repositories \
  --except=PersonalAccessToken,PasswordReset,Permission,Role
```

### Generated Repository Structure

Each generated repository includes:

```php
<?php

namespace App\Restify;

use App\Models\User;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;

class UserRepository extends Repository
{
    public static string $model = User::class;

    public function fields(RestifyRequest $request): array
    {
        return [
            id(),
            field('name'),
            field('email')->email(),
            field('email_verified_at')->datetime()->readonly(),
            field('created_at')->datetime()->readonly(),
            field('updated_at')->datetime()->readonly(),
        ];
    }
}
```

### Integration with Individual Generation

The bulk generation command works seamlessly with the individual repository command. You can:

1. Use bulk generation to create the initial repositories
2. Use individual generation to add new repositories as needed
3. Both commands respect the same path detection and organizational patterns

### Best Practices

1. **Review Generated Files**: Always review generated repositories before committing
2. **Add Relationships**: The bulk generator focuses on fields; add relationships manually
3. **Configure Authorization**: Set up policies for the generated repositories
4. **Test Endpoints**: Verify that all generated endpoints work as expected

### Troubleshooting

#### No Models Found

If the command reports "No models found", ensure:
- Models are in the `app/Models` directory (or `app/` for older Laravel versions)
- Models extend `Illuminate\Database\Eloquent\Model`
- Models are not in excluded paths (`Http`, `Console`, `Exceptions`, `Providers`, `Restify`)

#### Field Detection Issues

If fields are missing or incorrect:
- Ensure database tables exist and are migrated
- Check that model `$table` property is set correctly
- Verify database connection is working

#### Permission Errors

If you encounter permission errors during generation:
- Ensure the `app/Restify` directory is writable
- Check file permissions in your Laravel application