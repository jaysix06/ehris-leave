# Employee Details API

This API provides **read-only employee details** using an API key.

## Base URLs

- Local: `http://127.0.0.1:8000`
- Server example: `http://58.69.118.16:83`

## Authentication

Send your API key in the `X-API-KEY` header.

```http
X-API-KEY: your-shared-api-key
Accept: application/json
```

API key source:

- `.env` key: `EXTERNAL_API_KEY`
- Config file: `config/api_access.php`

After changing `.env`, run:

```bash
php artisan config:clear
```

## Endpoints

### Get All Employee Details

- **Method:** `GET`
- **Path:** `/api/employee-details`
- **Route name:** `api.employee-details.index`
- **Middleware:** `api.key`, `throttle:60,1`

#### 200 OK

```json
{
  "data": [
    {
      "userDetails": {
        "userId": 21371,
        "hrId": 1001001,
        "email": "tests1@gmail.com",
        "lastname": "DELA CRUZ",
        "firstname": "JUAN",
        "middlename": "SANTOS",
        "extname": "JR.",
        "avatar": "avatar-default.jpg",
        "job_title": "Teacher I",
        "role": "employee",
        "fullname": "JUAN SANTOS DELA CRUZ JR."
      }
    }
  ],
  "meta": {
    "count": 1
  }
}
```

### Get Employee Details By HRID

- **Method:** `GET`
- **Path:** `/api/employee-details/{hrid}`
- **Route name:** `api.employee-details.show`
- **Middleware:** `api.key`, `throttle:60,1`
- **Path param:**
  - `hrid` (number) - employee HR ID

## Response

### 200 OK

```json
{
  "data": {
    "userDetails": {
      "userId": 21371,
      "hrId": 1001001,
      "email": "tests@gmail.com",
      "lastname": "DELA CRUZ",
      "firstname": "JUAN",
      "middlename": "SANTOS",
      "extname": "JR.",
      "avatar": "avatar-default.jpg",
      "job_title": "Teacher I",
      "role": "employee",
      "fullname": "JUAN SANTOS DELA CRUZ JR."
    }
  }
}
```

### 401 Unauthorized (missing/invalid API key)

```json
{
  "message": "Unauthorized."
}
```

### 404 Not Found (no matching `tbl_user` record by `hrId`)

```json
{
  "message": "Employee details not found."
}
```

## cURL Examples

### All users

```bash
curl --request GET "http://127.0.0.1:8000/api/employee-details" \
  --header "Accept: application/json" \
  --header "X-API-KEY: your-shared-api-key"
```

### Single user by HRID

```bash
curl --request GET "http://127.0.0.1:8000/api/employee-details/1001001" \
  --header "Accept: application/json" \
  --header "X-API-KEY: 1q2w3e4r5t"
```

## Postman Quick Setup

1. Method: `GET`
2. URL:
   - All users: `{{base_url}}/api/employee-details`
   - Single user: `{{base_url}}/api/employee-details/{{hrid}}`
3. Headers:
   - `Accept: application/json`
   - `X-API-KEY: {{api_key}}`

Suggested Postman variables:

- `base_url` = `http://127.0.0.1:8000`
- `hrid` = `1001001`
- `api_key` = your `1q2w3e4r5t`
