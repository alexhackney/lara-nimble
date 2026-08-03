# Changelog

All notable changes to `lara-nimble` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2026-08-03

Every service now maps 1:1 to endpoints documented in Nimble's native API
(https://softvelum.com/nimble/api/). The 0.x services for streams, sessions,
DVR, pull, server and cache were built against endpoints that do not exist
in Nimble and could never work against a real server.

### Added
- `RestreamService::sync(iterable $desired)`: reconciler that converges the
  server's API-created rules onto a desired set (create missing, delete
  unwanted/duplicates, keep matches), with `RestreamDto::fingerprint()`/
  `matches()` and a `RestreamSyncResult`
- `Nimble::fake()`: test double with endpoint stubs (method-specific and
  wildcard patterns, closure stubs), request recording, and assertions
  (`assertSent`, `assertSentCount`, `assertNothingSent`,
  `assertRestreamCreated`, `assertRestreamDeleted`)
- `RestreamRuleCreated` / `RestreamRuleDeleted` events
- `AuthSchema` enum, accepted anywhere `RestreamDto` takes an auth schema
- `DvrService::exportMp4ToFile()` (streaming download, no memory buffering,
  no timeout by default), `exportSrtUrl()`, `exportSrt()`;
  `NimbleClient::download()` with Guzzle sink + per-call timeout
- Optional short-TTL response caching for `streams()->list()` and
  `server()->status()` via the `nimble.cache` config block (off by default)
- Env-gated integration test suite (`composer test-integration` with
  `NIMBLE_TEST_HOST`): read-only server checks plus a full republish rule
  lifecycle with cleanup
- `.gitattributes` export-ignore so composer dist installs no longer ship
  tests and development files
- `UPGRADE.md` with a 0.x → 1.0 migration guide
- `PublishControlService` (`Nimble::publishControl()`): list active publishers, deny by id
- `ProtocolService` (`Nimble::protocols()`): rtmp_settings, mpeg2ts status/settings, SRT/RIST sender+receiver stats, NDI list
- `IcecastService` (`Nimble::icecast()`): stream info, metadata injection
- `Scte35Service` (`Nimble::scte35()`): cue-out/cue-in/time_signal ad markers
- `DvrService::exportMp4Url()` / `exportMp4()`, `reload()`, `cleanupArchive()`
- `StreamService::byApp()`, `find()`, `exists()`
- `SessionService::find()`, multi-id `terminate()`
- `NimbleClient::url()` for building authenticated URLs (e.g. DVR export links)
- `NimbleClient::post()` now accepts query parameters alongside the JSON body
- New DTOs: `DvrStreamDto`, `PublishControlEntryDto`

### Changed
- **Breaking**: requires PHP 8.3+ and Laravel 12+ (dropped PHP 8.1/8.2, Laravel 10/11)
- **Breaking**: `ServerService` — `status()` now maps `GET /manage/server_status`
  (Connections/OutRate/caches/SysInfo); `reload()` → `reloadConfig()`
  (`/manage/reload_config`), `sync()` → `syncPanelSettings()`
  (`/manage/sync_panel_settings`); added `reloadSslCertificates()`, `playlistStatus()`
- **Breaking**: `StreamService` — rebuilt on `GET /manage/live_streams_status`;
  `get()`, `publish()`, `unpublish()`, `statistics()`, `liveStatus()`,
  `allLiveStreams()` removed (no such endpoints)
- **Breaking**: `SessionService` — `terminate()` posts id arrays to
  `/manage/sessions/delete`; `get()`/`statistics()` removed in favor of `find()`
- **Breaking**: `DvrService` — rebuilt on `dvr_status`/`export_mp4`/`reload`/
  `cleanup_archive`; `listArchives()`, `getArchive()`, `deleteArchive()`,
  `configure()` removed (no such endpoints)
- **Breaking**: `CacheService` — rebuilt on `/manage/data_cache` (`key()`,
  `delete()`); `clear()`, `statistics()`, `configure()` removed
- **Breaking**: `StreamDto`, `SessionDto`, `ServerStatusDto` reworked to real wire fields
- **Breaking**: `SessionTerminated` event carries an int id; `CacheCleared` carries the cache key
- **Breaking**: `StreamExistsRule` validates live streams by app + stream name
- `nimble:streams` lists live streams (`--app=` filter); `nimble:cache:clear` takes a key argument and `--dry-run`

### Removed
- **Breaking**: `PullService` and `PullDto` — stream pulling is not part of
  Nimble's native API (WMSPanel manages pull configs)
- **Breaking**: `ArchiveDto`, `StreamStatsDto` (superseded by `DvrStreamDto` and the reworked `StreamDto`)
- **Breaking**: `PublishAction` and `StreamStatus` enums, `StreamPublished`/`StreamUnpublished` events
- Unused `cache_*` keys from `config/nimble.php` (package-level caching was never implemented)

### Fixed
- `NimbleClient` no longer retries twice over (Guzzle middleware × manual
  loop could multiply attempts up to retry_times²)
- POST query parameters are no longer lost when a management token is
  configured (auth params previously replaced the URL query string)

## [0.2.0] - 2026-08-02

### Added
- Laravel 12 and Laravel 13 support (`illuminate/*` `^12.0|^13.0`)
- PHP 8.4 and PHP 8.5 support
- `RestreamDto::fromUrl()` to decompose RTMP(S) publishing URLs (e.g. Facebook's
  `secure_stream_url`) into `dest_addr`/`dest_port`/`dest_app`/`dest_stream`/`ssl`
- `RestreamService::stats()` for `GET /manage/rtmp/republish/stats`
- `RestreamStatsDto` for republishing connection statistics
- CI test matrix covering Laravel 10–13 on PHP 8.1–8.5 (runs on pull requests to main)

### Changed
- **Breaking**: `RestreamService` now targets Nimble's real RTMP republishing API
  (`/manage/rtmp/republish`); the previous `/manage/restream/*` endpoints do not
  exist in Nimble's native API
- **Breaking**: `RestreamDto` now carries the real republish rule fields
  (`src_app`, `src_stream`, `dest_addr`, `dest_port`, `dest_app`, `dest_stream`,
  plus optional `ssl`, `auth_schema`, `dest_login`, `dest_password`,
  `keep_src_stream_params`, `dest_app_params`, `dest_stream_params`) instead of
  `streamId`/`targetUrl`/`protocol`
- **Breaking**: `RestreamService::add()` was replaced by `create(RestreamDto)`,
  which returns the created rule with its assigned id
- **Breaking**: `RestreamService::update()` was removed — Nimble has no update
  endpoint; delete the rule and create a new one instead
- Tests migrated from `/** @test */` annotations to `#[Test]` attributes
  (doc-comment metadata is removed in PHPUnit 12+)
- GitHub Actions updated to Node 24 runtimes (`actions/cache@v6`,
  `softprops/action-gh-release@v3`)

### Fixed
- `nimble:status` no longer crashes on byte values above the TB range

## [0.1.0] - 2025-10-10

### Added
- Project initialization
- Package structure setup
- Configuration files
- Documentation framework
