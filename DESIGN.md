# Multi-Organization Platform Design

## System Architecture Overview

A Laravel-based multi-tenant platform with strict organization-level data isolation, role-based access control, and comprehensive contact management.

## Data Models

### Core Entities

```
Users
├── id (PK)
├── name, email, password
├── email_verified_at
└── timestamps

Organizations
├── id (PK)
├── name
├── slug (unique)
├── owner_user_id (FK)
└── timestamps

Organization_User (Pivot)
├── organization_id (FK)
├── user_id (FK)
├── role (Admin|Member)
└── timestamps

Contacts
├── id (PK)
├── organization_id (FK) *scoped*
├── first_name, last_name
├── email (nullable, unique per org)
├── phone, avatar_path
├── created_by, updated_by (FK)
└── timestamps

Contact_Notes
├── id (PK)
├── contact_id (FK)
├── user_id (FK)
├── body (text)
└── timestamps

Contact_Meta
├── id (PK)
├── contact_id (FK)
├── key, value
├── UNIQUE(contact_id, key)
└── timestamps (max 5 per contact)
```

## Data Scoping Strategy

### BelongsToOrganization Trait
- **Automatic Scoping**: All queries automatically filtered by current organization
- **Applied to**: Contacts, ContactNotes, ContactMeta
- **Implementation**: Global scope + relationship constraints

### Session-Based Organization Context
- **Middleware**: `SetCurrentOrganization`
- **Storage**: `session('current_organization_id')`
- **Switching**: POST `/organizations/{org}/switch`

## Authentication & Authorization

### Roles (spatie/laravel-permission)
- **Admin**: Full CRUD on all organization data
- **Member**: Read contacts, manage own notes

### Access Control
- **Organization-level**: User must belong to organization
- **Resource-level**: Automatic scoping via trait
- **Action-level**: Role-based permissions

## Route Structure

```
# Authentication
GET|POST /login, /register, /logout
GET|POST /email/verify

# Dashboard
GET /dashboard

# Organizations
GET /organizations (list user's orgs)
POST /organizations (create)
POST /organizations/{org}/switch

# Contacts (org-scoped)
GET /{org}/contacts
GET /{org}/contacts/create
POST /{org}/contacts
GET /{org}/contacts/{contact}
GET /{org}/contacts/{contact}/edit
PUT /{org}/contacts/{contact}
DELETE /{org}/contacts/{contact}
POST /{org}/contacts/{contact}/duplicate

# Contact Notes
POST /{org}/contacts/{contact}/notes
DELETE /{org}/contacts/{contact}/notes/{note}

# Health Check
GET /healthz
```

## Duplicate Email Flow

### Sequence Diagram
```
User → ContactController::store()
  ↓
Validation (unique:contacts,email,NULL,id,organization_id,{org_id})
  ↓
Duplicate Detected?
  ├─ YES → Log attempt
  │         ├─ API: Return 422 {"code": "DUPLICATE_EMAIL", "existing_contact_id": X}
  │         └─ Web: Redirect to existing contact + flash message
  └─ NO → Create contact normally
```

### Implementation Details
- **Validation Rule**: `unique:contacts,email,NULL,id,organization_id,{current_org_id}`
- **Logging**: `Log::info('Duplicate email attempt', [...])`
- **Response Format**: Exact 422 JSON payload as specified
- **Web Redirect**: `redirect()->route('contacts.show', $existing)->with('error', 'Duplicate detected...')`

## UI Architecture

### Layout Structure
```
app.blade.php
├── Navigation (org switcher)
├── Flash messages
└── @yield('content')

Dashboard
├── Organization stats
├── Recent contacts
└── Quick actions

Contacts Index
├── Search bar (name, email)
├── Create button
└── Contact cards (avatar, name, email)

Contact Show
├── Contact details + avatar
├── Edit/Delete actions
├── Notes section (CRUD)
├── Custom fields (up to 5)
└── Duplicate button
```

### Form Components
- **Contact Form**: Shared create/edit with avatar upload
- **Notes Form**: Inline add/edit with auto-save
- **Meta Fields**: Dynamic add/remove (5 max validation)
- **Organization Switcher**: Dropdown with POST form

## Security Considerations

### Data Isolation
- **Database Level**: Foreign key constraints
- **Application Level**: BelongsToOrganization trait
- **Session Level**: Organization context middleware

### File Upload Security
- **Avatar Storage**: `storage/app/public/avatars/`
- **Validation**: Image types, size limits
- **Access Control**: Public storage with proper naming

### CSRF Protection
- **All Forms**: `@csrf` tokens
- **AJAX Requests**: Meta tag + headers
- **Organization Switching**: POST-only with token

## Performance Optimizations

### Database
- **Indexes**: `organization_id` on all scoped tables
- **Eager Loading**: Contacts with notes/meta
- **Pagination**: 15 items per page

### Caching
- **Session**: Organization context
- **Views**: Blade template caching
- **Assets**: Vite compilation

## Testing Strategy

### Feature Tests
- **CrossOrgIsolationTest**: Verify data boundaries
- **DuplicateEmailTest**: Exact response validation
- **AuthTest**: Login/registration flows

### Test Data
- **Seeders**: Sample organizations, users, contacts
- **Factories**: Dynamic test data generation
- **Isolation**: Database transactions per test

## Technology Stack

- **Backend**: PHP 8.2, Laravel 12
- **Frontend**: Blade + Tailwind CSS
- **Database**: SQLite (dev), MySQL (prod)
- **Authentication**: Laravel Breeze
- **Permissions**: spatie/laravel-permission
- **Testing**: PHPUnit with Feature/Unit tests
- **Assets**: Vite for compilation

## Deployment Considerations

### Environment
- **Development**: SQLite + `php artisan serve`
- **Production**: MySQL + proper web server
- **Storage**: Symlinked public storage

### Configuration
- **Database**: Configurable via `.env`
- **File Storage**: Local disk (scalable to S3)
- **Session**: Database-backed for multi-server

## Trade-offs & Decisions

### SQLite vs MySQL
- **Chosen**: SQLite for development simplicity
- **Trade-off**: Production requires MySQL for performance
- **Mitigation**: Database-agnostic migrations

### Session vs JWT
- **Chosen**: Laravel sessions for simplicity
- **Trade-off**: Not suitable for API-first architecture
- **Benefit**: Built-in CSRF protection

### Trait vs Middleware Scoping
- **Chosen**: Model trait for automatic scoping
- **Trade-off**: Less explicit than middleware
- **Benefit**: Impossible to forget scoping

### Blade vs React
- **Chosen**: Blade templates for rapid development
- **Trade-off**: Less interactive than SPA
- **Benefit**: Server-side rendering, simpler deployment