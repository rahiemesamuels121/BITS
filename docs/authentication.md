# User accounts and login

The existing `User` model stores names, unique email addresses, hashed passwords, remember tokens, and an optional role. The original users migration creates the account tables; `2026_11_05_000001_add_role_id_to_users_table` adds the role foreign key without changing existing accounts.

## Database setup

```sh
php artisan migrate
php artisan db:seed --class=RoleSeeder
```

## Create an account

```sh
php artisan user:create
```

The command asks for a name, email address, Agency or Admin role, and a password with confirmation. Password input is hidden and requires at least 12 characters. The command hashes passwords, rejects duplicate emails, and never overwrites existing accounts. It does not accept passwords as command-line arguments.

You can optionally provide non-secret account fields:

```sh
php artisan user:create --name="Account Owner" --email="owner@example.com" --role=admin
```

Choose `agency` or `admin` for the role code. The default database seeder creates role codes only; it does not provision accounts with shared credentials.

## Sign in

The Client Login button opens `/login`. Successful sign-in opens `/dashboard` or the previously requested protected page. Guests are redirected from the dashboard to login. Signed-in users are redirected from login to the dashboard.

Sign-in includes validation, remember-me, failed-attempt rate limiting, and session regeneration. Sign out from the dashboard sidebar to invalidate the session. Inactive accounts cannot sign in. The infrastructure displayed on the dashboard remains sample data.

## Manage clients

Signed-in, active Admin accounts have a Clients navigation item at `/dashboard/clients`. Admins can create client accounts with a name, unique email, confirmed password (12–72 characters), and active/inactive status. Clients use the existing Agency role; submitted role values cannot grant administrator access. The client list supports name/email search, status filtering, pagination, and Activate/Deactivate actions. Other accounts cannot access these routes, including by directly posting requests.

The `2026_11_06_000000_add_is_active_to_users_table` migration adds an account-status flag and preserves existing accounts as active. Deactivation rotates the remember token and deletes database-backed sessions. Web middleware logs out inactive accounts on their next request, including when another session driver is used; JSON requests receive HTTP 401. Activation restores login with the existing password. Forms and responses are retained. Admin accounts cannot be deactivated through the client endpoints.

Clients appear in a responsive table with name/email search, account-status filtering, pagination, Add new client, Edit, Activate/Deactivate, and Remove actions. Editing supports name, unique email, and an optional confirmed replacement password. Changes to email/password revoke sessions and remember tokens and clear old password reset tokens. A blank password retains the existing hash. Email changes clear email verification. Client roles cannot be changed through these endpoints.

Remove asks for confirmation and soft-deletes the account using the `2026_11_06_000001_add_soft_deletes_to_users_table` migration. The account is hidden from the table and cannot authenticate or be edited/reactivated. Existing forms and responses remain stored; the email remains reserved. Removal also deactivates the account and revokes sessions, remember tokens, and password reset tokens. Administrator accounts are excluded from every client mutation endpoint.
