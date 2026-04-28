# API Token Authentication — Design Study

## Problem

Storing API tokens as plaintext in the database is a critical security vulnerability. If the database is compromised (SQL injection, backup leak, insider threat), every token is immediately usable by the attacker. Unlike passwords, tokens are long-lived credentials that grant direct API access — the blast radius of a plaintext token leak is as wide as the permissions the token holds.

---

## Community Approaches

### Option A — Full token hashing (SHA-256)

Generate a random token, store `hash('sha256', $token)`, and look up by hash on every request.

```
DB row: token_hash = sha256(random_token)
Auth:   WHERE token_hash = sha256(provided_token)
```

**Pros**
- Simple — one column, one lookup
- Token never stored in plaintext

**Cons**
- Requires the full token string to compute the lookup key — no way to look up by a shorter identifier
- Lookup is a full column scan unless the hash itself is the primary key
- No way to distinguish between multiple tokens belonging to the same user without additional metadata

**Used by:** Many simple implementations, internal tooling

---

### Option B — Split token: identifier + hashed secret ✅ (our choice)

Split the token into two parts separated by a delimiter:

```
plain_token = {identifier}.{secret}
```

Store the `identifier` in plaintext (indexed, for fast lookup) and `hash('sha256', secret)` as the hashed secret. Never store the secret.

```
DB row: identifier = bin2hex(random_bytes(8))     → 16 hex chars, plaintext, unique index
        hashed_secret = hash('sha256', secret)    → 64 hex chars, never retrievable
Auth:   SELECT * WHERE identifier = ?
        THEN verify: hash_equals(hashed_secret, hash('sha256', provided_secret))
```

**Pros**
- O(1) indexed lookup by identifier — no full-table scan
- Secret is never stored or recoverable — database leak does not expose usable tokens
- Supports multiple tokens per user naturally (each has a unique identifier)
- Easy per-token revocation (delete by identifier)
- Constant-time comparison via `hash_equals()` prevents timing attacks
- Industry standard pattern

**Cons**
- Slightly more implementation complexity than Option A
- Token string is longer (identifier + delimiter + secret)

**Used by:** Laravel Sanctum, GitHub Personal Access Tokens, Stripe API keys

---

### Option C — HMAC with a server-side secret

Store `hash_hmac('sha256', token, APP_SECRET)` instead of a plain SHA-256 hash. Even if the database is fully compromised, the attacker also needs the application secret to forge or verify tokens.

```
DB row: token_hmac = hmac(sha256, token, APP_SECRET)
Auth:   recompute HMAC, compare with hash_equals()
```

**Pros**
- Double protection: attacker needs both the DB dump and the app secret
- Verification is fast (no DB lookup if using the token as a key)

**Cons**
- Rotating `APP_SECRET` invalidates every token immediately
- Adds operational complexity
- Overkill for most applications — if the server is compromised enough to read the DB, the app secret is likely also compromised

**Used by:** High-security systems, payment gateways

---

## Why we chose Option B

| Criterion | Full hash | Split token | HMAC |
|---|---|---|---|
| Lookup speed | Full scan or hash-as-key | Indexed | Depends on design |
| DB leak safety | ✅ | ✅ | ✅✅ |
| Multiple tokens/user | Needs extra column | ✅ Native | Needs extra column |
| Per-token revocation | ✅ | ✅ | ✅ |
| Secret rotation impact | None | None | Invalidates all tokens |
| Implementation complexity | Low | Medium | High |
| Community adoption | Moderate | **High** | Low |

The split token pattern hits the best balance: it is safe against database leaks, fast to look up, natively supports multiple tokens per user, and is the approach used by the most widely trusted frameworks and platforms.

---

## Hash algorithm choice: SHA-256, not bcrypt

Bcrypt and Argon2 are designed for **low-entropy** inputs (passwords chosen by humans). Their slowness is intentional — it makes brute-force dictionary attacks expensive.

API token secrets are generated with `random_bytes(32)` — 256 bits of cryptographic randomness. Brute-forcing this is computationally impossible regardless of the hash speed. Using bcrypt would add 100–300 ms of CPU time to **every authenticated API request** for zero additional security benefit.

SHA-256 is the correct choice for hashing high-entropy random values:
- Sub-millisecond computation
- Collision-resistant at 256-bit output
- Constant-time comparison via `hash_equals()` eliminates timing attacks

---

## Implementation

### Token generation (`ApiToken::__construct`)

```php
$this->identifier = bin2hex(random_bytes(8));   // 16 hex chars — stored plaintext
$secret           = bin2hex(random_bytes(32));  // 64 hex chars — never stored
$this->hashedSecret = hash('sha256', $secret);
$this->plainToken   = $this->identifier . self::DELIMITER . $secret;
```

The `plainToken` property is set only during construction and is the only moment the full token is available. It must be shown to the user immediately (returned in the API response and/or emailed) — it cannot be recovered afterward.

### Database schema

| Column | Type | Notes |
|---|---|---|
| `identifier` | `VARCHAR(16)` | Plaintext, unique index, used for lookup |
| `hashed_secret` | `VARCHAR(64)` | SHA-256 hex digest, never returned |

### Authentication flow (`ApiTokenAuthenticator::authenticate`)

```
1. Extract Bearer token from auth-token header
2. Split on DELIMITER → [identifier, secret]
3. SELECT * FROM api_token WHERE identifier = ?
4. If not found: reject (generic "Token not found" — no hint about which part failed)
5. If found: hash_equals(stored_hashed_secret, sha256(provided_secret))
6. If mismatch: reject with same generic message (timing-safe, no enumeration)
7. Otherwise: return associated User
```

**Error messages are intentionally generic.** Returning different messages for "identifier not found" vs "wrong secret" would allow an attacker to enumerate valid identifiers.

### Token delivery

Tokens are generated via `POST /api/v1/tokens` (requires `ROLE_ADMIN`). The plaintext token is:
- Returned once in the JSON response body (`plain_token` field)
- Emailed to the user's registered address

After the HTTP response is sent, the plaintext is unrecoverable. If lost, the user must generate a new token.

### Token revocation

Tokens do not expire automatically. To revoke a token:

```
DELETE /api/v1/tokens/{identifier}
auth-token: Bearer {identifier}.{secret}
```

The row is deleted. Any subsequent request using that token is rejected with 401. Attempting to revoke a token belonging to a different user returns 404 (not 403) to avoid identifier enumeration.

### Token usage

```
auth-token: Bearer {identifier}.{secret}
```

---

## Security properties

| Property | Status |
|---|---|
| Token not stored in plaintext | ✅ Only SHA-256 hash stored |
| Timing-safe comparison | ✅ `hash_equals()` used |
| Brute-force resistance | ✅ 256 bits of entropy in secret |
| No identifier enumeration | ✅ Generic error messages |
| Per-token revocation | ✅ `DELETE /api/v1/tokens/{identifier}` — deletes the row |
| No expiry (long-lived) | ✅ Tokens are permanent until explicitly revoked |
| Revocation enumeration-safe | ✅ Returns 404 for wrong-owner attempts, not 403 |
| Plaintext delivered securely | ✅ HTTPS response + email |
