# @philiprehberger/env-validator

Schema-based environment variable validation with type-safe accessors.

## Installation

```bash
npm install @philiprehberger/env-validator
```

## Usage

```ts
import { createEnv } from '@philiprehberger/env-validator';

const env = createEnv({
  DATABASE_URL: { type: 'url', required: true },
  PORT: { type: 'port', default: 3000 },
  NODE_ENV: { type: 'enum', values: ['development', 'production', 'test'] },
  ENABLE_CACHE: { type: 'boolean', default: false },
  ADMIN_EMAIL: { type: 'email', required: true },
  MAX_CONNECTIONS: { type: 'number', default: 10 },
  FEATURE_FLAGS: { type: 'json', default: {} },
});

env.DATABASE_URL; // string (typed, validated)
env.PORT;         // number
env.ENABLE_CACHE; // boolean
```

### Supported Types

| Type | Parses to | Validation |
|------|-----------|------------|
| `string` | `string` | Any non-empty string |
| `number` | `number` | Valid number |
| `boolean` | `boolean` | true/false/1/0/yes/no |
| `url` | `string` | Valid URL |
| `email` | `string` | Valid email format |
| `enum` | `string` | One of specified `values` |
| `port` | `number` | Integer 0–65535 |
| `json` | `unknown` | Valid JSON |

### Error Reporting

All validation errors are collected and reported at once:

```ts
// If DATABASE_URL and ADMIN_EMAIL are both missing:
// EnvValidationError: Environment validation failed:
//   Missing required environment variable: DATABASE_URL
//   Missing required environment variable: ADMIN_EMAIL
```

### Custom Source

```ts
const env = createEnv(schema, {
  DATABASE_URL: 'postgres://localhost/mydb',
  PORT: '8080',
});
```


## Development

```bash
npm install
npm run build
npm test
```

## License

MIT
