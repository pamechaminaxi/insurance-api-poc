# API Documentation

## Base URL
`http://localhost:8000/api` (or your configured local domain)

## Authentication
All protected routes require an `Authorization` header using a Bearer token obtained from the `/login` endpoint.
`Authorization: Bearer <your_token>`

## Response Standard
All APIs return a standardized JSON structure:
```json
{
    "success": true,
    "message": "Operation successful",
    "data": { ... } 
}
```
In case of an error:
```json
{
    "success": false,
    "message": "Error description",
    "data": null
}
```

---

## 1. Authentication APIs
**POST /auth/login**
- **Body:** `email`, `password`
- **Access:** Public

**POST /auth/register**
- **Body:** `name`, `email`, `password`, `password_confirmation`, `role_id`
- **Access:** Admin Only

**POST /auth/logout**
- **Access:** Protected (Any Authenticated User)

**GET /auth/profile**
- **Access:** Protected (Any Authenticated User)

---

## 2. Quote Management APIs
**GET /quotes**
- **Query Params (Optional):** `search`, `status`, `per_page`
- **Access:** Admin, Agent, Customer (Customers only see their own)

**POST /quotes**
- **Body:** `customer_user_id`, `customer_name`, `insurance_type`, `premium_amount`, `coverage_amount`
- **Access:** Admin, Agent

**GET /quotes/{id}**
- **Access:** Admin, Agent, Customer (if owner)

**PUT /quotes/{id}**
- **Body:** Update any quote fields
- **Access:** Admin, Agent (Only if quote is in 'draft' status)

**DELETE /quotes/{id}**
- **Access:** Admin (or Agent if in 'draft' status)

---

## 3. Claim Management APIs
**GET /claims**
- **Query Params (Optional):** `status`, `per_page`
- **Access:** Admin, Agent, Customer (Customers only see their own)

**POST /claims**
- **Body (multipart/form-data):** 
  - `quote_id` (must belong to an 'approved' quote)
  - `claim_amount` (must not exceed policy coverage)
  - `description`
  - `documents[]` (Array of files: JPG, PNG, PDF. Max 10MB each)
- **Access:** Admin, Agent, Customer

**GET /claims/{id}**
- **Access:** Admin, Agent, Customer (if owner)

**PATCH /claims/{id}/status**
- **Body:** `status` (Pending, Under Review, Approved, Rejected, Settled)
- **Access:** Admin, Agent

**PUT /claims/{id}**
- **Body:** Update claim fields.
- **Access:** Admin, Agent

---

## 4. Activity Logs
**GET /activity-logs**
- **Access:** Admin Only
- **Description:** Retrieves an audit trail of system events including Logins, Data Creations, and Status Updates.
