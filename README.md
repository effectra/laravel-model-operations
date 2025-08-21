# LaravelModelOperations

`LaravelModelOperations` is a lightweight package that provides reusable **traits** for performing common Eloquent model operations in Laravel, such as creating, updating, deleting, and handling bulk actions.

It helps keep your controllers and services clean by centralizing repetitive model logic into reusable traits.

---

## Installation

You can install the package via Composer:

```bash
composer require effectra/laravel-model-operations
```

---

## Features

* 🎯 Simplified model operations (`create`, `createMany`, etc.)
* 🔄 Bulk operations with error handling
* 🛡️ Strong typing & exceptions for better safety
* 🧩 Easy to extend with your own logic

---

## The `UseCreate` Trait

The `UseCreate` trait provides methods to **create single or multiple model records** in a clean and reusable way.

### Importing the trait

```php
use LaravelModelOperations\Traits\UseCreate;

class UserService
{
    use UseCreate;

    protected string $model = \App\Models\User::class;
}
```

---

### Creating a single record

```php
// In a controller or service:
$userService = new UserService();

// Example request validation
$request = new \Illuminate\Http\Request([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => bcrypt('secret'),
]);

// Create user
$created = $userService->create($request);

if ($created) {
    $user = $userService->getModelCreated();
    dump("User created:", $user->toArray());
}
```

✅ **What happens here?**

* The data is validated (`$request->validated()`) before being used.
* A new `User` is created and stored in `$userService->getModelCreated()`.
* If creation fails, it simply returns `false`.

---

### Creating with default attributes

Sometimes you want to **add default values** during creation:

```php
$created = $userService->create(
    $request,
    ['status' => 'active'] // default values
);
```

This will merge defaults with the validated request before saving.

---

### Adding a callback after creation

You can pass a closure that runs after successful save:

```php
$created = $userService->create($request, [], function ($user) {
    // Send a welcome email
    \Mail::to($user->email)->send(new \App\Mail\WelcomeMail($user));
});
```

---

### Creating multiple records at once

```php
$request = new \Illuminate\Http\Request([
    [
        'name' => 'User 1',
        'email' => 'user1@example.com',
        'password' => bcrypt('secret'),
    ],
    [
        'name' => 'User 2',
        'email' => 'user2@example.com',
        'password' => bcrypt('secret'),
    ]
]);

$success = $userService->createMany($request);

if ($success) {
    echo "All users created successfully!";
} else {
    echo "Some users failed to create. Failed index: " . $userService->modelFailedIndex;
}
```

✅ **What happens here?**

* Each item in the request array is passed to `create()`.
* If all succeed → returns `true`.
* If one fails → returns `false` and stores the **failed index** for debugging.

---

## Exception Handling

When bulk creation fails, a `ManyOperationException` can be thrown with the index of the failed item.

```php
try {
    $success = $userService->createMany($request);
} catch (\ManyOperationException $e) {
    logger("Failed at index: " . $e->getIndex());
}
```

---

## License

This package is open-sourced under the [MIT license](LICENSE).

---