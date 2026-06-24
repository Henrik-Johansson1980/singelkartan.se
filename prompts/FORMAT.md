# Format

Run the full format pipeline and test suite. Fix any failures before committing.

## Steps

```bash
composer run format
php artisan test --compact
```

`composer run format` runs in order:
1. **Rector** — applies structural refactors (readonly, type declarations, dead code removal, etc.)
2. **Pint** — re-formats any code Rector changed
3. **PHPStan** — static analysis at level 8 via Larastan; must exit clean

## Rules

- All three tools must exit with no errors. No suppressions, no `@phpstan-ignore`, no skipped tests.
- If any step fails, fix the underlying issue and re-run the full pipeline — not just the failing step.
- Run `composer run format:check` to verify without applying changes (useful for CI).
