# Delivery And Source Protection

This repository and its `.agents/doc/` directory describe the current rcmaker V3 line. Do not introduce configuration or APIs from older releases.

## Distinguish The Artifacts

| Purpose | Linux x86_64 example | Linux AArch64 example |
| --- | --- | --- |
| Standalone PHP runtime archive | `php8.5-linux-x86_64.zip` | `php8.5-linux-aarch64.zip` |
| Micro SFX archive used to build one executable | `php8.5-micro-linux-x86_64.zip` | `php8.5-micro-linux-aarch64.zip` |
| Source-protection tool archive | `rcmakerbeast-linux-x86_64.zip` | `rcmakerbeast-linux-aarch64.zip` |

Every archive contains exactly one file. Runtime archives contain `php` or `php.exe`, Micro archives contain `micro.sfx`, and protection-tool archives contain `rcmakerbeast` or `rcmakerbeast.exe`. Never substitute the standalone runtime for `micro.sfx` in packaging logic.

The filename grammar is `php{version}-{platform}-{arch}.zip`, `php{version}-micro-{platform}-{arch}.zip`, and `rcmakerbeast-{platform}-{arch}.zip`. Supported targets are Linux x86_64/AArch64, macOS x86_64/AArch64, and Windows x86_64. Artifacts are downloaded from `https://rcmaker.runchance.com/download/`. The single-file Composer bootstrap is downloaded directly from `https://rcmaker.runchance.com/download/composer`; it is not a ZIP artifact and does not use platform or architecture suffixes.

Prefer installing the selected executable tools under `/usr/local/bin` on Linux and invoking their stable command names. Confirm execute permissions and architecture with system tools before debugging the application.

## Build A Standalone Application

For guided local operations, prefer the framework-native interactive console:

```bash
php index.php interact
```

The interactive implementation lives under `RC\Cli` and directly performs artifact download, binary construction, source protection, systemd management, and token-key generation. It must not include, require, spawn, or otherwise depend on `scripts/*.php`. Treat `interact` as the maintained default for local delivery work and user-facing instructions. When building for Windows, the framework builder must inject its framework-owned entry and must not read or copy the project-root `windows.php`; older projects may update only the Composer framework package.

Do not recommend `scripts/*.php` for new projects or new automation. Those files are compatibility fallbacks and may be removed at any time. Only when the user explicitly needs an existing unattended workflow that has not migrated may you document the legacy command, clearly labeled as temporary fallback behavior:

```bash
php -d phar.readonly=0 ./scripts/buildBin.php --with-php=8.5 --platform=auto --arch=auto
```

Build with source protection when required:

```bash
php -d phar.readonly=0 ./scripts/buildBin.php --with-php=8.5 --platform=auto --arch=auto --encrypt
```

Encryption executes the protection tool for the build host; its protected PHP payload is platform-independent. The Micro SFX still follows the selected target platform and architecture, so protected binaries may be cross-built. For current behavior, inspect `RC\Cli\Interactive` and its command classes. Inspect `scripts/` only for an explicitly requested legacy fallback; do not use those files as the source of truth for the framework-native workflow.

The expected deliverable is `build/rcmaker.bin` on Linux/macOS and `build/rcmaker.exe` on Windows. Keep `.env` beside the binary when the project reads external deployment configuration that way. Do not embed production credentials into the package.

The binary builder excludes known non-runtime inputs by default: Markdown/reStructuredText/source maps; AI, IDE and VCS directories; root build, runtime, scripts, tests, tools, coverage, Node modules and official-site sources; Composer/npm manifests and development-tool configuration; and dependency tests, examples, benchmarks and docs under `vendor/`. User-supplied exclusions are additional to this policy.

Do not broaden the default policy to all JSON, YAML, XML, certificates, templates or `public/` assets because applications may load them at runtime. `.env` is retained by default for compatibility; exclude it explicitly when deployment provides it beside the executable.

## Packaged Runtime Behavior

- A packaged application does not provide source-code reload. Rebuild and restart after source changes.
- Configuration loaded at startup still requires the affected process group to restart.
- Verify writable runtime/log/upload paths on the target host.
- Verify native extensions, CA certificates, timezone data, fonts, shared libraries and external commands needed by the application.
- Smoke-test signals, graceful shutdown, process supervision, logging, health checks and reverse-proxy forwarding.
- Build and test each supported CPU architecture; filename selection alone cannot make an x86_64 binary run on AArch64.

## Protect General PHP Scripts

Use option 2 in `php index.php interact` for independent PHP files or directories that are not packaged as the main rcmaker application. Let `RC\Cli\EncryptPhp` select `rcmakerbeast-{platform}-{arch}.zip`; do not construct legacy suffix-based names. Verify that protected output executes with the target runtime before deleting or archiving source inputs. The old `scripts/encryptPhp.php` entry is a compatibility fallback that may be removed at any time, not the recommended workflow.

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

Read `.agents/doc/md/interact.md` first, followed by `.agents/doc/md/download.md` and `.agents/doc/md/install.md`. Read documents under `.agents/doc/md/scripts/` only when maintaining an explicitly requested legacy fallback.
