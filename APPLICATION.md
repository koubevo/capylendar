# Capylendar application contract

This is the internal, authoritative description of Capylendar's current product
and business behavior. It is written for maintainers and coding agents. It is
not a marketing page, a public user guide, or a Laravel installation guide.

## Product scope and terminology

Capylendar is a private collaboration application for two people. It is not a
general team SaaS, and new logic MUST NOT assume arbitrary team membership or
team administration.

Pink and Blue identify the two people. Yellow identifies both people together.
The `capybara` value is a visual and product assignment, not an access-control
mechanism. A Pink, Blue, or Yellow event/todo can be either private or shared.
Visibility is determined by subscriber membership and `is_private`, never by
the capybara color. The capybara assignment does select recipients for new event
and todo notifications; this affects notification targeting, not access.

The authenticated user who creates an event or todo is its stored author. On
creation, a non-private item is assigned to all users and a private item is
assigned only to its author. Event and todo policies are subscriber-based: a
subscriber can view, edit, delete, restore, and (for todos) finish or postpone
the item. A non-subscriber MUST NOT gain access by guessing a URL.

When an authorized user edits the privacy setting, the current implementation
re-syncs subscribers using the user submitting the edit: private means that
editing user only, while non-private means all users. This is a current edge of
the subscriber contract and MUST remain explicit; capybara color does not alter
it.

## Document roles

The landing page is a public, concise product presentation and may use examples
or Czech copy. `readme.md` is public project documentation covering capabilities,
setup, operations, and distribution. This document is the internal behavioral
contract for agents. The landing page and README MUST NOT claim behavior that
contradicts this contract, and neither one replaces it.

## Events

A timed event has a start time and may have
an end time on the same date; an end time MUST be after the start. An all-day
event is shown as a whole-day item and has no end time.

Upcoming events begin with today and are ordered by start time ascending, then
all-day status, then title. Historical events are events before today and are
ordered newest first. The future dashboard and event history are separate
views. History is loaded in pages of 20 through Inertia infinite loading; it is
not part of the future dashboard's month stream.

A future event may have countdown_enabled; the default is false. Its countdown
targets start_at, while an all-day countdown targets the beginning of its
calendar day in the application timezone. Day labels use calendar-day
differences (dnes, zítra, then a day count), not elapsed 24-hour periods. Countdown labels never switch to hour or minute precision.
A countdown remains active through the event's entire calendar day, regardless
of whether the event is timed or all-day. The stored flag is preserved
afterward for historical truth, but elapsed events are omitted from active
countdown surfaces. Remaining time is always derived at read time and is never
stored. Countdown visibility follows event subscriber authorization on every
web, phone, and watch surface. A removed or inaccessible manually selected
event must fall back to an empty configuration state.

Events may have shared color tags. A Google Maps URL in the description can be
resolved into a map OpenGraph preview when a title and image are available. If
the remote lookup fails, the event remains valid without a preview. The stored
description remains the source content; the UI can hide the extracted map URL
when showing the human description.

Event images are private media. The image URL is an authenticated route and is
authorized with the same subscriber policy as the event. Missing media returns
not found. Creating or updating an event must not leave a newly uploaded image
behind when its database transaction fails, and an old image is removed only
after a successful replacement/removal.

Creating and editing an event redirect to the dashboard with a target date and
event highlight. Deleting is a soft delete. Subscribers can view the event
trash and restore a deleted event. Creating a non-private event sends a deferred
Web Push notification after the creation transaction commits. Pink or Blue
targets a subscriber with the matching capybara; Yellow targets all other
subscribers. The author is always excluded, and recipients must have
notifications enabled and an active push subscription. Editing, deletion, restore, and sharing
do not send push notifications.

## Todos

Priorities are High, Medium, and Low. Unfinished lists sort by deadline
ascending, then priority (High before Medium before Low), then title. Finished
lists sort by deadline descending with the same tie breakers. The dashboard
contains only unfinished todos; the dedicated todo page has separate unfinished
and finished views.

Todo completion is intentionally optimistic and MUST follow this contract:

- The card changes to checked/unchecked immediately and stays visible on the
  current page until a full page refresh. This lets the user undo an accidental
  tap on the same page.
- The web UI MUST use a silent same-origin `fetch` with the Laravel
  `XSRF-TOKEN` cookie/token, `credentials: same-origin`,
  `X-Requested-With: XMLHttpRequest`, and `Accept: application/json`.
- The background request returns `204 No Content` on success.
- A network error or non-success response MUST toggle the local state back so
  the UI does not drift from the server.
- When new Inertia props arrive, the dashboard MUST merge them with locally
  toggled todos and preserve those local states. A completed todo can therefore
  remain visible even when the refreshed unfinished query no longer contains it.

The same toggle is available from the dashboard, todo lists, and todo detail.
The server stores completion in `finished_at`; no completion push notification
is sent. After a full refresh, completed items leave unfinished dashboard lists
and appear in the finished todo view.

The postpone action moves one todo's deadline forward by one day. The bulk
postpone action moves every unfinished todo assigned to the current user on a
selected date forward by one day; finished todos and other dates are untouched.
Both actions redirect to the relevant dashboard date, and mutation redirects
may highlight the affected todo. Todos use the same subscriber-based privacy,
authorization, soft-delete, trash, and restore rules as events. Creating a
non-private todo sends the same deferred notification after the creation transaction
commits. Pink and Blue target subscribers with the matching capybara, while Yellow
targets all other subscribers; the author is always excluded. Update, finish,
postpone, delete, and restore mutations do not send push notifications.

## Dashboard

