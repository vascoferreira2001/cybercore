# Fiscal Data Change Management System - Implementation Summary

## Overview
A complete secure fiscal data change management system with approval workflow, audit trail, and role-based permissions has been implemented in the CyberCore platform.

## System Architecture

### Database Layer
- **New Table**: `fiscal_change_requests` in `sql/schema.sql` (lines 212-235)
- **Columns**:
  - Audit trail: `old_nif`, `new_nif`, `old_entity_type`, `new_entity_type`, `old_company_name`, `new_company_name`
  - Workflow: `status` (pending/approved/rejected)
  - Approval tracking: `reviewed_by`, `reviewed_at`, `decision_reason`
  - Timestamps: `requested_at`, `created_at`
- **Indexes**: user_id, status, requested_at, reviewed_by for optimal queries
- **Relationships**: Foreign keys to users table with CASCADE/SET NULL policies

### Business Logic Layer
- **File**: `inc/fiscal_requests.php` (240 lines)
- **Core Functions**:
  - `isValidNIF($nif)`: Validates Portuguese NIF with mod-11 checksum algorithm
  - `submitFiscalChangeRequest()`: Submits change request with validation, duplicate prevention, and logging
  - `approveFiscalChangeRequest()`: Updates user fiscal data upon approval, logs decision
  - `rejectFiscalChangeRequest()`: Rejects request with decision reason, logs rejection
  - `getUserFiscalRequests()`: Retrieves request history for individual users
  - `getPendingFiscalRequests()`: Retrieves pending requests with user details for approval page

### API Endpoint Layer
- **File**: `inc/api/fiscal-requests.php` (126 lines)
- **Endpoint**: POST `/inc/api/fiscal-requests.php`
- **Security**: CSRF token validation, session validation, role-based access control
- **Actions**:
  - `submit`: Client submits fiscal change request
  - `approve`: Manager/Financial Support approves change
  - `reject`: Manager/Financial Support rejects change with reason
  - `getHistory`: Client retrieves their request history
  - `getPending`: Manager/Financial Support gets all pending requests
- **Response Format**: JSON with success/message and optional data payload
- **HTTP Status Codes**: 201 for created, 400 for validation errors, 403 for permission denied

### Frontend Layer

#### User Profile Page (`profile.php`)
- **Fiscal Data Display**:
  - Read-only input fields for NIF, Entity Type, Company Name
  - Field-lock indicator showing data is protected
  - Info banner explaining the change request process
- **Client Interface**:
  - "Request Fiscal Change" button opens modal form
  - Modal form with fields for: New NIF, New Entity Type, New Company Name (conditional), Reason
  - Client-side validation with error messages
  - Toast notifications for success/failure
  - Fiscal request history display with status badges and timestamps
- **Manager/Financial Support Interface**:
  - Direct link to "/admin/fiscal-approvals.php" for approvals page
  - View-only fiscal data (no change requests available)

#### Modal Form (`profile.php`, lines 303-361)
- **Form Fields**:
  - New NIF (required, validated on submit)
  - New Entity Type (dropdown: Singular/Coletiva, required)
  - New Company Name (required if type is Coletiva)
  - Reason (required, minimum 10 characters)
- **Validation**: Client-side for UX, server-side for security
- **Submission**: POST to `/inc/api/fiscal-requests.php` with CSRF token
- **Feedback**: Toast notifications, auto-reload on success

#### Admin Approval Page (`admin/fiscal-approvals.php`)
- **Access Control**: Restricted to Gestor and Suporte Financeiro roles
- **Table Display**:
  - Client name and email
  - Current fiscal data (NIF, Type, Company Name)
  - Requested fiscal data (highlighted in yellow for clarity)
  - Change reason (truncated to 100 chars with ellipsis)
  - Request date/time
  - Action buttons: Approve, Reject
- **Approve Flow**: Confirmation dialog, updates user fiscal data, logs approval
- **Reject Flow**: Opens modal with required rejection reason field (minimum 10 chars), logs rejection
- **Toast Notifications**: Success/error feedback for all actions
- **Auto-Refresh**: Page reloads after approval/rejection

### Security Implementation

1. **Data Immutability**: Fiscal fields (NIF, Entity Type, Company Name) are permanently read-only in profile
2. **Permission Enforcement**:
   - Clients can only submit requests (checked at API and business logic layers)
   - Manager/Financial Support can only approve/reject (not submit)
   - Proper role validation at every step
3. **CSRF Protection**: All API calls validated with CSRF tokens
4. **Input Validation**:
   - NIF: Portuguese algorithm with mod-11 checksum
   - Entity Type: Enum restricted to valid values
   - Company Name: Required validation for Coletiva type
   - Reason: Minimum 10 characters required
