# Insurance Quote & Claim Management API

## Project Objective
A secure, scalable REST API built with Laravel for managing insurance quotes, claims, document uploads, and role-based authentication. 

## Key Features
- **Authentication:** Token-based authentication using Laravel Sanctum.
- **Role-Based Access Control (RBAC):** Distinct access levels for Admin, Agent, and Customer roles.
- **Quote Management:** Full CRUD capabilities with state management, pagination, and filtering.
- **Claim Management:** End-to-end claim lifecycle management tied to approved quotes, with automated financial validations.
- **Document Uploads:** Secure storage and validation for claim evidence (JPG, PNG, PDF up to 10MB).
- **Activity Logging:** Automated auditing of model changes (Created/Updated) and authentication events.
- **Security:** Standardized JSON responses, FormRequest validations, and strict route protections.

## Core Architecture
- **Controllers:** Handle HTTP request ingestion and response formatting (`ApiResponse`).
- **Services:** Encapsulate core business logic (`QuoteService`, `ClaimService`, `DocumentService`).
- **Traits:** Reusable logic such as `LogsActivity` for automatic event tracking.
- **FormRequests:** Dedicated validation rules to ensure data integrity before reaching controllers.

## Submission Assets Included
- Source Code
- `SETUP_STEPS.md` (Installation and Configuration)
- `API_DOCUMENTATION.md` (Endpoint References)
- `postman_testing_guide.md`
