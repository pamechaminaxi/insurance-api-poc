# Postman Testing Guide - Insurance Quote & Claim Management APIs

Follow these steps to test the full lifecycle of the Insurance APIs.

## Step 1: Authentication (Get Token)

### Login (Admin/Agent/Customer)
- **Method**: `POST`
- **URL**: `{{base_url}}/api/auth/login`
- **Body (JSON)**:
```json
{
    "email": "agent@insurance.com",
    "password": "password"
}
```
- **Action**: Copy the `token` from the response. Go to Postman **Auth** tab -> Type: **Bearer Token** -> Paste the token. Use this token for all subsequent requests.

---

## Step 2: Quote Management (Agent Only)

### Create Quote
- **Method**: `POST`
- **URL**: `{{base_url}}/api/quotes`
- **Body (JSON)**:
```json
{
    "customer_name": "John Doe",
    "insurance_type": "Auto",
    "premium_amount": 1250.50,
    "status": "Draft"
}
```

### List Quotes (with search/filter)
- **Method**: `GET`
- **URL**: `{{base_url}}/api/quotes?status=Draft&search=John`

### Update Quote
- **Method**: `PUT`
- **URL**: `{{base_url}}/api/quotes/1`
- **Body (JSON)**:
```json
{
    "customer_name": "John Doe Updated",
    "insurance_type": "Auto",
    "premium_amount": 1300.00,
    "status": "Submitted"
}
```

---

## Step 3: Claim Management

### File a Claim (Agent/Customer)
- **Method**: `POST`
- **URL**: `{{base_url}}/api/claims`
- **Body (form-data)**:
  - `quote_id`: `1`
  - `claim_amount`: `500.00`
  - `description`: `Accident on Highway 101`
  - `documents[]`: (Select multiple JPG/PDF files)
- *Note: Use `form-data` instead of `raw JSON` when uploading files.*

### List Claims
- **Method**: `GET`
- **URL**: `{{base_url}}/api/claims`

### Update Claim Status (Admin/Agent)
- **Method**: `PATCH`
- **URL**: `{{base_url}}/api/claims/1/status`
- **Body (JSON)**:
```json
{
    "status": "Under Review"
}
```

---

## Step 4: Admin Audit

### View Activity Logs (Admin Only)
- **Method**: `GET`
- **URL**: `{{base_url}}/api/activity-logs`
- *Note: Login as `admin@insurance.com` first to get an Admin token.*

---

## Response Formats

### Success Example
```json
{
    "success": true,
    "message": "Quote created successfully",
    "data": {
        "id": 1,
        "quote_number": "QT-ABC12345",
        "customer_name": "John Doe",
        ...
    }
}
```

### Error Example (Validation)
```json
{
    "success": false,
    "message": "The given data was invalid.",
    "errors": {
        "customer_name": ["The customer name field is required."]
    }
}
```
