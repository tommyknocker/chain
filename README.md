# Chain

A lightweight chaining utility for PHP 8.4+.  
It allows you to fluently call methods across different objects, switch the current context on the fly,
and still access the last result or the current instance.

---

## Why Chain?

### The problem
In plain PHP, when you need to work with multiple objects, you often end up with verbose code:

```php
$user = new User('Alice');
$name = $user->getName();

$order = $container->get('order');
$total = $order->getTotal();

$user2 = $container->get('user');
$name2 = $user2->getName();
```

This is repetitive, breaks the flow, and mixes object creation, method calls, and dependency resolution.

### The solution

`Chain` provides a fluent, expressive way to work with objects:

```php
$chain = Chain::from(new User('Alice'))
    ->getName()
    ->change('order')
    ->getTotal()
    ->change('user')
    ->getName();

echo $chain->result(); // "Alice"

```

### Benefits

* *Fluent syntax*: write less boilerplate, focus on the logic.
* *Context switching*: move between objects seamlessly with `change()`.
* *Integration with DI*: resolve objects from a registry or PSR‑11 container.
* *Functional helpers*: `tap` and `map` let you debug, log, or transform objects inline.
* *Consistency*: always access the last result with `result()` and the current object with `instance()`.

### When to use

* Building pipelines where objects change along the way.
* Writing expressive tests with minimal setup code.
* Debugging or logging intermediate states without breaking the chain.
* Rapid prototyping when you want to focus on flow, not boilerplate.

---

## Installation

```bash
composer require tommyknocker/chain
```
## Quick example

```php
use tommyknocker\chain\Chain;

// Start with a User object
$user = new User('Bob');

// Switch to an Order object from the registry/DI container
// Then switch back to the original User
$chain = Chain::from($user)
    ->getName()                 // "Bob"
    ->change('order')           // switch to Order
    ->getTotal()                // 99.95
    ->change('user')            // switch back to User (Alice)
    ->getName();                // "Alice"

echo $chain->result();          // "Alice"    
```
---

## Features

* Fluent chaining across multiple objects
* Context switching with `change()`
* Dependency injection support via a resolver (PSR‑11 compatible)
* Functional helpers:
  * `tap(callable $fn)` — run a callback without changing the context
  * `map(callable $fn)` — transform the current instance into a new object
* Accessors:
  * `$chain->result()` — last method call result
  * `$chain->instance()` — current object in the chain 

---

## Usage

### Creating a chain

```php
$chain = Chain::from(new Foo());
```

Or create an object by class name:

```php
$chain = Chain::make(Foo::class, 'arg1', 'arg2');
```

### Calling methods

```php
$chain->someMethod('param');
echo $chain->result();
```

### Switching context

```php
$chain
    ->change('order')   // resolved from registry or DI
    ->getTotal();
```

### Using tap

```php
$chain->tap(function ($instance) {
    // Log or debug without changing the chain
    error_log(get_class($instance));
});
```

### Using map

```php
$chain->map(fn($user) => new Profile($user->getName()))
      ->getProfileName();
```

### Resolver / Registry

```php
$registry = new Registry();
$registry->set('user', new User('Alice'));
$registry->set('order', new Order(99.95));

Chain::setResolver($registry);
```

Now you can switch by key:

```php
$chain->change('order')->getTotal();
```

---

## Advanced examples

### Combining `tap`, `map`, and `change`

You can mix functional helpers with context switching to build expressive chains:

```php
use tommyknocker\chain\Chain;

// Assume we have a registry with "user" and "order" already set
$chain = Chain::from(new User('Bob'))
    ->tap(function ($u) {
        // Log the current user
        echo "Current user: " . $u->getName() . PHP_EOL;
    })
    ->map(fn($u) => new Profile($u->getName()))   // transform User -> Profile
    ->tap(function ($p) {
        // Log the profile
        echo "Profile created: " . $p->getProfileName() . PHP_EOL;
    })
    ->change('order')                             // switch to Order from registry/DI
    ->getTotal()
    ->change('user')                              // switch back to User (Alice)
    ->getName();

echo $chain->result(); // "Alice"
```

### Using map for DTO transformation

```php
$chain = Chain::from(new User('Alice'))
    ->map(fn(User $u) => new UserDTO($u->getName(), $u->getEmail()))
    ->getEmail();

echo $chain->result(); // e.g. "alice@example.com"
```

### Using tap for debugging

```php
$chain = Chain::from(new Order(199.50))
    ->tap(fn($o) => error_log("Order total: " . $o->getTotal()))
    ->getTotal();

echo $chain->result(); // 199.50
```

These helpers make it easy to add logging, debugging, or transformations without breaking the fluent chain.

---

## Testing

```bash
./vendor/bin/phpunit --bootstrap vendor/autoload.php tests
```
or
```bash
composer run-script test
```