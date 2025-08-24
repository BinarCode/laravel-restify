---
title: Validation Methods 
menuTitle: Validation Methods 
category: API 
position: 9
---

# Fluent Validation Methods

Laravel Restify provides a fluent API for adding validation rules to fields, similar to Laravel Nova. This makes it easy to chain validation methods for cleaner and more readable code.

## Basic Usage

Instead of using the traditional `rules()` method with string rules, you can now use fluent validation methods:

```php
// Traditional approach
field('email')->rules('required', 'email', 'unique:users');

// Fluent approach
field('email')->required()->email()->unique('users');
```

## Available Validation Methods

### Common Validation Methods

#### Required and Nullable

```php
field('name')->required();
field('bio')->nullable();
```

#### Type Validation

```php
field('email')->email();
field('age')->integer();
field('price')->numeric();
field('is_active')->boolean();
field('tags')->array();
field('description')->string();
field('metadata')->json();
field('website')->url();
field('uuid')->uuid();
field('birthday')->date();
field('created_at')->datetime();
```

#### Numeric Constraints

```php
field('age')->integer()->min(18)->max(100);
field('price')->numeric()->between(0, 99999.99);
field('quantity')->integer()->min(0);
field('rating')->numeric()->between(1, 5);
```

#### String Constraints

```php
field('username')->string()->min(3)->max(20);
field('title')->string()->max(255);
field('code')->string()->size(6); // Exactly 6 characters
```

#### Password Validation

```php
field('password')->required()->password()->confirmed();
field('password')->required()->password(10); // Minimum 10 characters
```

#### Unique and Exists Validation

```php
// Basic unique validation
field('email')->unique('users');

// Unique with custom column
field('slug')->unique('posts', 'slug');

// Unique with ignore (useful for updates)
field('email')->unique('users', 'email', $userId);

// Exists validation
field('category_id')->exists('categories', 'id');
field('user_id')->required()->exists('users');
```

#### Date Validation

```php
field('start_date')->date()->after('today');
field('end_date')->date()->afterOrEqual('start_date');
field('birth_date')->date()->before('today');
field('expired_at')->datetime()->beforeOrEqual('2024-12-31');
field('scheduled_at')->datetime()->dateFormat('Y-m-d H:i:s');
```

#### File Validation

```php
field('document')->isFile()->max(5120); // 5MB
field('avatar')->isImage()->max(2048); // 2MB
```

#### Pattern Validation

```php
field('phone')->regex('/^[0-9]{10}$/');
field('username')->alphaDash(); // Letters, numbers, dashes, underscores
field('name')->alpha(); // Letters only
field('code')->alphaNum(); // Letters and numbers only
```

#### IP Address Validation

```php
field('ip')->ip();
field('ipv4')->ipv4();
field('ipv6')->ipv6();
```

#### Conditional Validation

```php
// Required if another field has a specific value
field('phone')->requiredIf('contact_method', 'phone');

// Required unless another field has a specific value
field('reason')->requiredUnless('status', 'approved');

// Required with other fields
field('password_confirmation')->requiredWith('password');

// Required with all specified fields
field('state')->requiredWithAll(['country', 'city']);

// Required without other fields
field('email')->requiredWithout('phone');
```

#### In/Not In Validation

```php
field('status')->in(['pending', 'approved', 'rejected']);
field('role')->notIn(['admin', 'super-admin']);
```

#### Other Useful Methods

```php
// Field must be accepted (yes, on, 1, or true)
field('terms')->accepted();

// Field must be confirmed (field_confirmation must exist)
field('email')->confirmed();

// Field must be different from another field
field('new_password')->different('current_password');

// Field must be the same as another field
field('password_confirmation')->same('password');

// Field must match current user's password
field('current_password')->currentPassword();

// Field must be filled if present
field('description')->filled();

// Field must be present in request
field('token')->present();

// String must start/end with specific values
field('url')->startsWith(['http://', 'https://']);
field('filename')->endsWith(['.jpg', '.png', '.pdf']);

// Timezone validation
field('timezone')->timezone();

// MAC address validation
field('mac')->macAddress();

// Multiple of value
field('quantity')->integer()->multipleOf(5);
```

## Complex Examples

### User Registration Form

```php
public function fields(RestifyRequest $request)
{
    return [
        field('name')->required()->string()->min(2)->max(100),
        
        field('email')->required()->email()->unique('users'),
        
        field('username')
            ->required()
            ->string()
            ->min(3)
            ->max(20)
            ->alphaDash()
            ->unique('users'),
        
        field('password')
            ->required()
            ->password()
            ->confirmed(),
        
        field('age')->nullable()->integer()->min(13)->max(120),
        
        field('terms_accepted')->required()->accepted(),
        
        field('notification_email')
            ->requiredIf('receive_notifications', true)
            ->email(),
    ];
}
```

### Product Form

```php
public function fields(RestifyRequest $request)
{
    return [
        field('name')->required()->string()->max(255),
        
        field('slug')->required()->string()->unique('products'),
        
        field('price')->required()->numeric()->min(0)->max(999999.99),
        
        field('sale_price')->nullable()->numeric()->between(0, 999999.99),
        
        field('sku')->required()->string()->size(8)->unique('products'),
        
        field('status')->required()->in(['draft', 'published', 'archived']),
        
        field('published_at')
            ->nullable()
            ->datetime()
            ->afterOrEqual('today'),
        
        field('category_id')->required()->exists('categories', 'id'),
        
        field('weight')->nullable()->numeric()->min(0),
        
        field('is_featured')->boolean(),
    ];
}
```

### Article Form with Conditional Rules

```php
public function fields(RestifyRequest $request)
{
    return [
        field('title')->required()->string()->max(200),
        
        field('content')->required()->string()->min(100),
        
        field('excerpt')
            ->requiredUnless('auto_excerpt', true)
            ->string()
            ->max(500),
        
        field('featured_image')
            ->requiredIf('is_featured', true)
            ->isImage()
            ->max(5120),
        
        field('publish_date')
            ->requiredIf('status', 'published')
            ->date()
            ->afterOrEqual('today'),
        
        field('tags')->nullable()->array(),
        
        field('meta_description')
            ->nullable()
            ->string()
            ->between(50, 160),
    ];
}
```

## Combining with Traditional Rules

You can still combine fluent methods with the traditional `rules()` method when needed:

```php
field('email')
    ->required()
    ->email()
    ->rules('unique:users,email,' . $userId)
    ->rules(new CustomEmailRule);
```

## Custom Validation Messages

Validation methods can be combined with custom messages:

```php
field('email')
    ->required()
    ->email()
    ->unique('users')
    ->messages([
        'required' => 'Email address is required.',
        'email' => 'Please provide a valid email address.',
        'unique' => 'This email is already registered.',
    ]);
```