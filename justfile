sail := "./vendor/bin/sail"

# Run the test suite with Xdebug step debugging enabled for this run only
test *args:
    {{sail}} debug test {{args}}

# Format PHP code with Pint
pint:
    {{sail}} php ./vendor/bin/pint

# Check PHP code style without making changes
pint-check:
    {{sail}} php ./vendor/bin/pint --test

# Run PHP static analysis (PHPStan/Larastan)
stan:
    {{sail}} php ./vendor/bin/phpstan analyse --memory-limit=512M

# Format frontend code with Prettier
format:
    pnpm run format

# Check frontend formatting without making changes
format-check:
    pnpm run format:check

# Lint frontend code with ESLint (auto-fix)
lint:
    pnpm run lint

# Check frontend lint without making changes
lint-check:
    pnpm run lint:check

# Type-check the frontend with vue-tsc
types-check:
    pnpm run types:check

# Run the full verification suite (PHP + frontend)
check: pint-check stan test format-check lint-check types-check
