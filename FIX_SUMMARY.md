# CyberCore Fix Summary - Dashboard Routing, Profile Data & Fiscal Permissions

## Issues Fixed

### 1. ✅ Dashboard Role-Based Routing
**Problem:** All roles were redirected to the client dashboard (dashboard.php) after login.

**Solution:** 
- Added `getDashboardUrlByRole()` function in `inc/auth.php` to map roles to specific dashboards:
  - **Gestor** → `/admin/dashboard.php`
  - **Suporte Financeiro** → `/finance.php`
  - **Suporte Técnico** → `/services.php`
  - **Suporte ao Cliente** → `/support.php`
  - **Cliente** → `/dashboard.php`

- Added `redirectToDashboard()` helper function to centralize redirect logic

- Updated `login.php` to use `redirectToDashboard()` after successful authentication

**Files Modified:**
- `/inc/auth.php` - Added dashboard routing functions
- `/login.php` - Updated to use role-based redirect
- `/admin/dashboard.php` - Added role check to ensure admin access only

---

### 2. ✅ Profile Data Loading
**Problem:** profile.php was not loading user data from the database - all form fields were empty.

**Solution:**
- User data is already being loaded at the top of `profile.php` via:
  ```php
  $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
  $stmt->execute([$cu['id']]);
  $userData = $stmt->fetch(PDO::FETCH_ASSOC);
  ```

- **Updated all personal data form fields** to populate with `$userData`:
  - Full Name: `value="<?php echo htmlspecialchars(trim(($userData['first_name'] ?? '') . ' ' . ($userData['last_name'] ?? ''))); ?>"`
  - Email: `value="<?php echo htmlspecialchars($userData['email'] ?? ''); ?>"`
  - Phone: `value="<?php echo htmlspecialchars($userData['phone'] ?? ''); ?>"`
  - Address: `value="<?php echo htmlspecialchars($userData['address'] ?? ''); ?>"`
  - City: `value="<?php echo htmlspecialchars($userData['city'] ?? ''); ?>"`
  - Postal Code: `value="<?php echo htmlspecialchars($userData['postal_code'] ?? ''); ?>"`
  - Country: Added `selected` attribute based on `$userData['country']`

**Files Modified:**
- `/profile.php` - Added value attributes to all personal data form fields

---

### 3. ✅ Fiscal Data Permissions
**Problem:** Fiscal data (NIF, Entity Type, Company Name) was locked for ALL roles.

**Solution:** Implemented role-based fiscal data permissions:

#### Frontend (profile.php)
- **Manager & Financial Support:**
  - Fiscal fields are **editable** (input/select fields without readonly)
  - Form includes "Guardar Dados Fiscais" submit button
  - Fields populated with current database values

- **All Other Roles (Client, Technical Support, Customer Support):**
  - Fiscal fields are **read-only** (readonly attribute + visual lock indicator)
  - No submit button for fiscal data
  - Client role sees fiscal change request button and history

#### Backend Protection
Created `/inc/fiscal_update.php` endpoint to handle fiscal data updates:
- **Authentication:** Requires valid session
- **Authorization:** Only Manager and Financial Support roles allowed
- **Validation:**
  - Entity type must be 'Singular' or 'Coletiva'
  - NIF must be exactly 9 digits
  - Company name required for 'Coletiva' type
  - NIF uniqueness check (prevent duplicates)
- **Audit Logging:** All fiscal updates logged to logs table
- **Error Handling:** Proper HTTP status codes and error messages

#### JavaScript Updates (profile.js)
- Detects if fiscal fields are editable based on readonly attribute
- For editable fields:
  - Handles form submission with validation
  - Posts to `/inc/fiscal_update.php`
  - Shows success/error messages
  - Reloads user data after successful update
- For read-only fields:
  - Prevents form submission
  - Maintains existing fiscal change request flow for Clients

**Files Created:**
- `/inc/fiscal_update.php` - Backend fiscal data update handler

**Files Modified:**
- `/profile.php` - Role-based fiscal field rendering (editable vs readonly)
- `/assets/js/pages/profile.js` - Added fiscal form submission logic for managers

---

## Permission Matrix

| Role | Dashboard | Profile Data | Fiscal Data (Read) | Fiscal Data (Edit) |
|------|-----------|--------------|--------------------|--------------------|
| **Gestor (Manager)** | /admin/dashboard.php | ✅ View & Edit | ✅ | ✅ Direct Edit |
| **Suporte Financeiro** | /finance.php | ✅ View & Edit | ✅ | ✅ Direct Edit |
| **Suporte Técnico** | /services.php | ✅ View & Edit | ✅ | ❌ Read-only |
| **Suporte ao Cliente** | /support.php | ✅ View & Edit | ✅ | ❌ Read-only |
| **Cliente (Client)** | /dashboard.php | ✅ View & Edit | ✅ | ❌ Request Change |

---

## Security Measures

### Dashboard Routing
- ✅ All dashboard routes protected with `requireLogin()`
- ✅ Admin routes protected with `checkRole()`
- ✅ Role validation at session level

