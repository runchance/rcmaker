# Delivery And Source Protection

rcmaker V3 is the current development line in this repository. Use `official/doc/V2/` only for legacy V2 projects and do not silently mix V2 configuration or APIs into V3 work.

## Distinguish The Artifacts

| Purpose | x86_64 example | AArch64 example |
| --- | --- | --- |
| Standalone PHP runtime | `php85` | `php85_aarch64` |
| micro SFX stub used to build one executable | `php85.micro.sfx` | `php85.micro.aarch64.sfx` |
| Source-protection tool | `rcmakerbeast` | `rcmakerbeast_aarch64` |

`php85_aarch64` is an executable PHP runtime, not a micro SFX file. Never substitute it for `php85.micro.aarch64.sfx` in packaging logic.

Prefer installing the selected executable tools under `/usr/local/bin` on Linux and invoking their stable command names. Confirm execute permissions and architecture with system tools before debugging the application.

## Build A Standalone Application

From the project root, with the required runtime and SFX assets available:

```bash
php -d phar.readonly=0 ./scripts/buildBin.php --with-php=8.5 --arch=auto
```

Build with source protection when required:

```bash
php -d phar.readonly=0 ./scripts/buildBin.php --with-php=8.5 --arch=auto --encrypt
```

Use an explicit architecture when CI is cross-building or auto-detection is inappropriate. Read `scripts/buildBin.php` before changing options; its current implementation is authoritative.

The expected deliverable is typically `build/rcmaker.bin`. Keep `.env` beside the binary when the project reads external deployment configuration that way. Do not embed production credentials into the package.

## Packaged Runtime Behavior

- A packaged application does not provide source-code reload. Rebuild and restart after source changes.
- Configuration loaded at startup still requires the affected process group to restart.
- Verify writable runtime/log/upload paths on the target host.
- Verify native extensions, CA certificates, timezone data, fonts, shared libraries and external commands needed by the application.
- Smoke-test signals, graceful shutdown, process supervision, logging, health checks and reverse-proxy forwarding.
- Build and test each supported CPU architecture; filename selection alone cannot make an x86_64 binary run on AArch64.

## Protect General PHP Scripts

Use the documented `encryptPhp`/rcmakerbeast workflow for independent PHP scripts that are not packaged as the main rcmaker application. Select `rcmakerbeast_aarch64` for AArch64 targets. Verify that protected output executes with the target runtime before deleting or archiving source inputs.

Source protection raises reverse-engineering cost. It does not protect runtime secrets, database credentials, API tokens, decrypted in-memory values, or insecure application behavior.

## Delivery Checklist

- Target OS, libc expectations, CPU architecture and PHP version are explicit.
- Runtime executable and micro SFX names are not confused.
- Build input excludes tests, local secrets, caches and unnecessary development files.
- The packaged binary starts without source tree assumptions.
- Main and bound APP groups listen on documented ports and can be supervised independently.
- Static roots and preload behavior work on the target platform.
- Health, shutdown, logs and restart behavior are verified.
- A clean-host smoke test covers one dynamic route, one static asset, storage access and required external services.

See `official/doc/md/install.md`, `official/doc/md/scripts/buildBin.md`, and `official/doc/md/scripts/encryptPhp.md` for the project-facing commands and download locations.
