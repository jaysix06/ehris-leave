# AI Prompt: Sick Leave Logic (Leave Application)

Use this prompt when implementing or reviewing sick leave rules in the leave application.

---

## Prompt

Implement (or verify) sick leave behavior in this leave application according to the following policy.

**Policy:**

1. **Filing timing:** Sick leave shall be filed immediately upon the employee's return from such leave.
2. **Supporting documents:** If the leave is filed in advance OR the leave exceeds five (5) days, the application shall be accompanied by a medical certificate. If medical consultation was not availed of, an affidavit should be executed by the applicant.

**Required logic and conditions:**

- **Filing upon return:** For leave type "Sick Leave", allow the employee to file with leave dates in the past (backdated), i.e. the application can be submitted after the absence. Optionally enforce "filed within N working days of return" if the organization defines "immediately" (e.g. 3 or 5 days).

- **When to require a supporting document:** Require either a medical certificate or an affidavit when **any** of these is true:
  - The leave is **filed in advance** (leave start date is after the application/submission date, or after today).
  - The leave **exceeds 5 days** (number of leave days > 5), regardless of whether it was filed in advance or upon return.

- **Type of document:** When a document is required, the applicant must provide **one** of:
  - **Medical certificate** (when medical consultation was availed of), or
  - **Affidavit** (when medical consultation was not availed of).

- **Definitions:**
  - **Filed in advance:** `leave_start_date > application_date` (or `leave_start_date > today` at submission).
  - **Number of days:** Use the same calendar or working-day count as for other leave types in this app.

**Implement or verify:**

1. **Validation:** If leave type is Sick Leave AND (filed in advance OR no_of_days > 5), require at least one attachment: medical certificate OR affidavit. Block submit and show a clear error if neither is provided.

2. **UI:** For Sick Leave only, allow start/end date to be in the past. When (filed in advance OR no_of_days > 5), show the requirement and provide upload for medical certificate and/or affidavit; optionally add a choice "Medical consultation availed? Yes/No" and require medical certificate when Yes and affidavit when No.

3. **Backend:** Validate on submit that when leave type is Sick Leave and (filed_in_advance OR no_of_days > 5), a medical certificate or affidavit file (or both) is present; reject the request with a validation error otherwise.

Ensure existing leave types and date/duration logic are unchanged; only add or adjust logic for Sick Leave as above.
