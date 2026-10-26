# Batch HTTP downloads and fetches

`Batch` is a small cURL-multi utility bundled with this package. It does not use
`Chain` internally: `Batch` schedules independent HTTP requests, while `Chain`
transforms one current value.

It requires the `ext-curl` PHP extension.

## Fetch response bodies into memory

Pass an ordinary list of URLs and get an array of successful bodies:

```php
use tommyknocker\chain\Batch;

$bodies = Batch::fetch($urls)
    ->concurrency(12)
    ->timeout(10)
    ->run()
    ->throwIfFailed()
    ->bodies();
```

Input keys are preserved. This makes an `id => url` map convenient without
additional bookkeeping:

```php
$articles = Batch::fetch([
    101 => 'https://example.test/articles/101.txt',
    205 => 'https://example.test/articles/205.txt',
])->run()->throwIfFailed()->bodies();

// [101 => '...', 205 => '...']
```

`fetch()` accepts only successful HTTP statuses (2xx) by default. To accept a
specific non-2xx result, opt in explicitly:

```php
$report = Batch::fetch($urls)->acceptStatuses(200, 304)->run();
```

`fetch()` holds each response body in memory. Its default per-response limit is
16 MiB; set a tighter or wider limit with `maxBodySize()`:

```php
$report = Batch::fetch($urls)->maxBodySize(2 * 1024 * 1024)->run();
```

Use `download()` for large responses.

## Download a list of URLs

```php
$report = Batch::download($urls)
    ->toDirectory('/var/app/images')
    ->concurrency(8)
    ->run();
```

The default file name is the final segment of the URL path; query parameters do
not become part of the name. A URL with no usable final segment becomes
`download-{key}`. Files are streamed into a temporary file and atomically moved
to their final path only after a successful 2xx response.

Existing files and two jobs that resolve to the same name produce individual
failures rather than being overwritten silently. Call `overwrite()` only when
replacement is intentional.

### ID-to-URL maps

Keys remain available in the report even when names come from URLs:

```php
$report = Batch::download([
    101 => 'https://cdn.example.test/a.jpg',
    205 => 'https://cdn.example.test/b.jpg',
])->toDirectory('/var/app/images')->run();

$path = $report->download(205)?->path();
```

### Explicit file names

When keys are file names, say so explicitly:

```php
$report = Batch::download([
    'avatar-101.jpg' => 'https://cdn.example.test/a.jpg',
    'avatar-205.jpg' => 'https://cdn.example.test/b.jpg',
])->toDirectory('/var/app/avatars')->fileNamesFromKeys()->run();
```

For an ID-based naming convention, provide one small resolver:

```php
$report = Batch::download($imageUrls)
    ->toDirectory('/var/app/images')
    ->fileNameUsing(static fn (int $id, string $url): string => "{$id}.jpg")
    ->run();
```

Names must be plain file names, not directory paths. This deliberately keeps all
writes within `toDirectory()`.

## Working with partial failures

`run()` always returns a `Report`; one failed URL does not stop other queued
URLs. Inspect the successful and failed work separately:

```php
$report = Batch::fetch($urls)->run();

foreach ($report->responses() as $id => $response) {
    $payload = $response->json();
}

foreach ($report->failures() as $id => $failure) {
    error_log($failure->url() . ': ' . $failure->message());
}
```

Call `throwIfFailed()` when the calling operation is all-or-nothing. It throws
`BatchFailedException`, which retains the report for diagnostics.

`Response` supplies `body()`, `json()`, status, headers, and duration.
`Download` supplies its final path, byte count, status, headers, and duration.
`Failure` supplies the key, URL, failure type, cURL error code when available,
and HTTP status when available.