5. **SQL Injection Prevention**: All database queries use prepared statements
6. **Rate Limiting**: Only one pending request per user at a time
7. **Audit Trail**: Complete history of all changes with:
   - Old and new values
   - Requestor user_id and timestamp
   - Reviewer user_id, timestamp, and decision reason
   - All logged to both fiscal_change_requests table and logs table

## User Workflows

### Client Workflow
1. Navigate to Profile page
2. See fiscal data is read-only with protection note
3. Click "Request Fiscal Change" button
4. Fill modal form with new values and reason
5. Submit form
6. See success notification
7. Request appears in "Histórico de Solicitações" section with "pending" status
8. Later, see status change to "approved" or "rejected"
9. If approved, fiscal data updates automatically
10. If rejected, see rejection reason (if provided)

### Manager/Financial Support Workflow
1. Navigate to "Aprovações Fiscais" from menu
2. See table of all pending requests
3. Review each request with current and requested fiscal data
4. Click "Aprovar" to immediately approve and update user data
5. Click "Rejeitar" to open rejection reason modal
6. Provide reason for rejection and submit
7. Receive confirmation notification
8. Page auto-refreshes to reflect change

## Menu Integration
Added to `inc/menu_config.php`:
- Menu item: "Aprovações Fiscais"
- Route: `/admin/fiscal-approvals.php`
- Icon: check-square
- Roles: Gestor, Suporte Financeiro
- Position: Between Pagamentos and Tickets in admin menu

## Implementation Checklist

### Database ✅
- [x] Created fiscal_change_requests table with full audit trail
- [x] Added indexes for optimal query performance
- [x] Set up foreign key relationships with proper cascade policies

### Business Logic ✅
- [x] Implemented NIF validation (Portuguese algorithm)
- [x] Implemented request submission with validation
- [x] Implemented request approval with data update
- [x] Implemented request rejection with decision reason
- [x] Implemented history retrieval functions
- [x] Added logging for all operations

### API ✅
- [x] Created REST endpoint at /inc/api/fiscal-requests.php
- [x] Implemented action routing (submit, approve, reject, getHistory, getPending)
- [x] Added CSRF validation
- [x] Added role-based permission checks
- [x] Added error handling with appropriate HTTP status codes
- [x] Returns proper JSON responses

### Frontend ✅
- [x] Updated profile.php to load user and fiscal data
- [x] Made fiscal fields read-only with visual indicators
- [x] Added protection info banner for clients
- [x] Created modal form for change requests
- [x] Implemented client-side validation
- [x] Added toast notifications for feedback
- [x] Display request history with status badges
- [x] Created admin fiscal-approvals.php page
- [x] Implemented approve/reject functionality in admin
- [x] Added rejection reason modal
- [x] Updated menu configuration with fiscal approvals link

### Security ✅
- [x] CSRF token validation on all API endpoints
- [x] Session validation (requireLogin)
- [x] Role-based access control
- [x] Input validation at all layers
- [x] Prepared statements for SQL queries
- [x] Rate limiting (one pending request per user)
- [x] Complete audit trail

## Testing Recommendations

1. **NIF Validation**
   - Test valid Portuguese NIF (9 digits with correct checksum)
   - Test invalid NIF (incorrect checksum, wrong length)
   - Test NIF format validation

2. **Client Flow**
   - Submit request as Cliente role
   - Verify request appears in history
   - Verify notification/toast appears
   - Attempt request with invalid data (should fail)

3. **Manager Flow**
   - View pending requests as Gestor or Suporte Financeiro
   - Approve request and verify user fiscal data updates
   - Reject request with reason and verify rejection stored
   - Verify only pending requests show

4. **Permission Enforcement**
   - Test that Clients cannot access /admin/fiscal-approvals.php
   - Test that other roles cannot submit requests (403)
   - Test that non-manager roles cannot approve (403)

5. **Duplicate Prevention**
   - Submit first request (should succeed)
   - Attempt to submit second request while first is pending (should fail)
   - Approve first request
   - Submit new request (should succeed)

6. **Audit Trail**
   - Verify old_* and new_* values stored correctly
   - Verify reviewed_by and reviewed_at populated on approval/rejection
   - Verify decision_reason stored on rejection
   - Check logs table for entries

## Files Modified/Created

### Created
- `inc/fiscal_requests.php` - Business logic library
- `inc/api/fiscal-requests.php` - REST API endpoint
- `admin/fiscal-approvals.php` - Admin approval dashboard

### Modified
- `sql/schema.sql` - Added fiscal_change_requests table
- `profile.php` - Updated fiscal data display and request form
- `inc/menu_config.php` - Added fiscal approvals menu item

### Total Implementation
- 3 new files (500+ lines)
- 3 modified files
- 1 new database table with complete audit trail
- Complete role-based permission system
- Full audit logging integration
