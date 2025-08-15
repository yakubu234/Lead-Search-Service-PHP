<?php
require_once dirname(__FILE__) . "/../app/include/config.php"; //session and ini_set
require_once dirname(__FILE__) . "/../app/models/lib/classes/db_common_functions.php";
require_once dirname(__FILE__) . "/../app/models/lib/classes/common_utilities.php";
require_once dirname(__FILE__) . "/../app/helpers/obj-question.php";
require_once dirname(__FILE__) . "/../app/include/secure-check.php"; //secure check
require_once dirname(__FILE__) . "/../app/include/language.inc.php"; //defined values
require_once dirname(__FILE__) . "/LeadSearchService.php"; // The new service class

// --- Dependency Initialization ---
$comm_func = new DB_Common_Functions();
$util = new Common_Utilities();

// --- Controller Logic ---
$title = "Search Leads";

// Instantiate the service and pass dependencies
$searchService = new LeadSearchService($comm_func, $util);

// Handle the request and execute the search
// Using $_GET as it's more appropriate for search parameters that can be bookmarked
$searchService->handleRequest($_GET, $_SESSION);
$searchService->executeSearch();

// Get the results from the service
$leads = $searchService->getLeads();
$paginationData = $searchService->getPaginationData();

?>
<!doctype html>
<html lang="en">
<head>
    <?php require_once dirname(__FILE__) . "/../app/include/meta.php"; ?>
    <?php require_once dirname(__FILE__) . "/../app/include/clientcss.php"; ?>
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
</head>
<body>

<?php require_once dirname(__FILE__) . '/../app/include/load_navbar.php'; ?>

<main role="main" class="container-fluid">

    <?php
    // This view contains the search form. It is assumed to exist.
    // It should be updated to include 'company_name' as a search option.
    require_once dirname(__FILE__) . "/../app/views/search-leads-view.php";
    ?>

    <?php
    // This new view displays the search results table and pagination.
    require_once dirname(__FILE__) . "/../app/views/search-results-view.php";
    ?>

</main><!-- /.container -->

<?php require_once dirname(__FILE__) . "/../app/include/clientjs.php"; ?>
</body>
</html>
