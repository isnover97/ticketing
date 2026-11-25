# Helpdesk login

The expanded helpdesk workspace and setup details are documented in [HELPDESK-MODULES.md](HELPDESK-MODULES.md).

Start Apache and MySQL in XAMPP. Open http://localhost/IT-Ticketing-system/public/login (or `/login` on a virtual host pointing to `public`).

The configured MySQL database is `it_ticketing_system`. Existing Laravel migrations define users, password hashes, roles, sessions, categories, tickets, and comments. Apply any pending migrations with `php artisan migrate`. Never run `migrate:fresh` against existing data.

Create an account with `php artisan helpdesk:create-user`. The command prompts for name, email, role and a hidden password; passwords are hashed by the User model. Existing accounts remain usable. Demo credentials from the original database seeder are for local development only; do not run that seeder against existing data.

Login uses server-side sessions, CSRF protection, and five failed attempts per email/IP per minute. Logout invalidates the session. Client ticket lists and comments are restricted to their own tickets; staff can manage ticket status and assignment. Public request submission remains available and rate limited.

For hosting, point the document root to `public`, configure HTTPS, set `APP_DEBUG=false` and `SESSION_SECURE_COOKIE=true`, and use private database credentials in `.env`.

Run `php artisan test` to check authentication and access controls using an isolated in-memory SQLite database.

## Administrator user management

Sign in as an Administrator and select **User Management** in the sidebar, or open `/admin/users`. Create accounts with a name, email, role, password and selected module permissions. Use **Edit** to change permissions, reset a password or deactivate an account. Password resets and deactivation revoke database sessions; deactivation also prevents login. You cannot deactivate or demote your own administrator account.

Roles are displayed as Client, System Developer and Administrator. The existing database values `user`, `agent` and `admin` are preserved. Administrators always have full access, including user management. Clients remain restricted to their own tickets and cannot receive staff permissions. Existing accounts keep their role's default access until an administrator customizes it; selecting no permissions explicitly revokes module access. Analytics also enables ticket viewing because the report uses ticket data. Permissions are enforced by server routes as well as portal controls.

Public request submission remains available to guests. Account module permissions apply to authenticated portal use.
