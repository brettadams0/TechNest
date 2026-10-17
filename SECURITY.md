# Security

TechNest is a portfolio project, not a store that takes money, but it is built as though it were
one. This page lists the controls in place, the threats they answer, and the ones it does not.

## Reporting a problem

Please open a [GitHub issue](https://github.com/brettadams0/TechNest/issues), or for anything
sensitive email adamsbrett00@gmail.com.

## Controls

| Threat | Control | Where |
|---|---|---|
| Cross-site request forgery | Synchronizer token per session, compared in constant time, checked in the kernel for **every** non-GET request | `Security/Csrf`, `Application::handle()` |
| Missing authorization on a new page | Guards are declared per route; tests enumerate the route table and require every `/admin` route to redirect guests and return 403 to customers | `Http/Route`, `tests/Http/SecurityTest` |
| Reading another customer's order (IDOR) | Orders are fetched by id **and** owner; a miss is a 404 so ids cannot be probed | `OrderRepository::findForUser()` |
| SQL injection | Prepared statements only, with typed binds; sort order chosen from a whitelist; `LIKE` wildcards escaped | `Database`, `ProductRepository` |
| Cross-site scripting | Every template value escaped with `View::e()`; a strict CSP blocks inline script and style; tests render pages with hostile product data | `View`, `SecurityHeaders`, `SecurityTest` |
| Clickjacking | `frame-ancestors 'none'` and `X-Frame-Options: DENY` | `SecurityHeaders` |
| Session fixation | Session id regenerated on login, logout and password change; strict mode; `HttpOnly`, `SameSite=Lax`, `Secure` over HTTPS | `NativeSession`, `Auth` |
| Spoofed client address or scheme | `X-Forwarded-For`/`-Proto` are ignored unless the peer is listed in `TRUSTED_PROXIES`; behind one, the client is the last address the trusted proxies did not add, so a forged header cannot pick its own throttle bucket | `Request::ip()`, `Request::isSecure()`, `IpRange` |
| Password guessing | 5 failures per account+IP per 15 minutes, stored in the database. Keyed on the account the login resolved to, so the username, the email and every spelling the database collation matches share one budget. Each attempt is reserved before the password is checked, so concurrent requests cannot all slip past the count. Confirming the current password before changing it draws on the same budget | `LoginThrottle`, `Auth` |
| Account enumeration | Same message for unknown user and wrong password; unknown users are checked against a dummy bcrypt hash at the runtime's default cost, so timing matches on every PHP version. Registration only says an email is taken once the username is free, and saying so is throttled per IP | `Auth::attempt()`, `AuthController::register()` |
| Stale access after a ban | The user is reloaded every request; a disabled account is signed out immediately | `Auth::user()` |
| A stolen session outliving a password change | Each account has a session version, bumped on password change; sessions holding an older one are signed out on their next request | `Auth::user()`, `UserRepository::changePassword()` |
| Open redirect after login | `?next=` and the stored intended URL accept same-site paths only | `SafeRedirect` |
| Price tampering | The session stores no prices; totals are recomputed from the database inside the checkout transaction; option ids are validated against the product | `Cart`, `CartPricer`, `OptionSelection` |
| Charging for a cart the customer did not review | The checkout form carries a fingerprint of the lines and prices it showed; a price change or a cart edited in another tab sends the customer back to the cart instead of placing the order | `PricedCart::fingerprint()`, `CheckoutService` |
| Overselling under concurrency | Stock is taken with an atomic conditional update inside the order transaction | `CheckoutService` |
| Information leakage | No PHP or Apache version headers; error detail only when `APP_DEBUG` is on; `/health` reports up/down only | `Dockerfile`, `Application`, `PageController` |
| Malicious uploads | There are no uploads; product images are chosen from files that ship with the store | `Admin\ProductController` |

Passwords are hashed with `password_hash()` (bcrypt), rehashed on login if PHP's default changes,
and limited to 72 bytes because bcrypt silently ignores anything longer.

## Credentials and the git history

Configuration comes from the environment; nothing secret has a default in the code. MySQL refuses to
start until `DB_NAME` and `DB_USER` are set, and says which are missing.

Earlier versions of this repository committed a real database name, user and password as fallback
values in `includes/config.php`. Those values remain in the git history. They must be treated as
compromised and rotated on whatever host they belonged to; removing them from the current tree does
not undo their publication.

The demo accounts (`admin` / `technest-admin`, `demo` / `technest-demo`) are published on purpose.
They are created only when `APP_ENV` is not `production`, and the CLI refuses to seed them otherwise.

## Known gaps

- Throttling is per account+IP: it slows targeted guessing, not a distributed attack across many
  accounts or IPs. There is no CAPTCHA.
- Registration still has to say when an email already has an account, since there is no mail
  service to send "you already have an account" to instead. That answer is limited to five per IP
  per 15 minutes.
- There is no email verification or password reset, because there is no mail service.
- Sessions use PHP's file storage, which suits a single server only.
