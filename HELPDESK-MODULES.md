# PREI helpdesk workspace

Open <http://localhost/IT-Ticketing-system/public/> for the customer request form, or sign in at `/login`. Signed-in accounts are directed to a workspace allowed by their permissions. This is a Laravel implementation of common workflows described in the [osTicket feature list](https://osticket.com/features/) and [panel documentation](https://docs.osticket.com/en/latest/Admin%20Panel.html).

## Installed modules

| Module | Working functions |
| --- | --- |
| Tickets and dashboard | Open tickets, scoped queues, search by subject/number/requester, priority/status/department filters, mine/unassigned/overdue queues, pagination, bulk status changes, CSV export |
| Ticket conversation | Requester replies, staff replies, private staff notes, canned responses, audit history, private attachments with authorized downloads |
| Workflow | Individual/team assignment, department transfer, five-minute staff locks, linked-task closure checks |
| Users and access | Account creation/editing, password reset, deactivation, module permissions, inherited role profiles, assigned/team-only or department-only ticket visibility |
| Customer accounts | Registration, login/logout, email-token password recovery, access restricted to own tickets |
| Directory and organizations | Search staff/requesters, organization assignment in user management, email-domain organization matching for new requests |
| Departments and teams | Add/edit/disable departments, default team/SLA, team membership and team assignments |
| Help topics | Category/priority defaults, department/team/agent/SLA routing, linked custom forms |
| Ticket filters | Ordered contains/equals rules on subject, sender, priority or category; routing, priority changes, rejection and stop-on-match |
| SLAs and schedules | Elapsed-hour targets or business-hour targets; working weekdays, opening/closing hours, timezone, holiday exclusions; overdue queues and notices |
| Custom forms, fields and lists | Forms linked to topics, conditional fields, text/textarea/number/date/select types, required-field validation and reusable select lists |
| Tasks | Standalone or ticket-linked tasks, staff assignment, due dates and progress/closure |
| Knowledge base | Publish/unpublish articles, public search, categories and article pages |
| Canned responses | Reusable staff response text inserted into ticket replies |
| Saved queues | Shared status, priority, department, assignment and overdue filters |
| Reports | Date-filtered volume/status/priority counts, overdue counts and staff workload |
| Audit history | Staff-only ticket events and administrator account/configuration activity |
| Email and delivery | Encrypted SMTP settings, reply-to address, customizable acknowledgement/reply/overdue templates, outbox and delivery failures, retries, signed inbound email bridge |
| API keys | One-time token display, hashed token storage, read/create scopes, account permissions, source-IP restriction, expiry and revocation |

Administrators always have full access. System Developers keep the database role `agent`. Clients keep the role `user` and cannot receive staff permissions. Existing users with no customized permissions keep their role defaults. A role profile inherits changes immediately; disabling a profile removes its inherited permissions. Clients always see only their own tickets, regardless of staff visibility settings.

## Using the configuration

Create business schedules before SLA plans, then teams/departments, and finally help topics. Routing precedence is help-topic defaults, matching filters in creation order, then an explicit staff assignment when permitted. Department defaults fill missing team/SLA choices. SLA deadlines are calculated at ticket creation; changing an SLA or transferring a ticket does not retroactively rewrite existing deadlines.

Create a Custom List for reusable options. Create a Custom Form, add Custom Fields linked to that form, then select the form on a Help Topic. A field without a form or topic is global. Unknown submitted fields are discarded and required/selection constraints are validated on the server.

Use Role Profiles for reusable module grants. In User Management, select the profile and set staff visibility to all tickets, assigned-to-user/team tickets, or selected departments. A customer cannot elevate their role through registration.

Standard starter configuration is installed by `php artisan db:seed --class=HelpdeskDefaultsSeeder`. This is idempotent and does not create/replace user accounts or tickets. It installs an IT department, team, standard SLA targets, help topics for current categories, queues, canned responses and a getting-started article.

## Email setup

