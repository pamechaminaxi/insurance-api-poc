# Walkthrough: Caching & Queue Implementation

I have fully implemented the requested bonus features: **Queue-based Email Notifications** and **Redis Caching**. 

---

## 🛠️ Changes Implemented

### 1. Environment Configurations
- **[.env](file:///var/www/html/insurance-api/.env#L40):** Changed `CACHE_STORE` from `database` to `redis` to enable high-performance in-memory caching.

### 2. Mailable & Queue Infrastructure
- **[StatusNotificationMail.php](file:///var/www/html/insurance-api/app/Mail/StatusNotificationMail.php):** Created a new Mailable to send status updates.
- **[status_notification.blade.php](file:///var/www/html/insurance-api/resources/views/emails/status_notification.blade.php):** Built a premium, clean card-styled HTML email design utilizing modern font styling and harmonious color gradients.
- **[SendEmailNotification.php](file:///var/www/html/insurance-api/app/Jobs/SendEmailNotification.php):** Implemented an asynchronous job that implements `ShouldQueue`, dispatching the email task to the background queue.

### 3. Caching & Notification Service Layer
- **[QuoteService.php](file:///var/www/html/insurance-api/app/Services/QuoteService.php):**
  - Cached the paginated `getAllQuotes()` listing query under the `quotes` tag.
  - Automatically flushes cache tags on quote creation, updates, and deletes.
  - Dispatches `SendEmailNotification` in `updateQuote()` when the quote's status is modified.
- **[ClaimService.php](file:///var/www/html/insurance-api/app/Services/ClaimService.php):**
  - Cached the paginated `getAllClaims()` listing query under the `claims` tag.
  - Flushes the claim cache on claim creation and status updates.
  - Dispatches `SendEmailNotification` asynchronously when `updateClaimStatus()` is called to notify the customer of progress (e.g., Pending -> Under Review -> Approved).

---

## 🔍 How to Test & Verify

Follow these simple steps on your local environment to see these features in action:

### 1. Ensure Redis is Running
Make sure you have your Redis server running locally:
```bash
redis-server
```

### 2. Test the Redis Caching
1. Call `GET /api/quotes` or `GET /api/claims` using Postman.
2. The first request will fetch the data from the database and cache it in Redis.
3. Make the same request a second time. The response should return significantly faster as it is now served directly from your fast Redis memory cache!
4. Create a new Quote or Claim. The cache will automatically invalidate. Making the `GET` request again will instantly fetch fresh data from MySQL and re-cache it.

### 3. Test the Background Email Queue
1. Change the status of a Claim via `PATCH /api/claims/{id}/status`.
2. The response will return **instantly** (without any network delay!) because the email sending task has been offloaded to the queue.
3. Look at your `jobs` table in the database—you will see a new pending job.
4. Run the queue worker to process the email in the background:
   ```bash
   php artisan queue:work
   ```
5. Since we are using the `log` driver in the local environment, check `storage/logs/laravel.log` to see the generated premium email content perfectly rendered and logged!
