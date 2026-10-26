# Benchmarks

Run the method-cache comparison with:

```bash
php benchmarks/method-cache.php
```

Run it several times on the deployment PHP runtime before treating the result as a performance decision. The benchmark measures cached versus uncached method-call validation inside `Chain`; it is not an application-level throughput benchmark.
