# BITS dynamic forms and data collection

## Open the module

Sign in through **Client Login**, then choose **Form Management** in the dashboard sidebar, or open `/dashboard/forms`.

The module stores real forms and responses in the existing configured database. The older systems-inventory dashboard remains illustrative; its sample data does not feed the forms module. There are no new PHP or production JavaScript dependencies. Happy DOM is a development-only dependency for testing builder interactions against the rendered Blade page.

## Supported workflow

1. Create a form with a title and description.
2. Click toolbox controls or drag them into the canvas. The Add Another Question button inserts a Short Text question directly. Adding a question opens and focuses its properties; on smaller screens the Properties panel is shown automatically. Click a question's Edit button to update its label, help text, options, and validation. Drag questions to reorder them, or use the accessible Up/Down buttons. Options have their own reorder controls.
3. Configure required answers, defaults, validation limits, upload restrictions, custom messages, and conditional visibility.
4. Open Form Settings for response limits, anonymity/authentication, deadlines, appearance, confirmation text, and the submit label.
5. Save Draft. Unsaved changes are indicated, and leaving the builder triggers a browser warning. Concurrent edits are protected by a revision check.
6. Preview the current unsaved editor state, or use the saved-draft preview route. Both support desktop/mobile framing and never save responses.
7. Publish, then copy or open the public link. Publishing automatically saves the current editor state first.
8. Collect responses through the published URL. Respondents see server-side validation errors and the configured confirmation message.
9. Inspect, search, date-filter, version-filter, paginate, delete, or export responses. Open Analytics for actual daily counts, numeric statistics, and option distributions.

Supported types: short text, paragraph, number, email, phone, date, time, dropdown, radio buttons, checkboxes, yes/no, private file upload, section heading, and instruction text.

There is no application limit on the number of forms. Individual forms allow up to 200 fields, with up to 100 options per choice field. These limits bound validation, rendering, and request size.

## Storage and relationships

- `forms`: belongs to a User; owns draft fields, versions, and submissions. Stores a stable unique slug, status, draft settings, current published version, revision number, and soft-delete timestamp.
- `form_fields`: belongs to a Form. `(form_id, field_key)` is unique. Stores field metadata and order, with JSON options, constraints, defaults, and conditions.
- `form_versions`: belongs to a Form and publishing User. `(form_id, version_number)` is unique. Published schema/settings snapshots cannot be updated through the model. The form points to its currently published version.
- `form_submissions`: belongs to a Form, its exact FormVersion, and optionally a respondent User. All answer types and all forms share the same JSON `data` column. Indexes cover form/date and form/user queries.
- `User::forms()` provides the ownership relationship. No organization table exists in this application, so no organization or tenant column was introduced.

No form-specific database or table is created. Deleting a form soft-deletes its definition and disables its URL while retaining historical records. Deleting a response permanently removes that response and its private attachments.

## Versioning and conditions

Draft edits do not change published forms. A publish action reuses the current version if its canonical definition is unchanged, including when MySQL reorders JSON object keys. Changed definitions create a new snapshot. Question and option ordering remains significant.

Submissions from stale browser tabs are rejected if the form was republished; respondents must refresh instead of submitting against a mismatched schema. Historical response details use their original schema. Cross-version tables/exports union stable question keys and show a dash for missing answers.

Conditional rules use this structured format:

```json
{"field":"employed","operator":"equals","value":"Yes"}
```

Operators: `equals`, `not_equals`, and `contains`. Contains supports text/checkbox answers. Conditions must refer to an earlier answer field; this prevents cycles. Missing or hidden controlling answers keep dependent questions hidden. Hidden answers are discarded on the server even if a client submits them. No expression or arbitrary code evaluation is used.

## Security and operations

- All management routes require the existing session authentication.
- FormPolicy restricts management, response access, export, and downloads to the owner. Admin and Agency accounts do not automatically gain access to another owner's forms.
- Laravel Form Requests validate configuration and submission envelopes. Dynamic server-side validation comes from the published schema, with typed normalization and strict allowed options/keys.
- Public submission POSTs use the existing web/CSRF middleware, a honeypot, and a named limit of 20 requests/minute per form and IP.
- Form locks serialize publication, quotas, and per-authenticated-user duplicate checks. Draft revision checks prevent silent overwrites from multiple editors.
- Private uploads use the existing `local` disk under `storage/app/private/forms/{form_id}/`, with generated filenames. JSON stores references, names, MIME types, and sizes; files are never exposed as public storage links.
- Upload extensions are restricted to PDF, JPG/JPEG, PNG, WebP, TXT, CSV, DOCX, and XLSX. Server MIME and size checks also apply. Executable files, HTML, and SVG are not supported.
- Per-field upload limits support 1–10,240 KB and default to 2,048 KB. PHP/web-server request limits may be lower; configure `upload_max_filesize`, `post_max_size`, and web-server limits to match intended uploads. The module does not change host PHP settings.
- Text and labels are escaped in Blade and rendered with safe DOM text APIs. Optional form logos use HTTPS image URLs and suppress referrers; there is no server-side URL fetch.
- CSV export streams responses in batches, respects filters, and neutralizes spreadsheet formula prefixes. No Excel package is installed, so export is CSV, which Excel can open; native XLSX export was not added.
- Analytics use SQL aggregation rather than loading all submissions. No completion rate or untracked metric is invented. Dates/deadlines in management and reports are labeled UTC.

## Routes

All management route names begin with `forms.`:

