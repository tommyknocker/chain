# State Model

Each chain holds two pieces of state:

- **Current object**: receiver for wrapped method calls, returned by `instance()`.
- **Current value**: returned by `get()` and `value()`.

At creation both point to the wrapped object. A value is always present after the first wrapped call, including when that call returns `null`.

| Operation | Current object | Current value |
| --- | --- | --- |
| Wrapped method or `invoke()` returns scalar / `null` | Unchanged | Returned value |
| Wrapped method or `invoke()` returns object | Returned object | Returned object |
| `change($object)` | Target object | Target object |
| `map($callback)` | Callback object result | Callback object result |
| `pipe()` returns scalar / `null` | Unchanged | Pipe result |
| `pipe()` returns object | Pipe object result | Pipe object result |
| `tap()` / `each()` / `dump()` | Unchanged | Unchanged |

This means separate `pipe()` calls compose through the last value:

```php
Chain::of($object)
    ->pipe(fn ($object) => 5)
    ->pipe(fn (int $value) => $value + 1)
    ->get(); // 6
```

Use `map()` or `change()` to turn a value back into an object context before invoking a wrapped method.
