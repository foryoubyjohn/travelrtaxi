# AGENTS.md

Never commit or push directly to `main` or `master`.

Always create a feature branch using this format:

```
agent/<short-task-name>
```

## Before any commit

1. Show the files changed.
2. Show the diff summary.
3. Confirm no `package.json` lifecycle hooks were added.
4. Confirm no `.env`, token, key, or credential file was changed.
5. Ask for human approval.

## Hard rules

- Never run `npm install` unless explicitly approved.
- Never modify `package.json` scripts without explicit approval.
- Never add `postinstall`, `preinstall`, `install`, `prepare`, or `prepublish` scripts.
- Never deploy without explicit approval.
