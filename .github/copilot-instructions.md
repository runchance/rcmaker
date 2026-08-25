# rcmaker Repository Instructions

Before generating or changing production code, read `AGENTS.md` and `.agents/skills/rcmaker-development/SKILL.md`. Follow the task-specific references selected by the Skill and consult the matching local framework documentation under `.agents/doc/`.

Existing rcmaker capabilities are mandatory. Do not replace them with native PHP implementations or overlapping packages. Preserve the Skill's Validator, AutoForm -> SDB -> complex SQL only DB, framework `curl()`, multi-application, process group, static preload and Windows-development-to-Linux-production compatibility rules.
