# Upgrade Guide

## 0.x → 1.0

Version 1.0 rebuilt the package on Nimble's real native API
(https://softvelum.com/nimble/api/). The 0.x services for streams,
sessions, DVR, pull, server and cache called endpoints that do not exist
in Nimble and could never work against a real server, so most 0.x call
sites need updating.

### Requirements

- PHP 8.3+ (was 8.1+)
- Laravel 12+ (was 10+)

### Streams

Nimble only exposes *currently live* streams; there is no stream CRUD.

| 0.x | 1.0 |
| --- | --- |
| `streams()->list()` | `streams()->list()` — now returns live streams with real fields (`app`, `stream`, `bandwidth`, `resolution`, `vcodec`, `acodec`, `protocol`, `publisherIp`, ...) |
| `streams()->get($id)` | `streams()->find($app, $stream)` (returns `null` when not live) |
| `streams()->publish()` / `unpublish()` | removed — no such endpoint; see `publishControl()->deny()` to disconnect publishers |
| `streams()->statistics($id)` | removed — stats are part of the live stream entry |
| `streams()->liveStatus($name)` / `allLiveStreams()` | `streams()->find($app, $stream)` / `streams()->list()` |

### Sessions

| 0.x | 1.0 |
| --- | --- |
| `sessions()->get($id)` | `sessions()->find((int) $id)` (returns `null` when unknown) |
| `sessions()->terminate('id')` | `sessions()->terminate(4)` or `terminate([4, 5])` |
| `sessions()->statistics($id)` | removed — bytes/timestamps are on `SessionDto` |

### Restreaming

Reworked in 0.2 to the real `/manage/rtmp/republish` API; 1.0 adds the
reconciler. If you are still on 0.1: `add()`/`update()` are gone — build a
`RestreamDto` and use `create()` / `delete()` / `sync()`.

### DVR

| 0.x | 1.0 |
| --- | --- |
| `dvr()->listArchives()` | `dvr()->status()` (per-stream archive info) |
| `dvr()->getArchive($id)` | `dvr()->status($app, $stream, timeline: true)` |
| `dvr()->deleteArchive($id)` | `dvr()->cleanupArchive($app, $stream, ...)` |
| `dvr()->configure([...])` | removed — DVR is configured in Nimble config/WMSPanel |

### Server

| 0.x | 1.0 |
| --- | --- |
| `server()->status()->status/version/uptime` | `server()->status()->connections/outRate/ramCacheSize/...` |
| `server()->reload()` | `server()->reloadConfig()` |
| `server()->sync()` | `server()->syncPanelSettings()` |

### Cache

The 0.x cache methods targeted nonexistent endpoints. 1.0 wraps Nimble's
data cache: `cache()->key($originUrl)` and `cache()->delete($key, dryRun:)`.

### Pull

`PullService` is removed entirely — stream pulling is not part of the
native API (WMSPanel manages pull configs).

### Events, enums, DTOs

- `StreamPublished` / `StreamUnpublished` events removed.
- `SessionTerminated::$sessionId` is now an `int`; `CacheCleared::$key`
  replaces `$type`.
- `StreamStatus` / `PublishAction` enums removed; `AuthSchema` added.
- `ArchiveDto` → `DvrStreamDto`; `StreamStatsDto` folded into `StreamDto`;
  `StreamDto` / `SessionDto` / `ServerStatusDto` now carry real wire fields.

### Validation rules

`StreamExistsRule` now validates that a stream is live: pass the app in the
constructor (`new StreamExistsRule(app: 'live')`) or validate `"app/stream"`
values.

### Artisan commands

- `nimble:streams --filter=` → `nimble:streams --app=`
- `nimble:cache:clear --type=` → `nimble:cache:clear {key} [--dry-run]`
