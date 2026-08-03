# Laravel Nimble Package

A comprehensive Laravel package for the [Nimble Streamer](https://softvelum.com/nimble/) native management API. Inspect live streams and sessions, manage RTMP republishing, DVR archives, publish control, the data cache and more with a clean, expressive Laravel interface.

**Developed by Alex Hackney**

[![Latest Version on Packagist](https://img.shields.io/packagist/v/alexhackney/lara-nimble.svg?style=flat-square)](https://packagist.org/packages/alexhackney/lara-nimble)
[![Tests](https://img.shields.io/github/actions/workflow/status/alexhackney/lara-nimble/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/alexhackney/lara-nimble/actions/workflows/tests.yml)
[![Coverage](https://img.shields.io/codecov/c/github/alexhackney/lara-nimble?style=flat-square)](https://codecov.io/gh/alexhackney/lara-nimble)
[![Total Downloads](https://img.shields.io/packagist/dt/alexhackney/lara-nimble.svg?style=flat-square)](https://packagist.org/packages/alexhackney/lara-nimble)
[![License](https://img.shields.io/packagist/l/alexhackney/lara-nimble.svg?style=flat-square)](https://packagist.org/packages/alexhackney/lara-nimble)
[![PHP Version](https://img.shields.io/badge/php-8.3%20%7C%208.4%20%7C%208.5-blue?style=flat-square)]()
[![Laravel Version](https://img.shields.io/badge/laravel-12%20%7C%2013-red?style=flat-square)]()

Every service maps 1:1 to endpoints documented in the [Nimble native API reference](https://softvelum.com/nimble/api/) — nothing is invented, and every request/response shape is covered by tests.

## Features

- ✅ **Live Streams**: Inspect currently publishing streams (bandwidth, resolution, codecs, publisher)
- ✅ **RTMP Republishing**: Create, list, delete and monitor republish rules (YouTube, Facebook, Twitch, ...)
- ✅ **Restream Reconciler**: `sync()` converges the server onto your desired rule set — built for Nimble's non-persistent API rules
- ✅ **First-class Testing**: `Nimble::fake()` with request recording and assertions
- ✅ **Session Management**: List and terminate active client sessions
- ✅ **DVR Control**: Archive status, MP4 export, reload and cleanup
- ✅ **Publish Control**: List active publishers and deny (disconnect) them
- ✅ **Server Management**: Status, config reload, SSL certificate reload, WMSPanel sync, playlist status
- ✅ **Data Cache**: Resolve cache keys and evict cached items
- ✅ **Protocol Insights**: RTMP settings, MPEG-TS status/settings, SRT/RIST stats, NDI list
- ✅ **Icecast**: Read stream info and inject metadata
- ✅ **SCTE-35**: Insert cue-out/cue-in/time_signal ad markers (Nimble Advertizer)
- ✅ **Type Safety**: Strict types, readonly DTOs, PHP 8.3+
- ✅ **Laravel Integration**: Service provider, facade, config, artisan commands, events, validation rules

## Requirements

- PHP 8.3, 8.4 or 8.5
- Laravel 12 or 13
- Nimble Streamer with the management API enabled

## Installation

### Step 1: Install via Composer

```bash
composer require alexhackney/lara-nimble
```

### Step 2: Publish Configuration

```bash
php artisan vendor:publish --provider="AlexHackney\LaraNimble\NimbleServiceProvider" --tag="nimble-config"
```

This will create a `config/nimble.php` file in your Laravel application.

### Step 3: Configure Environment Variables

**Minimal Configuration** - Add only what you need to your `.env` file:

```env
# Required: Your Nimble server hostname
NIMBLE_HOST=your-nimble-server.com

# Optional: Only if your Nimble server requires authentication
NIMBLE_TOKEN=your-secret-token
```

That's it! The package uses sensible defaults for everything else.

**Optional Overrides** - Only add these if you need to change the defaults:

```env
# Connection (defaults shown)
NIMBLE_PORT=8082              # Default Nimble management port
NIMBLE_PROTOCOL=http          # Use 'https' for production

# Timeouts (in seconds)
NIMBLE_TIMEOUT=30             # Request timeout
NIMBLE_CONNECT_TIMEOUT=10     # Connection timeout

# Retry Logic
NIMBLE_RETRY_TIMES=3          # Number of retry attempts
NIMBLE_RETRY_SLEEP=100        # Milliseconds between retries

# Debug/Logging
NIMBLE_LOG_REQUESTS=false     # Enable to log all requests
NIMBLE_LOG_CHANNEL=stack      # Laravel log channel

# SSL (for self-signed certs in development only)
NIMBLE_VERIFY_SSL=true        # Set to false to disable SSL verification
```

### Step 4: Enable Nimble API

Ensure your Nimble server has the API enabled. Edit `/etc/nimble/nimble.conf`:

```conf
management_port = 8082;
management_token = your-secret-token;  # Optional but recommended
```

Restart Nimble after configuration changes:

```bash
sudo systemctl restart nimble
```

## Usage

### Live Streams

Nimble's native API exposes *currently publishing* streams via `/manage/live_streams_status`. A stream that is not live simply does not appear.

```php
use AlexHackney\LaraNimble\Facades\Nimble;

// List all live streams across all applications
$streams = Nimble::streams()->list();
foreach ($streams as $stream) {
    echo "{$stream->app}/{$stream->stream}\n";
    echo "  Protocol: {$stream->protocol}\n";
    echo "  Resolution: {$stream->resolution}\n";
    echo "  Codecs: {$stream->vcodec} / {$stream->acodec}\n";
    echo "  Bandwidth: " . round(($stream->bandwidth ?? 0) / 1_000_000, 2) . " Mbps\n";
    echo "  Publisher: {$stream->publisherIp}:{$stream->publisherPort}\n";
}

// Only one application
$streams = Nimble::streams()->byApp('live');

// Find one stream (null when it is not live)
$stream = Nimble::streams()->find('live', 'stream1');

// Convenience boolean
if (Nimble::streams()->exists('live', 'stream1')) {
    echo 'Stream is live!';
}
```

### Restream Management (RTMP Republishing)

Restreaming uses Nimble's native RTMP republishing API (`/manage/rtmp/republish`).

```php
use AlexHackney\LaraNimble\DTOs\RestreamDto;
use AlexHackney\LaraNimble\Facades\Nimble;

// List republishing rules created through this API
$rules = Nimble::restream()->list();
foreach ($rules as $rule) {
    echo "Rule {$rule->id}: {$rule->srcApp}/{$rule->srcStream}";
    echo " -> {$rule->destAddr}:{$rule->destPort}/{$rule->destApp}/{$rule->destStream}\n";
}

// Get a specific rule (null when it does not exist)
$rule = Nimble::restream()->get(42);

// Create a rule from explicit fields
$created = Nimble::restream()->create(new RestreamDto(
    srcApp: 'live',
    srcStream: 'stream1',
    destAddr: 'a.rtmp.youtube.com',
    destPort: 1935,
    destApp: 'live2',
    destStream: 'your-stream-key',
));
echo "Created rule {$created->id}";

// Or decompose an RTMP(S) publishing URL, e.g. Facebook's secure_stream_url
$created = Nimble::restream()->create(RestreamDto::fromUrl(
    'live',
    'stream1',
    'rtmps://live-api-s.facebook.com:443/rtmp/your-stream-key'
));

// Delete a rule
if (Nimble::restream()->delete(42)) {
    echo "Rule deleted!";
}

// Connection statistics for all rules
foreach (Nimble::restream()->stats() as $stat) {
    echo "Rule {$stat->id}: {$stat->state}, {$stat->bandwidth} bandwidth\n";
}
```

#### Reconciling rules with sync()

Because API-created rules disappear on Nimble reloads, the typical pattern
is a reconciler: declare the rules you want, and let `sync()` converge the
server. Rules are compared by fingerprint (every field except `id`);
missing rules are created, unwanted or duplicate rules deleted, matching
rules left untouched. WMSPanel-defined rules are never affected because
the native API cannot see them.

```php
use AlexHackney\LaraNimble\DTOs\RestreamDto;
use AlexHackney\LaraNimble\Facades\Nimble;

$desired = [
    RestreamDto::fromUrl('live', 'stream1', 'rtmps://live-api-s.facebook.com:443/rtmp/fb-key'),
    RestreamDto::fromUrl('live', 'stream1', 'rtmp://a.rtmp.youtube.com/live2/yt-key'),
];

$result = Nimble::restream()->sync($desired);

if ($result->changed()) {
    logger()->info('Restream rules reconciled', [
        'created' => $result->created->count(),
        'deleted' => $result->deleted->count(),
        'kept' => $result->kept->count(),
    ]);
}
```

Run it on deploy, on a schedule, or after `Nimble::server()->reloadConfig()` —
it is idempotent. `RestreamRuleCreated` / `RestreamRuleDeleted` events fire
for every change, so you get an audit trail for free.

Notes from the Nimble API docs:

- `src_stream` is optional in the raw API, but omitting it republishes **every** stream in the source application, so `RestreamDto` refuses to build a create payload without it.
- `list()` only returns rules created through the native API — rules defined in WMSPanel do not appear.
- Rules created through the native API are not persisted across a Nimble config reload or restart; recreate them as needed.

### Session Management

```php
use AlexHackney\LaraNimble\Facades\Nimble;

// List all active sessions
$sessions = Nimble::sessions()->list();
foreach ($sessions as $session) {
    echo "Session {$session->id}: {$session->app}/{$session->stream}\n";
    echo "  Type: {$session->type}\n";           // HLS, MPEG-DASH, ...
    echo "  Client IP: {$session->clientIp}\n";
    echo "  Bytes sent: {$session->bytesSent}\n";
}

// Find one session (null when it does not exist)
$session = Nimble::sessions()->find(4);

// Terminate one or many sessions
Nimble::sessions()->terminate(4);
Nimble::sessions()->terminate([4, 5, 6]);
```

### DVR Management

```php
use AlexHackney\LaraNimble\Facades\Nimble;

// Archive status for all DVR-enabled streams
$archives = Nimble::dvr()->status();
foreach ($archives as $archive) {
    echo "{$archive->stream}: {$archive->duration}s, {$archive->size} bytes\n";
}

// One stream, including its recorded period timeline
$archive = Nimble::dvr()->status('live', 'stream1', timeline: true)->first();
foreach ($archive->timeline as $period) {
    echo "Period {$period['period']}: {$period['start']} - {$period['end']}\n";
}

// Build a download URL for an MP4 export (auth params included automatically)
$url = Nimble::dvr()->exportMp4Url('live', 'stream1', start: 1700000000, end: 1700003600);

// Stream an MP4 export straight to a local file (no memory buffering,
// no request timeout by default — safe for long archives)
Nimble::dvr()->exportMp4ToFile('live', 'stream1', storage_path('exports/show.mp4'), start: 1700000000, end: 1700003600);

// Or download the MP4 bytes through PHP (small ranges only)
$mp4 = Nimble::dvr()->exportMp4('live', 'stream1', start: 1700000000, end: 1700003600);

// SRT subtitle export from a DVR range
$srtUrl = Nimble::dvr()->exportSrtUrl('live', 'stream1', start: 1700000000, end: 1700003600);
$srt = Nimble::dvr()->exportSrt('live', 'stream1', start: 1700000000, end: 1700003600, track: 1, lang: 'en');

// Reload an archive from disk
Nimble::dvr()->reload('live', 'stream1');

// Cleanup: keep only the most recent 60 minutes
Nimble::dvr()->cleanupArchive('live', 'stream1', targetDepth: 60);

// Cleanup: remove a specific range
Nimble::dvr()->cleanupArchive('live', 'stream1', from: 1700000000, to: 1700003600);
```

### Publish Control

Requires publish control to be enabled in Nimble's RTMP settings.

```php
use AlexHackney\LaraNimble\Facades\Nimble;

// List active publishers
$publishers = Nimble::publishControl()->status();
foreach ($publishers as $publisher) {
    echo "{$publisher->id}: {$publisher->stream} from {$publisher->ip}\n";
}

// Disconnect publishers by id
Nimble::publishControl()->deny('pub-1');
Nimble::publishControl()->deny(['pub-1', 'pub-2']);
```

### Server Management

```php
use AlexHackney\LaraNimble\Facades\Nimble;

// Get server status
$status = Nimble::server()->status();
echo "Connections: {$status->connections}\n";
echo "Out rate: {$status->outRate}\n";
echo "RAM cache: {$status->ramCacheSize} / {$status->maxRamCacheSize}\n";
echo "File cache: {$status->fileCacheSize} / {$status->maxFileCacheSize}\n";
// $status->sysInfo holds the raw SysInfo array as returned by Nimble

// Reload server configuration (optionally including drm.conf)
Nimble::server()->reloadConfig();
Nimble::server()->reloadConfig(drm: true);

// Reload SSL certificates without a restart
Nimble::server()->reloadSslCertificates();

// Trigger settings sync with WMSPanel
Nimble::server()->syncPanelSettings();

// Server playlist status (raw array, shape defined by Nimble)
$playlists = Nimble::server()->playlistStatus();
```

### Data Cache

Nimble's data cache holds responses for remote VOD and similar content, addressed by keys derived from origin URLs.

```php
use AlexHackney\LaraNimble\Facades\Nimble;

// Resolve the cache key for an origin URL (null when unknown)
$key = Nimble::cache()->key('http://origin:8081/vod/sample.mp4');

// Evict cached items; returns the list of removed items
$removed = Nimble::cache()->delete($key);

// Dry run: report what would be removed without removing it
$wouldRemove = Nimble::cache()->delete($key, dryRun: true);
```

### Protocol Status & Settings

These endpoints return structures defined by Nimble or the respective protocol specs, so they are exposed as raw arrays.

```php
use AlexHackney\LaraNimble\Facades\Nimble;

$rtmp = Nimble::protocols()->rtmpSettings();       // RtmpSettings structure
$mpegts = Nimble::protocols()->mpegtsStatus();
$cameras = Nimble::protocols()->mpegtsSettings();  // CamerasHash + Cameras
$srtOut = Nimble::protocols()->srtSenderStats();   // fields per SRT spec
$srtIn = Nimble::protocols()->srtReceiverStats();
$ristOut = Nimble::protocols()->ristSenderStats(); // fields per RIST spec
$ristIn = Nimble::protocols()->ristReceiverStats();
$ndi = Nimble::protocols()->ndiList();
```

### Icecast

```php
use AlexHackney\LaraNimble\Facades\Nimble;

// Current metadata of an Icecast stream
$info = Nimble::icecast()->info('radio', 'main');
echo $info['icy-name'] ?? '';
echo $info['streamtitle'] ?? '';

// Inject new metadata
Nimble::icecast()->updateMetadata('radio', 'main', 'Artist - Song');
Nimble::icecast()->updateMetadata('radio', 'main', 'Artist - Song', 'https://example.com');
```

### SCTE-35 Ad Markers

Requires the Nimble Advertizer feature.

```php
use AlexHackney\LaraNimble\Facades\Nimble;

// Start an ad break (optionally with a duration in seconds)
Nimble::scte35()->cueOut('live', 'stream1', 30);

// End an ad break
Nimble::scte35()->cueIn('live', 'stream1');

// Insert a time_signal marker
Nimble::scte35()->timeSignal('live', 'stream1', segType: 52, upidType: 14, upid: 'abc123');
```

### Using in Controllers

```php
<?php

namespace App\Http\Controllers;

use AlexHackney\LaraNimble\DTOs\RestreamDto;
use AlexHackney\LaraNimble\Facades\Nimble;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StreamController extends Controller
{
    public function index(): JsonResponse
    {
        $streams = Nimble::streams()->list();

        return response()->json([
            'streams' => $streams->map->toArray(),
        ]);
    }

    public function restream(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'stream' => ['required', 'string'],
            'target_url' => ['required', 'string'],
        ]);

        $rule = Nimble::restream()->create(RestreamDto::fromUrl(
            'live',
            $validated['stream'],
            $validated['target_url'],
        ));

        return response()->json(['rule_id' => $rule->id], 201);
    }

    public function sessions(): JsonResponse
    {
        $sessions = Nimble::sessions()->list();

        return response()->json([
            'sessions' => $sessions->map->toArray(),
            'count' => $sessions->count(),
        ]);
    }
}
```

### Using in Jobs/Queues

```php
<?php

namespace App\Jobs;

use AlexHackney\LaraNimble\DTOs\RestreamDto;
use AlexHackney\LaraNimble\Facades\Nimble;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreateRestreamJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $srcApp,
        public string $srcStream,
        public string $targetUrl,
    ) {
    }

    public function handle(): void
    {
        Nimble::restream()->create(RestreamDto::fromUrl(
            $this->srcApp,
            $this->srcStream,
            $this->targetUrl,
        ));
    }
}
```

## Configuration

The package is designed for **minimal configuration**. Only two settings are required in your `.env`:

```env
NIMBLE_HOST=your-server.com    # Required
NIMBLE_TOKEN=your-token        # Optional, only if server requires auth
```

All other settings have sensible defaults that work for most use cases. The `config/nimble.php` file shows all available options with their defaults:

```php
return [
    // Connection (only NIMBLE_HOST is required)
    'host' => env('NIMBLE_HOST', 'localhost'),          // Your Nimble server
    'port' => env('NIMBLE_PORT', 8082),                 // Standard Nimble API port
    'protocol' => env('NIMBLE_PROTOCOL', 'http'),       // http or https

    // Authentication (optional)
    'token' => env('NIMBLE_TOKEN'),                     // Only if server requires it

    // Request settings (rarely need changing)
    'timeout' => env('NIMBLE_TIMEOUT', 30),             // Seconds
    'connect_timeout' => env('NIMBLE_CONNECT_TIMEOUT', 10),
    'retry_times' => env('NIMBLE_RETRY_TIMES', 3),      // Auto-retry failed requests
    'retry_sleep' => env('NIMBLE_RETRY_SLEEP', 100),    // Milliseconds

    // Logging (for debugging)
    'log_requests' => env('NIMBLE_LOG_REQUESTS', false),
    'log_channel' => env('NIMBLE_LOG_CHANNEL', 'stack'),

    // SSL (dev only - never disable in production)
    'verify_ssl' => env('NIMBLE_VERIFY_SSL', true),
];
```

**You only need to override these in `.env` if the defaults don't work for your setup.**

### Optional Response Caching

Dashboards polling stream or server status every second shouldn't hammer
the Nimble API. When enabled, `streams()->list()` (and therefore
`byApp()`/`find()`/`exists()`) and `server()->status()` are served from a
short-TTL cache:

```env
NIMBLE_CACHE_ENABLED=true
NIMBLE_CACHE_TTL=2          # seconds (default 2)
NIMBLE_CACHE_STORE=redis    # optional; defaults to your default cache store
```

Disabled by default. Note that `StreamExistsRule` also sees cached data
while this is on.

## Testing Your Application

`Nimble::fake()` swaps the manager for a test double. The real services
and DTO parsing still run — only the HTTP layer is faked — so your tests
exercise exactly the code paths production uses.

```php
use AlexHackney\LaraNimble\Facades\Nimble;

public function test_it_creates_a_restream_rule(): void
{
    $fake = Nimble::fake([
        // 'METHOD /endpoint' or just '/endpoint'; * wildcards supported;
        // values are response arrays or closures
        'POST /manage/rtmp/republish' => ['status' => 'Ok', 'rule' => [
            'id' => 7,
            'src_app' => 'live',
            'src_stream' => 'stream1',
            'dest_addr' => 'live-api-s.facebook.com',
            'dest_port' => 443,
            'dest_app' => 'rtmp',
            'dest_stream' => 'fb-key',
            'ssl' => true,
        ]],
    ]);

    $this->post('/api/restreams', ['stream' => 'stream1', 'target_url' => '...'])
        ->assertCreated();

    $fake->assertRestreamCreated(fn (array $payload) => $payload['ssl'] === true);
    $fake->assertSentCount(1);
}
```

Unstubbed endpoints respond `{"status": "Ok"}`, so happy paths work with
no stubs at all. Available assertions: `assertSent()`, `assertNotSent()`,
`assertSentCount()`, `assertNothingSent()`, `assertRestreamCreated()`,
`assertRestreamDeleted()`; callbacks receive
`(string $method, string $endpoint, array $data, array $query)`.
Facades also support standard Mockery mocking (`Nimble::shouldReceive()`)
if you prefer stubbing at the service level.

## Integration Tests (against a real server)

The package ships an opt-in suite that runs read-only checks plus a full
republish-rule lifecycle against a live Nimble instance:

```bash
NIMBLE_TEST_HOST=your-server.com NIMBLE_TEST_TOKEN=your-token composer test-integration
```

Without `NIMBLE_TEST_HOST` the suite skips itself, so `composer test`
stays hermetic. Use this once against staging to prove your Nimble
version matches the package's expectations.

## Artisan Commands

### Check Server Status

```bash
php artisan nimble:status
```

Displays connections, out rate and RAM/file cache usage.

### List Live Streams

```bash
php artisan nimble:streams

# Only one application
php artisan nimble:streams --app=live
```

Lists currently live streams with protocol, resolution, bandwidth and publisher.

### Clear Data Cache

```bash
# Resolve the key first via Nimble::cache()->key($originUrl)
php artisan nimble:cache:clear "127.0.0.1:8081/vod/sample.mp4"

# Report what would be removed without removing it
php artisan nimble:cache:clear "127.0.0.1:8081/vod/sample.mp4" --dry-run
```

### Health Check

```bash
php artisan nimble:health
```

Performs a connectivity and configuration check of your Nimble server.

## Validation Rules

The package includes custom validation rules for Laravel forms:

```php
use AlexHackney\LaraNimble\Rules\NimbleHostRule;
use AlexHackney\LaraNimble\Rules\StreamExistsRule;
use AlexHackney\LaraNimble\Rules\StreamProtocolRule;

// Validate Nimble host connectivity
$request->validate([
    'host' => ['required', new NimbleHostRule],
]);

// Validate streaming protocol
$request->validate([
    'protocol' => ['required', new StreamProtocolRule],
]);

// Validate that a stream is currently live, scoped to an application
$request->validate([
    'stream' => ['required', new StreamExistsRule(app: 'live')],
]);

// Or accept "app/stream" values
$request->validate([
    'stream' => ['required', new StreamExistsRule],
]);
```

## Laravel Events

### Available Events

- `AlexHackney\LaraNimble\Events\RestreamRuleCreated` — fired after `restream()->create()` (and per create in `sync()`), carrying the created `RestreamDto`
- `AlexHackney\LaraNimble\Events\RestreamRuleDeleted` — fired after a successful `restream()->delete()` (and per delete in `sync()`)
- `AlexHackney\LaraNimble\Events\SessionTerminated` — fired per session id after a successful `sessions()->terminate()`
- `AlexHackney\LaraNimble\Events\CacheCleared` — fired after `cache()->delete()` removes items (not on dry runs)

### Listening to Events

```php
<?php

namespace App\Listeners;

use AlexHackney\LaraNimble\Events\SessionTerminated;
use Illuminate\Support\Facades\Log;

class LogSessionTerminated
{
    public function handle(SessionTerminated $event): void
    {
        Log::info('Nimble session terminated', ['session_id' => $event->sessionId]);
    }
}
```

Register in `EventServiceProvider`:

```php
protected $listen = [
    \AlexHackney\LaraNimble\Events\SessionTerminated::class => [
        \App\Listeners\LogSessionTerminated::class,
    ],
];
```

## Testing

```bash
# Run all tests
composer test

# Run with coverage
composer test-coverage

# Run static analysis
composer analyse

# Format code
composer format
```

Unit tests cover every service against mocked HTTP with request path, method and body assertions, plus all DTOs and the HTTP client; feature tests cover the Laravel integration.

## Troubleshooting

### Connection Issues

If you're getting connection errors:

1. Verify Nimble is running: `sudo systemctl status nimble`
2. Check firewall allows port 8082
3. Verify `NIMBLE_HOST` and `NIMBLE_PORT` in `.env`
4. Test API manually: `curl http://your-server:8082/manage/server_status`

### Authentication Errors

If you're getting 401/403 errors:

1. Verify `management_token` in `/etc/nimble/nimble.conf` matches your `NIMBLE_TOKEN`
2. If not using authentication, remove `NIMBLE_TOKEN` from `.env`
3. Restart Nimble after config changes

### SSL Verification Issues

For development/testing with self-signed certificates:

```env
NIMBLE_VERIFY_SSL=false
```

**Note:** Never disable SSL verification in production!

## API Documentation

For detailed Nimble API documentation, visit:
- [Nimble API Reference](https://softvelum.com/nimble/api/)
- [Nimble Configuration Guide](https://softvelum.com/nimble/configure/)

## Development

### Project Structure

```
lara-nimble/
├── src/
│   ├── Client/           # HTTP client & authentication
│   ├── Commands/         # Artisan commands
│   ├── DTOs/             # Data Transfer Objects
│   ├── Enums/            # Enums (protocols)
│   ├── Events/           # Laravel events
│   ├── Exceptions/       # Custom exceptions
│   ├── Facades/          # Laravel facades
│   ├── Rules/            # Validation rules
│   ├── Services/         # Service classes
│   ├── Nimble.php        # Main manager class
│   └── NimbleServiceProvider.php
├── tests/
│   ├── Feature/          # Laravel integration tests
│   └── Unit/             # Unit tests
└── config/
    └── nimble.php        # Package configuration
```

## Contributing

We welcome contributions! To contribute code:

1. Fork the repository and create a feature branch
2. Follow PSR-12 coding standards (`composer format`)
3. Write tests for new features
4. Ensure all tests pass: `composer test`
5. Run static analysis: `composer analyse`
6. Open a Pull Request

When adding endpoints, verify them against the [Nimble API reference](https://softvelum.com/nimble/api/) first — this package only wraps documented endpoints.

## Support

For issues and questions:
- **GitHub Issues**: [Report an issue](https://github.com/alexhackney/lara-nimble/issues)
- **Source Code**: [View on GitHub](https://github.com/alexhackney/lara-nimble)

## Security

If you discover a security vulnerability, please email security@alexhackney.com instead of using the issue tracker. All security vulnerabilities will be promptly addressed.

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history and release notes.

## License

The MIT License (MIT). See [LICENSE](LICENSE) for details.

---

**Repository**: https://github.com/alexhackney/lara-nimble
