# BPNHS API
Brooke's Point National High School API

## Available Requests
Queries can be `POST|GET` method only

### Queries
 - /api/login - user, pass
 - /api/register - user, pass, fname, mname, lname, gender, email
 - /api/auth/logout - token
 - /api/account_info - token
 - /api/auth/verify_token - token
 - /api/user/:id

### Cron jobs
 - /api/cron/logout_expired