Go to **Email & Delivery** as an administrator. Enter the actual SMTP server/port, encryption, mailbox username/app password, sender and reply-to address. Passwords and inbound signing secrets are encrypted with the application's existing `APP_KEY`; keep that key backed up. Log mode does not send email. SMTP settings are not verified until delivery is attempted.

Set `HELPDESK_EMAIL_URL` to the helpdesk base URL that recipients can reach, including any deployment subdirectory. It defaults to `APP_URL`. Refresh Laravel's configuration cache after changing the value if the deployment uses cached configuration.

Run `php artisan helpdesk:send-mail` to deliver queued acknowledgements, replies, password reset links and overdue notifications. Failed deliveries are retried up to five attempts. The Laravel scheduler runs the command each minute; keep `php artisan schedule:work` running locally, or configure Windows Task Scheduler to run `php artisan schedule:run` once a minute. No background worker was installed or started automatically. In SMTP mode, running the mail worker sends pending messages to their recipients.

The inbound route is a provider-neutral bridge, not an IMAP/OAuth mailbox poller. Connect your email provider or a trusted mailbox forwarding service to:

`POST /api/mail/inbound`

Set an inbound signing secret of at least 32 random characters in Email & Delivery, or set `HELPDESK_INBOUND_SECRET` in `.env`. Send JSON with `message_id`, `from`, `subject`, and plain-text `body`. Optional fields are `name`, `ticket_number` for a reply, `category_id`, `help_topic_id`, `custom_fields` and `automatic`. Include:

- `X-Helpdesk-Timestamp`: current Unix timestamp in seconds.
- `X-Helpdesk-Signature`: lowercase hex HMAC-SHA256 of `timestamp + "." + exact_raw_JSON_body`, using the signing secret.

The signature must be current within five minutes. Message IDs are deduplicated. Replies must come from the ticket requester's email address. Explicit automatic messages are ignored to avoid responder loops. The bridge must authenticate the provider and pass verified sender addresses; knowing the signing secret authorizes submission on behalf of senders. Inbound attachments are not imported by this endpoint.

## Integration API

Administrators can create API keys under **API Keys**, binding each key to an active staff account. Copy the token once and send `Authorization: Bearer TOKEN`.

- `GET /integration/v1/tickets`: read scope; returns only tickets visible to the bound account.
- `POST /integration/v1/tickets`: create scope; accepts the same ticket fields as the customer/staff creation workflow. Supply requester email, subject as `title`, `description`, `category_id` and `priority`; optional help topic and custom fields are validated normally.

Keys are hashed in the database, checked for expiry/revocation and optional exact source IP, and still require the bound account's module permissions. Ordinary browser API endpoints continue to require sessions and CSRF tokens. Only signed inbound mail and bearer-key integration routes are exempt from CSRF.

## Maintenance and validation

Run `php artisan helpdesk:backup` for a private JSON snapshot of database rows in `storage/app/private/backups`. This is a data snapshot, not an automatic restore system or a backup of attachments/application encryption keys. Keep separate backups of `.env`, uploaded private files and a database schema dump.

All changes use additive migrations. Do not use `migrate:fresh` against your live database. Tests force an isolated in-memory SQLite database and refuse refresh operations against other databases. Run `php artisan test` for authentication, permissions, routing, forms/lists, SLA schedules, scoped visibility, locks, task closure, private notes/files, bulk atomicity, CSV safety, knowledge publication, email signatures, account recovery and API checks.

The project-root Apache `.htaccess` redirects to `public` and blocks direct HTTP access to application internals, `.env` and private storage when using XAMPP. Displayed timestamps use the application timezone shown in the footer; schedules calculate deadlines in their own configured timezone.

## Differences from osTicket

This is not an installation of osTicket or a claim of complete feature parity. The workspace implements the modules above using the existing Laravel application. Native osTicket plugins, IMAP/OAuth polling, multiple department-specific role combinations per agent, multi-ticket merge/referral workflows, per-user configurable queue columns, custom ticket status state machines, external task collaborators and multifactor authentication are not implemented. There is no automatic migration/import from an osTicket installation. SMTP delivery and provider forwarding require the real mailbox credentials/integration setup before live email can work.
