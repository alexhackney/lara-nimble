# Laravel Nimble Package

A comprehensive Laravel package for seamless integration with Nimble Streamer API. Manage streams, DVR, sessions, restreaming, and more with a clean, expressive Laravel interface.

**Developed by Alex Hackney**

[![Latest Version on Packagist](https://img.shields.io/packagist/v/alexhackney/lara-nimble.svg?style=flat-square)](https://packagist.org/packages/alexhackney/lara-nimble)
[![Total Downloads](https://img.shields.io/packagist/dt/alexhackney/lara-nimble.svg?style=flat-square)](https://packagist.org/packages/alexhackney/lara-nimble)
[![License](https://img.shields.io/packagist/l/alexhackney/lara-nimble.svg?style=flat-square)](https://packagist.org/packages/alexhackney/lara-nimble)
[![PHP Version](https://img.shields.io/badge/php-8.1%2B-blue?style=flat-square)]()
[![Laravel Version](https://img.shields.io/badge/laravel-10%20%7C%2011-red?style=flat-square)]()

## Features

- ✅ **Stream Management**: Create, configure, publish/unpublish, and monitor streams
- ✅ **Session Management**: Monitor and control active client sessions
- ✅ **DVR Control**: Manage archives, recordings, and playback
- ✅ **Restreaming**: Configure and manage restream targets
- ✅ **Stream Pulling**: Pull streams from external sources
- ✅ **Server Management**: Monitor server status, reload configuration, and sync
- ✅ **Cache Control**: Manage server cache and statistics
- 🚧 **Icecast Integration**: Manage Icecast server and inject metadata (coming soon)
- 🚧 **Playlist Management**: Create and control server playlists (coming soon)
- ✅ **Real-Time Statistics**: Get live stream stats including bandwidth, resolution, codecs, and more
- ✅ **Protocol Support**: RTMP, MPEG-TS, SRT, NDI, HLS, RTSP
- ✅ **Test-Driven Development**: Comprehensive test coverage with 127 tests
- ✅ **Type Safety**: Full PHP 8.1+ type hints and enums
- ✅ **Laravel Integration**: Service provider, facade, and configuration

## Requirements

- PHP 8.1 – 8.5
- Laravel 10, 11, 12 or 13
- Nimble Streamer with API enabled
- Composer

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

### Stream Management

```php
use AlexHackney\LaraNimble\Facades\Nimble;

// List all streams
$streams = Nimble::streams()->list();
foreach ($streams as $stream) {
    echo "Stream: {$stream->name} ({$stream->status->value})\n";
    echo "Protocol: {$stream->protocol->value}\n";
    echo "Viewers: {$stream->viewers}\n";
}

// Get a specific stream
$stream = Nimble::streams()->get('stream-123');
echo "Status: {$stream->status->value}";

// Publish a stream
if (Nimble::streams()->publish('live', 'my-stream')) {
    echo "Stream published successfully!";
} else {
    echo "Failed to publish stream";
}

// Unpublish a stream
if (Nimble::streams()->unpublish('live', 'my-stream')) {
    echo "Stream unpublished successfully!";
}

// Get stream statistics
$stats = Nimble::streams()->statistics('stream-123');
echo "Bitrate: {$stats['bitrate']} kbps\n";
echo "Viewers: {$stats['viewers']}\n";
echo "Duration: {$stats['duration']} seconds\n";
```

### Real-Time Stream Statistics

Get comprehensive, real-time statistics for active streams including bandwidth, resolution, codecs, protocol information, and publisher details. Perfect for monitoring dashboards and live stream health checks.

```php
use AlexHackney\LaraNimble\Facades\Nimble;

// Get real-time stats for a specific stream by stream key
$stats = Nimble::streams()->liveStatus('my-stream-key');

if ($stats) {
    echo "Stream: {$stats->streamName}\n";
    echo "Application: {$stats->application}\n";
    echo "Protocol: {$stats->protocol}\n";
    echo "Resolution: {$stats->resolution}\n";
    echo "Video Codec: {$stats->videoCodec}\n";
    echo "Audio Codec: {$stats->audioCodec}\n";
    echo "Bandwidth: " . ($stats->bandwidth / 1000000) . " Mbps\n";
    echo "Bitrate: {$stats->bitrate} kbps\n";
    echo "FPS: {$stats->fps}\n";
    echo "Viewers: {$stats->viewers}\n";
    echo "Duration: {$stats->duration} seconds\n";
    echo "Publisher IP: {$stats->publisherIp}\n";
    echo "Publisher Port: {$stats->publisherPort}\n";
    echo "Source URL: {$stats->sourceUrl}\n";
    echo "Started: {$stats->startTime}\n";
} else {
    echo "Stream is not currently live\n";
}

// Get real-time stats for ALL currently active streams
$allStreams = Nimble::streams()->allLiveStreams();

foreach ($allStreams as $stream) {
    echo "Stream: {$stream->streamName}\n";
    echo "  Protocol: {$stream->protocol}\n";
    echo "  Resolution: {$stream->resolution}\n";
    echo "  Bandwidth: " . ($stream->bandwidth / 1000000) . " Mbps\n";
    echo "  Viewers: {$stream->viewers}\n";
    echo "\n";
}

// Example: Build a real-time monitoring dashboard
$liveStreams = Nimble::streams()->allLiveStreams();

$dashboard = $liveStreams->map(function ($stream) {
    return [
        'name' => $stream->streamName,
        'app' => $stream->application,
        'status' => 'live',
        'protocol' => $stream->protocol,
        'quality' => $stream->resolution,
        'codecs' => [
            'video' => $stream->videoCodec,
            'audio' => $stream->audioCodec,
        ],
        'network' => [
            'bandwidth_mbps' => round($stream->bandwidth / 1000000, 2),
            'bitrate_kbps' => $stream->bitrate,
            'publisher_ip' => $stream->publisherIp,
        ],
        'metrics' => [
            'viewers' => $stream->viewers,
            'fps' => $stream->fps,
            'uptime_seconds' => $stream->duration,
        ],
    ];
});

return response()->json(['streams' => $dashboard]);
```

**Use Cases for Real-Time Statistics:**
- **Live Monitoring**: Display current stream health in admin dashboards
- **Quality Alerts**: Detect bitrate drops or resolution changes
- **Viewer Analytics**: Track real-time viewer counts
- **Stream Discovery**: List all currently active streams
- **Protocol Monitoring**: Track which protocols are being used (RTMP, SRT, NDI)
- **Network Analysis**: Monitor bandwidth usage and publisher connections

### Session Management

```php
use AlexHackney\LaraNimble\Facades\Nimble;

// List all active sessions
$sessions = Nimble::sessions()->list();
foreach ($sessions as $session) {
    echo "Session: {$session->id}\n";
    echo "Client IP: {$session->clientIp}\n";
    echo "Protocol: {$session->protocol}\n";
    echo "Duration: {$session->duration} seconds\n";
}

// Get a specific session
$session = Nimble::sessions()->get('session-456');
echo "Stream: {$session->streamId}";

// Terminate a session
if (Nimble::sessions()->terminate('session-456')) {
    echo "Session terminated successfully!";
}

// Get session statistics
$stats = Nimble::sessions()->statistics('session-456');
echo "Bytes transferred: {$stats['bytes_transferred']}\n";
```

### DVR Management

```php
use AlexHackney\LaraNimble\Facades\Nimble;

// List all DVR archives
$archives = Nimble::dvr()->listArchives();
foreach ($archives as $archive) {
    echo "Archive: {$archive->filename}\n";
    echo "Stream: {$archive->streamId}\n";
    echo "Size: {$archive->size} bytes\n";
    echo "Duration: {$archive->duration} seconds\n";
}

// Get a specific archive
$archive = Nimble::dvr()->getArchive('archive-123');
echo "Path: {$archive->path}";

// Delete an archive
if (Nimble::dvr()->deleteArchive('archive-123')) {
    echo "Archive deleted successfully!";
}

// Configure DVR settings
if (Nimble::dvr()->configure([
    'stream' => 'stream1',
    'enabled' => true,
    'path' => '/var/dvr/stream1',
    'max_duration' => 3600,
])) {
    echo "DVR configured successfully!";
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

Notes from the Nimble API docs:

- `src_stream` is optional in the raw API, but omitting it republishes **every** stream in the source application, so `RestreamDto` refuses to build a create payload without it.
- `list()` only returns rules created through the native API — rules defined in WMSPanel do not appear.
- Rules created through the native API are not persisted across a Nimble config reload or restart; recreate them as needed.

### Stream Pulling

```php
use AlexHackney\LaraNimble\Facades\Nimble;

// List all pull configurations
$pulls = Nimble::pull()->list();
foreach ($pulls as $pull) {
    echo "Source: {$pull->sourceUrl}\n";
    echo "Local: {$pull->localApp}/{$pull->localStream}\n";
    echo "Protocol: {$pull->protocol}\n";
    echo "Status: {$pull->status}\n";
}

// Get a specific pull configuration
$pull = Nimble::pull()->get('pull-123');
echo "Source URL: {$pull->sourceUrl}";

// Add a new pull configuration
if (Nimble::pull()->add([
    'source_url' => 'rtmp://source.com/live/stream',
    'local_app' => 'live',
    'local_stream' => 'pulled-stream',
    'protocol' => 'rtmp',
    'enabled' => true,
])) {
    echo "Pull configuration added successfully!";
}

// Update a pull configuration
if (Nimble::pull()->update('pull-123', [
    'enabled' => false,
])) {
    echo "Pull configuration updated!";
}

// Delete a pull configuration
if (Nimble::pull()->delete('pull-123')) {
    echo "Pull configuration deleted!";
}

// Get pull stream status
$status = Nimble::pull()->status('pull-123');
echo "Uptime: {$status['uptime']} seconds\n";
echo "Bitrate: {$status['bitrate']} kbps\n";
```

### Server Management

```php
use AlexHackney\LaraNimble\Facades\Nimble;

// Get server status
$status = Nimble::server()->status();
echo "Status: {$status->status}\n";
echo "Version: {$status->version}\n";
echo "Uptime: {$status->uptime} seconds\n";
echo "Connections: {$status->connections}\n";
echo "Bandwidth In: {$status->bandwidth['in']} bytes\n";
echo "Bandwidth Out: {$status->bandwidth['out']} bytes\n";

// Reload server configuration
if (Nimble::server()->reload()) {
    echo "Server configuration reloaded successfully!";
}

// Sync with WMSPanel
if (Nimble::server()->sync()) {
    echo "Synchronization completed!";
}
```

### Cache Management

```php
use AlexHackney\LaraNimble\Facades\Nimble;

// Clear all cache
if (Nimble::cache()->clear()) {
    echo "Cache cleared successfully!";
}

// Clear specific cache type
if (Nimble::cache()->clear('hls')) {
    echo "HLS cache cleared!";
}

// Get cache statistics
$stats = Nimble::cache()->statistics();
echo "Total Size: {$stats['total_size']} bytes\n";
echo "Items: {$stats['items']}\n";
echo "Hit Rate: {$stats['hit_rate']}\n";
echo "Miss Rate: {$stats['miss_rate']}\n";

// Configure cache settings
if (Nimble::cache()->configure([
    'enabled' => true,
    'max_size' => 2147483648,
    'ttl' => 3600,
])) {
    echo "Cache configured successfully!";
}
```

### Using in Controllers

```php
<?php

namespace App\Http\Controllers;

use AlexHackney\LaraNimble\Facades\Nimble;
use Illuminate\Http\JsonResponse;

class StreamController extends Controller
{
    public function index(): JsonResponse
    {
        $streams = Nimble::streams()->list();

        return response()->json([
            'streams' => $streams->map->toArray(),
        ]);
    }

    public function publish(string $app, string $stream): JsonResponse
    {
        $success = Nimble::streams()->publish($app, $stream);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Stream published' : 'Failed to publish stream',
        ]);
    }

    public function unpublish(string $app, string $stream): JsonResponse
    {
        $success = Nimble::streams()->unpublish($app, $stream);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Stream unpublished' : 'Failed to unpublish stream',
        ]);
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

use AlexHackney\LaraNimble\Facades\Nimble;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PublishStreamJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $app,
        public string $stream
    ) {
    }

    public function handle(): void
    {
        $success = Nimble::streams()->publish($this->app, $this->stream);

        if (!$success) {
            $this->fail('Failed to publish stream');
        }
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

## Testing

The package includes comprehensive tests built with Test-Driven Development (TDD):

```bash
# Run all tests
composer test

# Run with coverage
composer test-coverage

# Run specific test suite
vendor/bin/phpunit tests/Unit/Services/StreamServiceTest.php
```

**Current Test Coverage:**
- 127 tests
- 330 assertions
- Unit tests for all services, DTOs, and HTTP client
- Feature tests for Laravel integration

## Troubleshooting

### Connection Issues

If you're getting connection errors:

1. Verify Nimble is running: `sudo systemctl status nimble`
2. Check firewall allows port 8082
3. Verify `NIMBLE_HOST` and `NIMBLE_PORT` in `.env`
4. Test API manually: `curl http://your-server:8082/manage/status`

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
│   ├── DTOs/             # Data Transfer Objects
│   ├── Enums/            # Enums (protocols, statuses)
│   ├── Exceptions/       # Custom exceptions
│   ├── Facades/          # Laravel facades
│   ├── Services/         # Service classes
│   ├── Nimble.php        # Main manager class
│   └── NimbleServiceProvider.php
├── tests/
│   ├── Feature/          # Laravel integration tests
│   └── Unit/             # Unit tests
└── config/
    └── nimble.php        # Package configuration
```

### Contributing

This package is currently in active development. Contributions welcome!

1. Follow PSR-12 coding standards
2. Write tests for new features (TDD approach)
3. Update documentation
4. Ensure all tests pass: `composer test`

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history.

## License

The MIT License (MIT). See [LICENSE](LICENSE) for details.

## Credits

**Developed by:** Alex Hackney

**Built with:**
- Laravel 10 – 13
- PHP 8.1 – 8.5
- Nimble Streamer API

## Artisan Commands

The package provides helpful Artisan commands for common operations:

### Check Server Status

```bash
php artisan nimble:status
```

Displays server status, version, uptime, connections, and bandwidth statistics.

### List Streams

```bash
php artisan nimble:streams

# Filter by status
php artisan nimble:streams --filter=active
php artisan nimble:streams --filter=inactive
```

Lists all streams with their status, protocol, viewers, and bitrate.

### Clear Cache

```bash
php artisan nimble:cache:clear

# Clear specific cache type
php artisan nimble:cache:clear --type=hls
php artisan nimble:cache:clear --type=dash
```

Clears Nimble server cache.

### Health Check

```bash
php artisan nimble:health
```

Performs a comprehensive health check of your Nimble server configuration.

## Validation Rules

The package includes custom validation rules for Laravel forms:

```php
use AlexHackney\LaraNimble\Rules\NimbleHostRule;
use AlexHackney\LaraNimble\Rules\StreamProtocolRule;
use AlexHackney\LaraNimble\Rules\StreamExistsRule;

// Validate Nimble host connectivity
$request->validate([
    'host' => ['required', new NimbleHostRule],
]);

// Validate streaming protocol
$request->validate([
    'protocol' => ['required', new StreamProtocolRule],
]);

// Validate stream exists
$request->validate([
    'stream_id' => ['required', new StreamExistsRule],
]);
```

## Laravel Events

The package dispatches events for key actions, allowing you to hook into important moments:

### Available Events

- `AlexHackney\LaraNimble\Events\StreamPublished` - Fired when a stream is published
- `AlexHackney\LaraNimble\Events\StreamUnpublished` - Fired when a stream is unpublished
- `AlexHackney\LaraNimble\Events\SessionTerminated` - Fired when a session is terminated
- `AlexHackney\LaraNimble\Events\CacheCleared` - Fired when cache is cleared

### Listening to Events

Create an event listener:

```php
<?php

namespace App\Listeners;

use AlexHackney\LaraNimble\Events\StreamPublished;
use Illuminate\Support\Facades\Log;

class LogStreamPublished
{
    public function handle(StreamPublished $event): void
    {
        Log::info('Stream published', [
            'app' => $event->app,
            'stream' => $event->stream,
        ]);

        // Send notification, update database, etc.
    }
}
```

Register in `EventServiceProvider`:

```php
protected $listen = [
    \AlexHackney\LaraNimble\Events\StreamPublished::class => [
        \App\Listeners\LogStreamPublished::class,
    ],
];
```

## Performance Optimizations

The package includes several performance optimizations:

- **Service Caching**: Service instances are cached within the Nimble manager to reduce memory usage
- **Connection Pooling**: HTTP connections are reused when possible
- **Retry Logic**: Automatic retry with exponential backoff for failed requests
- **Lazy Loading**: Services are only instantiated when needed

## Contributing

We welcome contributions from the community! Here's how you can help:

### Reporting Issues

If you find a bug or have a feature request, please:

1. Check if the issue already exists in [GitHub Issues](https://github.com/alexhackney/lara-nimble/issues)
2. If not, [create a new issue](https://github.com/alexhackney/lara-nimble/issues/new) with:
   - Clear description of the problem or feature
   - Steps to reproduce (for bugs)
   - Laravel and PHP versions
   - Nimble Streamer version
   - Any relevant code samples or error messages

### Pull Requests

We love pull requests! To contribute code:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Follow PSR-12 coding standards
4. Write tests for new features (we use TDD!)
5. Ensure all tests pass: `composer test`
6. Run code formatting: `composer format`
7. Run static analysis: `composer analyse`
8. Commit your changes (`git commit -m 'Add amazing feature'`)
9. Push to your branch (`git push origin feature/amazing-feature`)
10. Open a Pull Request

### Development Guidelines

- **Test-Driven Development**: Write tests first, then implement features
- **Type Safety**: Use strict types and full type hints
- **Documentation**: Update README and inline docs for new features
- **Backward Compatibility**: Don't break existing APIs without major version bump
- **Code Style**: Follow PSR-12, use Laravel conventions

### Running Tests

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

## Support

For issues and questions:
- **GitHub Issues**: [Report an issue](https://github.com/alexhackney/lara-nimble/issues)
- **Source Code**: [View on GitHub](https://github.com/alexhackney/lara-nimble)
- **Documentation**: [README](https://github.com/alexhackney/lara-nimble#readme)
- **Email**: alex@alexhackney.com

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history and release notes.

## Security

If you discover a security vulnerability, please email security@alexhackney.com instead of using the issue tracker. All security vulnerabilities will be promptly addressed.

---

**Made with ❤️ using Test-Driven Development**

**Repository**: https://github.com/alexhackney/lara-nimble
