# Zod PHP Feature Checklist

This checklist tracks the implementation status of Zod features in the PHP version compared to the official [Zod JS library](https://github.com/colinhacks/zod).

## Core Schemas

- [x] `Z::string()`
- [x] `Z::number()`
- [x] `Z::boolean()`
- [x] `Z::any()`
- [x] `Z::unknown()`
- [x] `Z::never()`
- [x] `Z::null()`
- [x] `Z::literal()`
- [x] `Z::enum()` (Supports strings and PHP native Enums)
- [x] `Z::union()`
- [x] `Z::tuple()`
- [x] `Z::intersection()`
- [x] `Z::array()`
- [x] `Z::object()`
- [x] `Z::record()`
- [x] `Z::lazy()`
- [x] `Z::preprocess()`
- [x] `Z::bigint()`
- [x] `Z::date()`
- [ ] `Z::symbol()` (Likely N/A for PHP)
- [ ] `Z::undefined()` (PHP typically uses `null`)
- [ ] `Z::void()`
- [ ] `Z::nan()`
- [ ] `Z::map()`
- [ ] `Z::set()`
- [ ] `Z::promise()` (N/A)
- [ ] `Z::function()` (N/A)
- [ ] `Z::discriminatedUnion()`
- [ ] `Z::nativeEnum()` (Partially covered by `Z::enum()`)
- [ ] `Z::pipeline()` / `.pipe()`
- [ ] `Z::brand()`

## Common Methods (Base Schema)

- [x] `.parse()`
- [x] `.safeParse()`
- [x] `.optional()`
- [x] `.nullable()`
- [x] `.default()`
- [x] `.transform()`
- [x] `.preprocess()`
- [x] `.refine()`
- [ ] `.superRefine()`
- [ ] `.describe()`
- [ ] `.catch()`
- [x] `.or()` (Shorthand for union)
- [x] `.and()` (Shorthand for intersection)
- [ ] `.pipe()`

## String Validations

- [x] `.min()` / `.minLength()`
- [x] `.max()` / `.maxLength()`
- [x] `.length()`
- [x] `.email()`
- [x] `.regex()`
- [x] `.nonempty()`
- [x] `.url()`
- [ ] `.emoji()`
- [x] `.uuid()`
- [ ] `.nanoid()`
- [ ] `.cuid()` / `.cuid2()`
- [ ] `.ulid()`
- [ ] `.datetime()` (ISO 8601)
- [ ] `.ip()`
- [ ] `.cidr()`
- [x] `.startsWith()`
- [x] `.endsWith()`
- [x] `.includes()`
- [x] `.trim()`
- [x] `.toLowerCase()` / `.uppercase()`
- [x] `.toUpperCase()` / `.lowercase()`
- [ ] `.base64()`

## Number Validations

- [x] `.min()` / `.gte()`
- [x] `.max()` / `.lte()`
- [x] `.int()`
- [x] `.positive()`
- [x] `.nonnegative()`
- [x] `.negative()`
- [x] `.nonpositive()`
- [x] `.multipleOf()` / `.step()`
- [x] `.finite()`
- [x] `.gt()`
- [x] `.lt()`
- [ ] `.safe()`

## Object Methods

- [x] `.extend()`
- [x] `.passthrough()`
- [x] `.strict()`
- [x] `.strip()`
- [x] `.merge()`
- [x] `.pick()`
- [x] `.omit()`
- [x] `.partial()`
- [ ] `.deepPartial()`
- [x] `.required()`
- [ ] `.keyof()`

## Array Methods

- [x] `.min()` / `.minItems()`
- [x] `.max()` / `.maxItems()`
- [x] `.length()`
- [x] `.nonempty()`
- [ ] `.element` (Access element schema)

## Coercion (`Z::coerce()`)

- [x] `.string()`
- [x] `.number()`
- [x] `.boolean()`
- [ ] `.bigint()`
- [ ] `.date()`

## JSON Schema Features

- [x] `Z::jsonSchema()` (Export to JSON Schema)
- [ ] `Z::fromJSONSchema()` (Convert JSON Schema to Zod)
- [ ] Support for JSON Schema `target` (Draft 4, 7, 2020-12, OpenAPI 3.0)
- [ ] Metadata / Registry support (title, description, etc.)
- [ ] Custom `override` logic for conversion
- [ ] Cyclic schema support via `$ref`
- [ ] Reused schema extraction via `$defs`
- [ ] `io` mode (Input vs Output schema conversion)
- [ ] Unrepresentable type handling (`throw` vs `any`)

## Extra Features (PHP Specific)

- [x] `Z::jsonSchema()` (Base implementation)