### Profile Data
- ✅ Session-based authentication
- ✅ CSRF token validation on all updates
- ✅ Input sanitization with htmlspecialchars()
- ✅ SQL injection prevention via prepared statements

### Fiscal Data
- ✅ **Frontend Protection:** Readonly attributes prevent client-side editing
- ✅ **Backend Protection:** Role validation in fiscal_update.php
- ✅ **Double Layer Security:** Even if frontend bypassed, backend rejects unauthorized requests
- ✅ **Audit Trail:** All fiscal changes logged with user_id, timestamp, and action
- ✅ **HTTP 403** returned for permission denied
- ✅ **NIF Validation:** 9-digit format check
- ✅ **Uniqueness Check:** Prevents duplicate NIFs across users

---

## Testing Checklist

### Dashboard Routing
- [ ] Login as **Gestor** → Should redirect to `/admin/dashboard.php`
- [ ] Login as **Suporte Financeiro** → Should redirect to `/finance.php`
- [ ] Login as **Suporte Técnico** → Should redirect to `/services.php`
- [ ] Login as **Suporte ao Cliente** → Should redirect to `/support.php`
- [ ] Login as **Cliente** → Should redirect to `/dashboard.php`

### Profile Data Loading
- [ ] Navigate to `/profile.php`
- [ ] Verify **Full Name** field shows first_name + last_name
- [ ] Verify **Email** field shows current email
- [ ] Verify **Phone**, **Address**, **City**, **Postal Code**, **Country** all populated
- [ ] Edit personal data and save → Should update successfully

### Fiscal Permissions - Manager/Financial Support
- [ ] Login as **Gestor** or **Suporte Financeiro**
- [ ] Navigate to `/profile.php` → **Fiscal tab**
- [ ] Verify fiscal fields are **editable** (no readonly)
- [ ] Verify "Guardar Dados Fiscais" button appears
- [ ] Edit NIF, Entity Type, or Company Name
- [ ] Click save → Should update successfully
- [ ] Verify success toast message appears
- [ ] Verify data persists after page reload

### Fiscal Permissions - Other Roles
- [ ] Login as **Cliente**, **Suporte Técnico**, or **Suporte ao Cliente**
- [ ] Navigate to `/profile.php` → **Fiscal tab**
- [ ] Verify fiscal fields are **read-only** with lock indicators
- [ ] Verify no "Guardar Dados Fiscais" button
- [ ] For **Cliente**: Verify "Solicitar alteração" button appears
- [ ] Attempt to POST to `/inc/fiscal_update.php` directly → Should return **403 Forbidden**

### Backend Security
- [ ] Attempt fiscal update as Cliente via API → Should receive 403
- [ ] Attempt fiscal update with invalid NIF → Should receive validation error
- [ ] Attempt fiscal update with duplicate NIF → Should receive uniqueness error
- [ ] Verify all fiscal updates logged to `logs` table

---

## API Endpoints

### Profile Updates
- **Endpoint:** `POST /inc/profile_update.php`
- **Auth:** Session required
- **Fields:** fullName, email, phone, address, city, postalCode, country
- **Response:** JSON with success/errors

### Fiscal Data Updates (Manager/Financial Support Only)
- **Endpoint:** `POST /inc/fiscal_update.php`
- **Auth:** Session + Role validation (Gestor, Suporte Financeiro)
- **Fields:** entityType, companyName, taxId
- **Validation:**
  - entityType: 'Singular' or 'Coletiva'
  - taxId: 9 digits, numeric, unique
  - companyName: Required if Coletiva, min 3 chars
- **Response:** JSON with success/errors
- **HTTP Codes:**
  - 200: Success
  - 400: Validation error
  - 401: Unauthorized
  - 403: Forbidden (wrong role)
  - 405: Method not allowed
  - 500: Server error

---

## Files Modified Summary

| File | Changes |
|------|---------|
| `/inc/auth.php` | Added getDashboardUrlByRole(), redirectToDashboard() |
| `/login.php` | Updated to use redirectToDashboard() after login |
| `/admin/dashboard.php` | Added checkRole() for admin access |
| `/profile.php` | Populated form fields, role-based fiscal rendering |
| `/assets/js/pages/profile.js` | Added fiscal form submission handler |
| `/inc/fiscal_update.php` | **NEW** - Fiscal data update endpoint |

---

## Deployment Notes

1. **Database:** No schema changes required - uses existing users table
2. **Session:** Ensure session is active and role is stored in $_SESSION['role']
3. **CSRF:** Ensure CSRF token is available in meta tag or via /inc/get_csrf_token.php
4. **Permissions:** Verify user roles are properly set in database (correct spelling/case)
5. **Testing:** Test each role individually to verify dashboard routing and permissions

---

## Future Enhancements

- [ ] Email notification when Manager updates user fiscal data
- [ ] Fiscal change history/audit view for Managers
- [ ] Bulk fiscal data import for Managers
- [ ] Advanced NIF validation (Portuguese tax authority API check)
- [ ] Two-factor authentication for fiscal data changes

---

**Implementation Date:** December 26, 2025  
**Status:** ✅ Complete - Ready for Testing