| Method | URI | Name suffix |
| --- | --- | --- |
| GET | `/dashboard/forms` | `index` |
| GET | `/dashboard/forms/create` | `create` |
| POST | `/dashboard/forms` | `store` |
| GET | `/dashboard/forms/{form}/edit` | `edit` |
| GET | `/dashboard/forms/{form}/settings` | `settings` |
| PUT | `/dashboard/forms/{form}` | `update` |
| DELETE | `/dashboard/forms/{form}` | `destroy` |
| POST | `/dashboard/forms/{form}/duplicate` | `duplicate` |
| POST | `/dashboard/forms/{form}/publish` | `publish` |
| POST | `/dashboard/forms/{form}/unpublish` | `unpublish` |
| POST | `/dashboard/forms/{form}/archive` | `archive` |
| GET | `/dashboard/forms/{form}/preview` | `preview` |
| GET | `/dashboard/forms/{form}/analytics` | `analytics` |
| GET | `/dashboard/forms/{form}/submissions` | `submissions.index` |
| GET | `/dashboard/forms/{form}/submissions/{submission}` | `submissions.show` |
| DELETE | `/dashboard/forms/{form}/submissions/{submission}` | `submissions.destroy` |
| GET | `/dashboard/forms/{form}/submissions/{submission}/files/{field}` | `submissions.file` |
| GET | `/dashboard/forms/{form}/export` | `export` |

Public routes:

- `GET /forms/public/{slug}` — `public-forms.show`
- `POST /forms/public/{slug}/submit` — `public-forms.submit`

## Created files

- Migration: `database/migrations/2026_11_05_000002_create_form_builder_tables.php`
- Models: `app/Models/Form.php`, `FormField.php`, `FormVersion.php`, `FormSubmission.php`
- Policy: `app/Policies/FormPolicy.php`
- Requests: `app/Http/Requests/Forms/CreateFormRequest.php`, `SaveFormRequest.php`, `PublicSubmissionRequest.php`
- Services under `app/Services/Forms/`: `FormSchemaService.php`, `FormConditionService.php`, `FormValidationService.php`, `FormService.php`, `FormPublishingService.php`, `FormSubmissionService.php`, `FormReportService.php`
- Controllers under `app/Http/Controllers/Forms/`: `FormController.php`, `FormBuilderController.php`, `FormPublicationController.php`, `PublicFormController.php`, `FormSubmissionController.php`, `FormAnalyticsController.php`
- Component: `app/View/Components/FormRenderer.php` and `resources/views/components/form-renderer.blade.php`
- Views under `resources/views/forms/`: `index`, `create`, `builder`, `preview`, `public`, `confirmation`, `unavailable`, `submissions`, `submission`, `analytics`, and `partials/nav`, `partials/pagination` (all `.blade.php`)
- Public layout: `resources/views/layouts/form.blade.php`
- Frontend: `resources/css/forms.css`, `resources/js/forms.js`, `resources/js/form-builder.js`, `resources/js/form-renderer.js`
- Routes: `routes/forms.php`
- Tests: `tests/Feature/FormBuilderTest.php`, `tests/js/form-conditions.test.mjs`, `tests/js/form-builder-interactions.test.mjs`, `tests/Support/render-form-builder.php`, `tests/manual/form-builder-mysql-smoke.php`
- This implementation guide: `docs/form-builder.md`

## Modified files

- `app/Models/User.php`: forms relationship
- `app/Providers/AppServiceProvider.php`: policy registration and public submission limiter
- `routes/web.php`: loads module routes
- `resources/views/layouts/admin.blade.php`: management navigation, live module labeling, CSRF metadata, validation feedback, and confirmation dialog
- `resources/js/app.js`: loads module JavaScript
- `resources/css/app.css`: imports module styles
- `package.json`, `package-lock.json`: development-only Happy DOM dependency and `test:forms` script

## Deployment and verification commands

```sh
php artisan migrate
npm ci
npm run build
php artisan test --compact
npm run test:forms
```

While `npm run dev` is running, run `npm run test:assets` to verify that the actual JavaScript modules allow browser requests from both `http://localhost:8000` and `http://127.0.0.1:8000`. Vite requires explicit `server.cors` origins here: setting only `server.origin` causes the Laravel plugin to allow the asset origin instead of the application origin, so browsers block the scripts and question buttons remain inactive. Direct HTTP CRUD and DOM tests do not enforce this browser check.

On a local/testing MySQL connection, the following optional check verifies actual MySQL JSON persistence, analytics, version reuse/history, and duplication inside an outer transaction. Its temporary users, forms, and responses are rolled back:

```sh
php tests/manual/form-builder-mysql-smoke.php
```

The four-table migration was applied to the configured MySQL database during implementation. The complete PHP suite passes 44 tests (including the 24 new form-module tests), and all 4 JavaScript tests pass. Automated PHP integration tests cover the end-to-end farmer-registration workflow, all field types, drafts/settings, concurrent draft revisions, publishing, immutable history, private uploads/downloads, ownership boundaries, duplication, CSV filtering/formula safety, condition validation, deadlines/quotas, and public rate limiting. The DOM interaction test renders the real Blade template against an isolated in-memory database and loads the complete application JavaScript. It covers all 14 toolbox types, Add Another Question, label edits, preview/settings, and save payloads. PHP must be available on PATH; set `PHP_BINARY` to its executable path if needed. The rollback-only MySQL smoke check also passed. The production Vite build passes.

Browser automation is unavailable in this environment, so visual/responsive behavior and native drag-and-drop have not been verified in a live browser. Responsive styles, accessible field labels, keyboard selection, and explicit Up/Down controls are implemented. No separate analytics/chart package or spreadsheet package was added.
