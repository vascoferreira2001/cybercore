# Quick Start Guide - Fiscal Data Change Management

## For Clients

### How to Request a Fiscal Data Change

1. **Log in** to your CyberCore account
2. **Navigate** to your Profile (click your name in top menu)
3. **Find** the "Dados Fiscais" section
4. **Click** "Solicitar alteração de dados fiscais" button (red button)
5. **Fill in** the modal form:
   - **Novo NIF**: Enter your new NIF (9 digits, will be validated)
   - **Tipo de Entidade**: Select "Pessoa Singular" or "Pessoa Coletiva"
   - **Nome da Empresa**: Enter company name if type is "Pessoa Coletiva"
   - **Motivo da Alteração**: Explain why you need this change (min 10 chars)
6. **Click** "Enviar Solicitação"
7. **See** success notification - your request is submitted
8. **Wait** for approval from a manager - you can see the status in "Histórico de Solicitações"

### Expected Timeline
- ✅ Request submitted immediately
- ⏳ Pending review by Financial Manager (usually 1-2 business days)
- ✅ Approved: Fiscal data updates automatically
- ❌ Rejected: You can see the reason and submit a new request

---

## For Managers & Financial Support

### How to Approve Fiscal Change Requests

1. **Log in** as a manager or Financial Support staff
2. **Navigate** to Admin menu → "Aprovações Fiscais"
3. **Review** each pending request:
   - Current fiscal data (left side)
   - Requested fiscal data (yellow highlighted)
   - Change reason provided by client
4. **Approve** the change:
   - Click "Aprovar" button
   - Confirm in popup dialog
   - Change is applied immediately
5. **Reject** the change (if needed):
   - Click "Rejeitar" button
   - Enter detailed rejection reason (min 10 chars)
   - Submit - client will see rejection reason

### Key Features
- ✅ All pending requests visible in one table
- ✅ Easy comparison of current vs requested data
- ✅ One-click approval with confirmation
- ✅ Optional rejection reason for transparency
- ✅ Complete audit trail of all changes

---

## System Security Features

### Data Protection
- 🔒 Fiscal data (NIF, Entity Type, Company Name) **cannot be edited directly**
- 🔒 All changes require **approval workflow**
- 🔒 Changes are **logged with full audit trail**
- 🔒 NIF validation ensures **data integrity**

### Permission Control
- **Clients**: Can only submit requests
- **Managers/Financial Support**: Can only approve/reject
- **All roles**: Cannot edit fiscal data directly

### Audit Trail
Every change is tracked with:
- Old value → New value
- Who requested the change (client)
- When they requested it
- Who approved/rejected it
- When it was approved/rejected
- Rejection reason (if applicable)

---

## Frequently Asked Questions

**Q: Why can't I edit my NIF directly?**
A: Fiscal data (NIF, entity type, company name) are critical for legal and financial records. They require manager verification before any changes are made.

**Q: How long does approval take?**
A: Typically 1-2 business days. Your manager will review and either approve or reject the request.

**Q: What if my request is rejected?**
A: You can see the rejection reason and submit a new request with corrected information.

**Q: Can I submit multiple requests?**
A: No, only one pending request per client at a time. Wait for approval/rejection before submitting a new one.

**Q: Where can I track my requests?**
A: In your Profile page, under "Histórico de Solicitações" section. You'll see the status (pending/approved/rejected) and timestamps.

---

## Technical Information

### Database
- Table: `fiscal_change_requests`
- Tracks all requests with complete audit trail
- Indexes optimized for fast queries
- Relationships with users table for referential integrity

### API Endpoint
- `POST /inc/api/fiscal-requests.php`
- Actions: submit, approve, reject, getHistory, getPending
- Secured with CSRF tokens and role-based access control
- Returns JSON responses with clear error messages

### Validation
- Portuguese NIF validation (mod-11 checksum)
- Entity type validation (Singular/Coletiva)
- Company name required for Coletiva type
- Reason field minimum 10 characters
- No duplicate pending requests allowed

### Files
- Business Logic: `/inc/fiscal_requests.php`
- API Endpoint: `/inc/api/fiscal-requests.php`
- Admin Page: `/admin/fiscal-approvals.php`
- User Profile: `/profile.php` (updated)
- Menu Config: `/inc/menu_config.php` (updated)
- Database Schema: `/sql/schema.sql` (updated)

---

## Support

For technical issues or questions:
1. Check the audit trail in the fiscal_change_requests table
2. Review logs in System Logs page
3. Contact your CyberCore administrator

Last Updated: 2025-01-15
