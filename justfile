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

# Run frontend component tests with Vitest
test-js:
    pnpm run test

# Format PHP and frontend code (Pint + Prettier)
format-code: pint format

# Run static analysis and lint fixes (PHPStan + ESLint)
lint-code: stan lint

# Run the full verification suite (PHP + frontend)
check: pint-check stan test format-check lint-check types-check test-js
