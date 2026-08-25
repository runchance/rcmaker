# rcmaker AI Development Instructions

This repository is an rcmaker project. Before changing production code, configuration or framework documentation, read and follow:

1. `.agents/skills/rcmaker-development/SKILL.md`
2. The task-specific references linked from that Skill
3. The corresponding framework documentation under `.agents/doc/`
4. Current project usage and, when an API remains unclear, `vendor/runchance/rcmaker-framework/src/`

Do not start implementation until the framework capability mapping required by the Skill is complete.

## Mandatory Baseline

- Reuse rcmaker capabilities whenever they already exist. Do not recreate them with native PHP or overlapping Composer packages.
- Validate external input with the rcmaker Validator or AutoForm validation rules.
- Use AutoForm for standard CRUD, records, lists and pagination; use `SDB()` when AutoForm is unsuitable; use `DB()` only for complex SQL that SDB cannot reasonably express.
- Use framework `curl()` / `curl(true)` for scraping, external APIs, downloads and outbound HTTP.
- Follow the Skill rules for Request/Response, cache, Session, Token, Queue, multi-application binding, APP process groups and static preload.
- Treat Windows, SQLite and file cache as possible development settings. Production code must remain compatible with Linux multi-process operation, Redis/cache, MySQL and PostgreSQL through configuration, without hardcoded drivers, paths, endpoints or single-process state.
- Native PHP remains valid for language constructs, transformations, algorithms and domain logic not already provided by rcmaker.
- Native clients may be used in isolated AI-generated tests for independent verification, but test exceptions must never enter production code.
- When rcmaker lacks a required capability, place the custom implementation in the existing responsibility directories under `apps/`, `support/`, `config/`, `public/`, `view/`, `tests/`, `scripts/` or project documentation. Never use the repository root as a default location for business code, services, tasks, tests, demos or temporary files.

When framework Markdown changes, update matching files in both `official/doc/` and `.agents/doc/`.
