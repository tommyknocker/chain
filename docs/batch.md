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

## Advanced requests, auth, and validation

Simple URL arrays remain supported. For per-job HTTP settings, pass an immutable
`HttpRequest` instead of a URL:

```php
$report = Batch::fetch([
    'catalog' => HttpRequest::get($catalogUrl)
        ->bearerToken($token)
        ->query(['page' => 2])
        ->expectJson(),
    'partner' => HttpRequest::post($partnerUrl)
        ->basicAuth($user, $password)
        ->json(['event' => 'sync']),
])->headers(['Accept' => 'application/json'])->run();
```

Batch headers are defaults; a header on `HttpRequest` replaces the same header
for that job, case-insensitively. `bearerToken()` is a convenience for the
Authorization header and `basicAuth()` configures cURL basic authentication.
When credentials are present, redirects are disabled by default so secrets are
not sent to a different host. Opt into redirect following explicitly with
`followRedirects(true)` only when that is safe for the target.

Use `expectContentType('image/*')`, `expectJson()`, or per-request equivalents
to reject a 2xx response with an unexpected body type. Such a download is
discarded before its temporary file is published.

## Retries and events

Retries are opt-in and do not block active transfers:

```php
$report = Batch::fetch($urls)
    ->retry(HttpRetryPolicy::transient(maxAttempts: 3))
    ->onFailed(static fn (BatchEvent $event) => logFailure($event->failure))
    ->onProgress(static fn (BatchEvent $event) => reportProgress($event))
    ->run();
```

The default retry policy retries only GET and HEAD requests after transport
errors, timeouts, and 408/429/502/503/504 responses. Retrying unsafe methods
requires `retryUnsafeMethods: true` in `HttpRetryPolicy`. `onFailed()` observes
only final failures; use `onAttemptFailed()` to observe an attempt that will be
retried. Observer exceptions are intentionally suppressed so logging or metrics
cannot change batch results.

## Workload controls

Use `maxJobs()` to reject unexpectedly large input before opening any HTTP
connections. `failFast()` stops scheduling new jobs after a final failure; jobs
already active are cleaned up. `cancel()` may be called by application code or
an observer to stop the remaining work explicitly. Cancellation is best-effort:
the report contains completed jobs and failures known before cancellation; it
does not synthesize failures for work that never started.

```php
$batch = Batch::download($urls)
    ->toDirectory('/var/app/images')
    ->maxJobs(5_000)
    ->failFast();

$batch->onProgress(static function (BatchEvent $event) use ($batch): void {
    if (shouldStopImport()) {
        $batch->cancel();
    }
});

$report = $batch->run();
```

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