The dashboard starts at today. Its first
month includes events from today onward and also includes every unfinished todo
whose deadline is already overdue. Later content is loaded one calendar month
at a time. The stream extends far enough to include the latest future event or
unfinished todo, and always has at least the current month.

The dashboard has three views: a combined event/todo view, an event-only view,
and a todo-only view. Search filters title and description. Other filters select
the capybara assignment and one or more shared tags. Filters apply to both
events and todos in the dashboard and to the separate event-history view.

Create, edit, and postpone redirects may carry a date and an item ID. The
dashboard loads the required month, scrolls to the date or item, highlights the
item, and then removes the transient URL parameters. This redirect behavior is
intentional. Event history remains separate from the future dashboard and uses
its own infinite loading.

## Chat, documents, and tags

### Chat

Chat is shared between authenticated users. Messages are owned for attribution
by the sending user, are limited to 1,000 characters, and are displayed in
chronological order. There is currently no per-message privacy, edit, or delete
workflow. Text is rendered as text with detected links; the UI does not inject
raw HTML.

After a message is stored and the HTTP response is ready, the application sends
a Web Push notification to other users who have notifications enabled and an
active push subscription. The sender is not notified. This notification is
deferred work after the response, not a queued job, so a queue worker is not
required for current chat notifications.

### Documents

Documents are shared pair data. The author is recorded and displayed for
attribution, but the current authenticated routes do not restrict documents to
their author: either authenticated user can view, create, edit, or permanently
delete a document. Documents have no trash/restore lifecycle. Creating a
document sends a deferred Web Push notification to every other user who has
notifications enabled and an active push subscription. Document updates and
deletions do not send notifications.

The editor stores Markdown-like plain text. The current renderer intentionally
supports headings up to level three, unordered lists, separators, tables,
`**bold**` inline text, and detected links. It renders through Vue text and
elements rather than raw HTML, so document content cannot inject markup. Bare
links are opened as HTTPS links; external links use a new tab with
`noopener noreferrer`. Unsupported Markdown is shown as text, not interpreted
as arbitrary HTML.

### Tags

Tags are shared global records for the pair, with a label and color. An
authenticated user can create or delete tags and assign them to events and
todos. Tags are used in forms, cards, and search filters; tag color does not
change privacy or authorization.

## Notifications and PWA behavior

Each browser/device push subscription belongs to one user. Saving a valid
subscription enables that user's global notification switch. Disabling the
switch deletes that user's stored push subscriptions. The frontend reconciles
the browser subscription with the server when authentication, visibility, or
connectivity changes; permission or unsupported-browser failures do not grant a
subscription.

Created-item notifications are scheduled only after a successful create operation.
Event and todo recipients come from the committed subscriber set and always exclude
the author. Pink and Blue notify only the matching capybara; Yellow notifies all
other subscribers. Private item details therefore cannot leak. Document
notifications go to other users because documents are shared pair data. Payloads include the title, author,
relevant date or deadline, and a same-origin deep link, but never descriptions or
images. These notifications use post-response deferred work without a queue worker,
and a push failure does not fail or repeat item creation.

The morning summary covers today's events. The evening summary covers
tomorrow's events; when tomorrow is empty but a later event exists, it reports
the next event instead. No daily notification is sent when there is no relevant
event. Recipients must have both the global switch enabled and at least one push
subscription, and event visibility still follows subscriber access.

An external wake caller may request `morning` or `evening` with the configured
Bearer token. Each notification type is idempotent for a calendar day. A
failed send releases the idempotency key so a later retry can run. The endpoint
is rate-limited and MUST NOT be treated as an unauthenticated public trigger.

The service worker handles push clicks. It accepts only same-origin target URLs;
an invalid or external target falls back to the current origin. It may focus an
existing same-origin window or open a new one. Push navigation MUST NOT become
an open redirect.

## Wear OS

The Wear OS client is a separate application that communicates directly with
the Capylendar server; a phone companion is not required. It reads the user's
assigned upcoming events and unfinished todos and can update todo completion.

Pairing starts on the watch with a short-lived eight-character code. The user
approves the code in the authenticated web app, and the watch polls until it
can claim a bearer token. The pairing expires after a short window, and a
repeated claim of an already approved pairing is idempotent so a transient
network failure does not create another device. The server authenticates the
active device token by its hash. The watch stores its clear token encrypted in
the Android Keystore.

Users can revoke a device from the web settings. A revoked token stops
authorizing Wear OS API calls, and the watch must return to pairing. Wear OS
todo completion is optimistic; the watch updates immediately and rolls back on
server or connectivity failure. The watch retries transient pairing failures
but must not bypass expiration or authorization errors.

The Wear OS app is distributed by local debug-APK sideload. Play Store
distribution and Play Store signing are not part of the current product
contract.

## Security and operations

Guests can see the public landing page only. Application data requires web
authentication, and event/todo access requires subscriber membership. Private
event media uses the same authorization boundary; copying a URL must not make
it public. Web Push endpoints are validated as supported public HTTPS provider
URLs. Registration is not possible.

The deployed/local application uses PostgreSQL through Sail. Tests use
`RefreshDatabase` with an isolated in-memory SQLite database and MUST NOT use a
production database. Telescope is not a runtime dependency. Current daily and
chat notifications do not require a queue worker: wake sends synchronously and
chat uses post-response deferred work.

All PHP, Composer, Artisan, and Node commands for the web project MUST run
through `vendor/bin/sail`. The Wear OS project uses its checked-in Gradle
wrapper. Operational setup belongs in `readme.md`; this document records the
behavior agents must preserve.

App is hosted on Laravel Cloud.
