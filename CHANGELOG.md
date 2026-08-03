# Changelog

All notable changes to `lara-nimble` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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
