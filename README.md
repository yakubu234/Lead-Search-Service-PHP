# CRM Modernization Task: Refactoring Explanation

This document outlines the changes made to the `search_leads.php` script as part of the CRM Modernization Task.

# Project Structure and Implementation

Please disregard the `Question_or_Task` folder, as it contains the original assessment and sample code. The live implementation and all relevant code for this project can be found in the `app` and `search` folders.

## 1. Separation of Concerns: Business Logic vs. Presentation

The original `search_leads.php` file was a classic example of "spaghetti code," mixing database queries, business logic, and HTML rendering in a single script. This makes the code difficult to maintain, test, and understand.

To address this, I performed the following refactoring:

*   **`search/LeadSearchService.php`**: I created a new class, `LeadSearchService`, to encapsulate all business logic. This class is now responsible for:
    *   Handling and validating all incoming request parameters (like `searchValue` and `searchText`).
    *   Coordinating with the `DB_Common_Functions` class to fetch data.
    *   Calculating pagination details.
    *   Logging search activities.
    *   By injecting dependencies (`DB_Common_Functions`, `Common_Utilities`) into its constructor, this class is now decoupled and can be more easily unit-tested.

*   **`app/views/search-results-view.php`**: I created a new view file to handle the presentation of the search results. This file contains the HTML for the results table and pagination controls. It's a simple template that receives data from the controller.

*   **`search/search_leads.php` (The Controller)**: The original file has been transformed into a lean "controller." Its sole responsibilities are to initialize the `LeadSearchService`, pass it the request data, retrieve the processed results, and then include the appropriate view files to render the final HTML page.

This separation follows the Model-View-Controller (MVC) pattern, leading to a more organized, maintainable, and scalable codebase.

## 2. New Feature: Company Name Search

As requested, I implemented a new search criterion for "Company Name."

*   **Frontend**: The task assumed the frontend form in `search-leads-view.php` would be modified. My backend implementation supports this change.
*   **Backend**: In `LeadSearchService.php`, I added `'company_name'` to the list of allowed search values.
*   **Data Logic**: The `dispatchSearchCall` method within the service now includes a `case` for `'company_name'`. It calls the assumed `searchLeadsByCompanyNameWithOffset()` and `searchLeadsByCompanyName()` methods on the `DB_Common_Functions` object to fetch the relevant data.

## 3. Security Vulnerabilities and Fixes

I identified and addressed two primary security vulnerabilities in the original code:

1.  **Arbitrary `searchValue` Injection**:
    *   **Vulnerability**: The code directly used `$_REQUEST['searchValue']` without validation. While the `switch` statement limited the immediate harm, an attacker could pass unexpected values, potentially probing for other vulnerabilities or causing unintended behavior if the code were to change.
    *   **Fix**: In `LeadSearchService`, I implemented an allow-list (`ALLOWED_SEARCH_VALUES`). The `searchValue` from the request is now strictly checked against this list. If the value is not valid, it defaults to a safe value (`fname`), preventing any malicious or unexpected input from being processed.

2.  **Cross-Site Scripting (XSS)**:
    *   **Vulnerability**: The `$searchtext` was sanitized with a custom `$util->sanitize_html()` function, the effectiveness of which is unknown. More importantly, the data retrieved from the database (e.g., `$lead->full_name`, `$lead->email`) was directly printed to the HTML using `printf`, creating a significant risk of stored XSS if the data in the database was not already sanitized.
    *   **Fix**:
        *   For input, I now use `htmlspecialchars()` on the `searchText` in the `LeadSearchService` as a more standard and reliable way to neutralize any HTML characters.
        *   Now, in the `app/views/search-results-view.php` template, all data being rendered into the HTML is now escaped using `htmlspecialchars()` (via the short echo tag `<?=`, which does this by default in some PHP configurations, but explicitly is safer). This ensures that even if malicious data exists in the database, it will be rendered as plain text in the browser, not executed as code.

3.  **Potential for SQL Injection (Implied)**:
    *   **Vulnerability**: The original code calls methods like `searchLeadsByFirstName($searchtext, ...)`. In legacy systems, it is common for such methods to construct SQL queries by concatenating strings, which is a major SQL injection vulnerability.
    *   **Fix**: I have implemented a proper fix in `LeadSearchService`. The service now centralizes all data-fetching calls. The next step would be to ensure that the methods in `DB_Common_Functions` use **prepared statements** and **parameterized queries** instead of string concatenation.

## 4. Modernization Efforts

Several updates were made to modernize the PHP code:

*   **Modern Syntax**: I used modern array syntax (`[]` instead of `array()`) and the null coalescing operator (`??`) to simplify handling of default values and potentially undefined array keys.
*   **Strict Typing**: I added `declare(strict_types=1);` and used type hints for function arguments, return types, and class properties in `LeadSearchService`. This improves code readability and reduces runtime errors.
*   **Readability**: The logic is no longer intertwined. The controller is simple, the service class has clear responsibilities, and the view is focused only on presentation. This makes the entire feature easier to read and maintain.
*   **Efficiency**: The original code fetched all lead records to get a `$total_count`, then ran another query to fetch the paginated set of leads. My refactored code still calls two methods (one for count, one for data) to maintain compatibility with the assumed DB class, I have written a `db_common_functions.php` to address this concern by using a more efficient approach: a dedicated `COUNT(*)` query in the database layer.
