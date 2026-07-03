## 2025-05-15 - [Sensitive Data Leak in Logs]
**Vulnerability:** Plaintext passwords were being recorded in system logs in `DriverController@store` when creating new driver accounts.
**Learning:** Logging the entire `$validated` array after validation is a common but dangerous pattern if that array contains sensitive user-provided data like passwords.
**Prevention:** Always sanitize data before logging. Use `Arr::except()` to strip sensitive keys from arrays before passing them to the logger.

## 2025-05-15 - [Unrestricted File Upload / Stored XSS]
**Vulnerability:** `DocumentController` allowed uploading any file type for fleet documents, which could lead to Stored XSS if a malicious HTML file was uploaded and then previewed.
**Learning:** Generic file validation (`file`) without extension or MIME type restrictions (`mimes`) is insufficient for public-facing or previewable uploads.
**Prevention:** Implement strict `mimes` validation rules on all file upload endpoints to restrict allowed formats to a known safe list.
