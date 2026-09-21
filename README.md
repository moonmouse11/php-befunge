# php-befunge

A [Befunge-93](https://en.wikipedia.org/wiki/Befunge) interpreter library for PHP.

The interpreter is self-contained: it has no global state, never touches
`STDIN`/`STDOUT` on its own, and receives all environment interaction through
constructor injection. That makes it safe to embed into any application.

## Requirements

- PHP >= 8.1

## Installation

```bash
composer require pots/php-befunge
```

## Usage

```php
<?php

require 'vendor/autoload.php';

use Pots\PhpBefunge\BefungeInterpreter;

$interpreter = new BefungeInterpreter();

echo $interpreter->interpret('64+"!dlroW ,olleH">:#,_@');
// Hello, World!
```

The same interpreter instance can run any number of programs; every call to
`interpret()` starts from a clean state.

### Program input

The Befunge-93 `&` (read integer) and `~` (read character) instructions read
from an injected `InputInterface`. If a program requests input and no source
was injected, an `InputRequiredException` is thrown.

```php
use Pots\PhpBefunge\BefungeInterpreter;
use Pots\PhpBefunge\Input\StringInput;

$interpreter = new BefungeInterpreter(new StringInput('42'));
echo $interpreter->interpret('&.@'); // 42
```

Provide your own implementation of `Pots\PhpBefunge\Input\InputInterface` to
read input from anywhere (HTTP request, queue, database, ...).

### Execution limit

To protect against infinite loops, the interpreter stops after a configurable
number of executed instructions (default: 1 000 000) and throws an
`ExecutionLimitExceededException`.

```php
new BefungeInterpreter(maxSteps: 10_000);
```

### Error handling

| Exception | Thrown when |
|---|---|
| `Pots\PhpBefunge\Exception\ExecutionLimitExceededException` | the program does not terminate within the step limit |
| `Pots\PhpBefunge\Exception\InputRequiredException` | `&`/`~` is executed without an injected input source |
| `Pots\PhpBefunge\Exception\InputExhaustedException` | the injected input source has no more data |

## Supported instructions

All Befunge-93 instructions are implemented: digits, `+ - * / %`, `! `` ` `,
directions `> < ^ v ?`, conditionals `_ |`, `"` string mode, `: \ $` stack
operations, `. ,` output, `#` trampoline, `g p` self-modification, `& ~` input
and `@` termination. Unknown characters are treated as no-ops, and popping an
empty stack yields `0`, as required by the specification.

## Testing

```bash
composer install
composer test     # PHPUnit test suite
composer analyse  # PHPStan, level 8
```

## Continuous integration

Every push and pull request runs the PHPUnit suite and PHPStan on PHP
8.1-8.4, plus a SonarQube analysis (with Clover coverage and a Quality Gate
check) via `.github/workflows/sonar.yml`.

The SonarQube workflow expects two repository secrets
(`Settings -> Secrets and variables -> Actions`):

| Secret | Value |
|---|---|
| `SONAR_TOKEN` | SonarCloud: a token from https://sonarcloud.io/account/security (organization must match `sonar.organization` in `sonar-project.properties`). Self-hosted: a user token with analysis rights. |
| `SONAR_HOST_URL` | `https://sonarcloud.io` for SonarCloud, or your server URL. |

A self-hosted SonarQube variant (service container instead of SonarCloud) is
included as a commented block in the workflow.

## License

MIT License. See [LICENSE](LICENSE) for details.

## Links

- https://esolangs.org/wiki/Befunge-93
- https://en.wikipedia.org/wiki/Befunge
