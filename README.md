# SuperStocks API File Summary

This README describes how each of the API files works.

## `api/add_category.php`
- Accepts only `POST` requests.
- Requires the user to be authenticated with `require_auth()`.
- Reads `name` from `$_POST`, trims it, and validates that it is not empty.
- Inserts a new category into the `categories` table using a prepared statement.
- If a category with the same name already exists, it returns a `409 Conflict` error.
- On success, it returns HTTP `201 Created` with JSON containing the new category `id` and `name`.

## `api/delete_category.php`
- Accepts only `POST` requests.
- Requires the user to be authenticated with `require_auth()`.
- Reads `id` from `$_POST`, validates it as a positive integer.
- Deletes the category from the `categories` table using a prepared statement.
- If the category still has associated products, it returns a `409 Conflict` error due to foreign key constraints.
- If no category is found, it returns a `404 Not Found` error.
- On success, it returns HTTP `200 OK` with JSON `{"ok": true}`.

## `api/get_categories.php`
- Requires the user to be authenticated with `require_auth()`.
- Fetches all categories from the `categories` table.
- Orders categories by `name` ascending.
- Converts the result rows into an array of objects and returns them as JSON.
- Uses `api_respond()` to send the JSON response.

## `api/bulk_delete_products.php`
- Accepts only `POST` requests.
- Requires the user to be authenticated with `require_auth()`.
- Reads `ids` from `$_POST` as a JSON array.
- Validates that `ids` is a non-empty array of positive integers.
- Builds a dynamic prepared statement for `DELETE FROM products WHERE id IN (...)`.
- Executes the delete and returns the number of deleted rows as JSON.

## `api/bulk_dispose_products.php`
- Accepts only `POST` requests.
- Requires the user to be authenticated with `require_auth()`.
- Reads `ids` from `$_POST` as a JSON array.
- Validates that `ids` is a non-empty array of positive integers.
- Builds a dynamic prepared statement for `UPDATE products SET quantity = 0 WHERE id IN (...)`.
- Executes the update and returns the number of updated rows as JSON.
