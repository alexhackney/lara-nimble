# Security Policy

## Supported Versions

We release patches for security vulnerabilities in the following versions:

| Version | Supported          |
| ------- | ------------------ |
| 0.x.x   | :white_check_mark: |

## Reporting a Vulnerability

If you discover a security vulnerability within Laravel Nimble Package, please send an email to Alex Hackney at **security@alexhackney.com**. All security vulnerabilities will be promptly addressed.

**Please do not report security vulnerabilities through public GitHub issues.**

### What to Include

When reporting a vulnerability, please include:

- Type of vulnerability (e.g., SQL injection, XSS, authentication bypass)
- Full paths of source file(s) related to the vulnerability
- Location of the affected source code (tag/branch/commit or direct URL)
- Any special configuration required to reproduce the issue
- Step-by-step instructions to reproduce the issue
- Proof-of-concept or exploit code (if possible)
- Impact of the issue, including how an attacker might exploit it

### Response Timeline

- **Acknowledgment**: Within 48 hours
- **Initial Assessment**: Within 5 business days
- **Fix Timeline**: Varies based on severity and complexity
- **Public Disclosure**: After fix is released and users have had time to update

## Security Best Practices

When using this package:

1. **Never commit credentials**: Keep your `NIMBLE_TOKEN` in `.env` and never commit it to version control
2. **Use HTTPS**: Always set `NIMBLE_PROTOCOL=https` in production
3. **Enable SSL Verification**: Never set `NIMBLE_VERIFY_SSL=false` in production
4. **Restrict Access**: Limit access to your Nimble management API
5. **Keep Updated**: Regularly update to the latest version of this package
6. **Secure Your Nimble Server**:
   - Use strong `management_token` values
   - Restrict management API access with firewall rules
   - Keep Nimble Streamer updated

## Security Features

This package includes:

- Token-based authentication support
- SSL/TLS verification
- Request timeout protection
- Input validation and sanitization
- Type-safe DTOs and enums
- No SQL database queries (API-only communication)

## Disclosure Policy

When we receive a security bug report, we will:

1. Confirm the problem and determine affected versions
2. Audit code to find any similar problems
3. Prepare fixes for all supported releases
4. Release new security fix versions as soon as possible
5. Credit the reporter (if desired) in the release notes

## Comments on This Policy

If you have suggestions for improving this policy, please submit a pull request.
