<?php
require_once dirname(__FILE__) . "/../app/include/config.php"; //session and ini_set
require_once dirname(__FILE__) . "/../app/models/lib/classes/db_common_functions.php";
require_once dirname(__FILE__) . "/../app/models/lib/classes/common_utilities.php";
require_once dirname(__FILE__) . "/../app/helpers/obj-question.php";
require_once dirname(__FILE__) . "/../app/include/secure-check.php"; //secure check
require_once dirname(__FILE__) . "/../app/include/language.inc.php"; //defined values


$comm_func = new DB_Common_Functions();
$util = new Common_Utilities();

$title = "Search Leads";

$agent_id = $_SESSION['admin_id'];

$searchby = "fname";
if(isset($_REQUEST['searchValue']) && $_REQUEST['searchValue'] != "") {
    $searchby = trim($_REQUEST['searchValue']);
}

$searchtext = "";
if( isset($_REQUEST['searchText']) && $_REQUEST['searchText'] != "" ) {
    $searchtext = $util->sanitize_html(trim($_REQUEST['searchText']));
}

if(isset($_REQUEST['searchBtn'])) {
    $table_name = "log_agent_searches";
    $searchcriteriatext = $comm_func->getSearchCriteriaText($searchby);
    $data = array($agent_id, $searchtext, $searchcriteriatext);
    $fields = array('agent_id', 'search_value', 'search_criteria');
    $comm_func->store_data($data, $fields, $table_name);
}

$owner_id = $_SESSION['owner_id'];
$leads = array();

$connstr = "";
if($searchtext !== "") {
    switch($searchby)
    {
        case "fname":
            $leads = $comm_func->searchLeadsByFirstName($searchtext, $owner_id);
            $connstr = "&searchValue=fname&searchText=" . $searchtext;
            break;
        case "lname":
            $leads = $comm_func->searchLeadsByLastName($searchtext, $owner_id);
            $connstr = "&searchValue=lname&searchText=" . $searchtext;
            break;
        case "phone_number":
//            $searchtext = $util->stripPhoneNumber($searchtext);
            $searchtext = $util->formatPhoneNumber($searchtext);
            $leads = $comm_func->searchLeadsByPhone($searchtext, $owner_id);
            $connstr = "&searchValue=phone_number&searchText=" . $searchtext;
            break;
        case "email":
            $searchtext = urldecode($searchtext);
            $leads = $comm_func->searchLeadsByEmail($searchtext, $owner_id);
            $connstr = "&searchValue=email&searchText=" . $searchtext;
            break;
        case "crm_id":
            $leads = $comm_func->searchLeadsByCRMId($searchtext, $owner_id);
            $connstr = "&searchValue=crm_id&searchText=" . $searchtext;
            break;
        case "mkt_id":
            $leads = $comm_func->searchLeadsByMktId($searchtext, $owner_id);
            $connstr = "&searchValue=mkt_id&searchText=" . $searchtext;
            break;

    }
}

//start pagination
$total_count = count($leads);
$total_pages = ceil( $total_count / PAGE_SIZE);

if(isset($_GET['current_page']) && $_GET['current_page'] != 0) {
    $current_page = trim($_GET['current_page']);
} else {
    $current_page = 1;
}

if($current_page > $total_pages) {
    $current_page = $total_pages;
}

$page_link_display_size = LINK_DISPLAY_SIZE;

if(($current_page - LINK_GAP_SIZE) <= 0) {
    $start_page = 1;
    $end_page = $current_page + LINK_GAP_SIZE;

    if( $end_page <= $page_link_display_size) {
        $end_page = $page_link_display_size + 1;
    }
} else {
    $start_page = $current_page - LINK_GAP_SIZE;
    $end_page = $current_page + LINK_GAP_SIZE;
}

if($end_page > $total_pages) {
    $end_page = $total_pages;
}

if( (($end_page - $start_page) + 1) < $page_link_display_size) {
    $start_page = $end_page - $page_link_display_size;
}

if($start_page <= 0) {
    $start_page = 1;
}

$display_clients_headers = 9;

$previouspage = $current_page - 1;
$nextpage = $current_page + 1;

//$current_page == 1 is the first page
$offset = ($current_page - 1) * PAGE_SIZE;

$url = "/search/search_leads.php";

if($searchtext !== "") {
    switch($searchby)
    {
        case "fname":
            $leads = $comm_func->searchLeadsByFirstNameWithOffset($searchtext, $owner_id, $offset);
            break;
        case "lname":
            $leads = $comm_func->searchLeadsByLastNameWithOffset($searchtext, $owner_id, $offset);
            break;
        case "phone_number":
//            $searchtext = $util->stripPhoneNumber($searchtext);
            $searchtext = $util->formatPhoneNumber($searchtext);
            $leads = $comm_func->searchLeadsByPhoneWithOffset($searchtext, $owner_id, $offset);
            break;
        case "email":
            $searchtext = urldecode($searchtext);
            $leads = $comm_func->searchLeadsByEmailWithOffset($searchtext, $owner_id, $offset);
            break;
        case "crm_id":
            $leads = $comm_func->searchLeadsByCRMId($searchtext, $owner_id);
            break;
        case "mkt_id":
            $leads = $comm_func->searchLeadsByMktId($searchtext, $owner_id);
            break;

    }
}



?>
<!doctype html>
<html lang="en">
<head>
    <?php
    include_once dirname(__FILE__) . "/../app/include/meta.php";
    ?>

    <?php
    //css files
    include_once dirname(__FILE__) . "/../app/include/clientcss.php";
    ?>

    <title><?php echo $title; ?></title>

</head>

<body>

<?php
require_once dirname(__FILE__) . '/../app/include/load_navbar.php';

?>

<main role="main" class="container-fluid">

    <?php
        include_once dirname(__FILE__) . "/../app/views/search-leads-view.php";
    ?>

    <div class="row">

        <div class="col-md-1"></div>

        <div class="col-md-10">
            <div class="row">
                <div class="table-responsive">

                    <div class="table-center">
                    <?php
                    echo $util->construct_pagination($url, $display_clients_headers, $current_page, $previouspage, $nextpage, $start_page, $end_page, $total_pages, $connstr);
                    ?>
                    </div>

                    <table class="table table-sm table-hover table-bordered" width="98%">
                        <thead>
                        <tr class="light-blue-bg-1 center-text">
                            <th>Date</th>
                            <th>Name</th>
                            <th>Main Phone</th>
                            <th>Second Phone</th>
                            <th>Gender</th>
                            <th>E-Mail</th>
                            <th>City</th>
                            <th>State</th>
                            <th>Office</th>
                            <th>Status</th>
                        </tr>
                        </thead>

                        <tbody>
                        <?php
                            if(count($leads) > 0 ) {
                                foreach ($leads as $lead) {
                                    if($lead->full_name != "") {
                                        $full_name = $lead->full_name;
                                    } else {
                                        $full_name = $lead->fname . " " .  $lead->lname;
                                    }

                                    $real_date = $lead->real_date;
                                    $real_date = date("m/d/Y", strtotime($real_date));

                                    $phone = $util->formatDisplayPhone($lead->mainPhoneArea, $lead->mainPhone);
                                    $secondphone = "";
                                    if($lead->secondPhoneArea != "" && $lead->secondPhone != "") {
                                        $secondphone = $util->formatDisplayPhone($lead->secondPhoneArea, $lead->secondPhone);
                                    }
                                    printf("<tr>\n");
                                    printf("<td>%s</td>\n",  $real_date);
                                    printf("<td><a href='/leads/lead_details.php?crm_lead_id=%s'>%s</a></td>\n",
                                        $lead->lead_id, $full_name);
                                    printf("<td>%s</td>\n",  $phone);
                                    printf("<td>%s</td>\n",  $secondphone);
                                    printf("<td>%s</td>",  $lead->sex);
                                    printf("<td>%s</td>\n",  $lead->email);
                                    printf("<td>%s</td>",  $lead->city);
                                    printf("<td>%s</td>\n",  $lead->state);
                                    printf("<td>%s</td>\n",  $lead->name);
                                    printf("<td>%s</td>\n",  $lead->current_status);
                                    printf("</tr>\n");
                                }
                            } else {
                                printf("<tr><td colspan='10'>No result found.</td></tr>\n");
                            }
                        ?>
                        </tbody>

                    </table><!-- table -->


                    <div class="table-center">
                    <?php
                    echo $util->construct_pagination($url, $display_clients_headers, $current_page, $previouspage, $nextpage, $start_page, $end_page, $total_pages, $connstr);
                    ?>
                    </div>


                </div><!-- table-responsive -->




            </div>
            <!-- row -->

        </div>
        <!-- col-md-7 -->

        <div class="col-md-1">
            <div class="row">
                <div class="col-md-12">
                    <!-- calendar-->
                    <?php
//                    include_once dirname(__FILE__) . "/../app/views/calendar-view.php";
                    ?>
                </div><!-- col-md-12 -->
            </div><!-- .row -->
            <div class="row">
                <div class="col-md-12">


                </div><!-- col-md-12 -->
            </div><!-- .row -->
        </div><!-- col-md-3 -->

    </div><!-- .row -->


</main><!-- /.container -->

<?php
require_once dirname(__FILE__) . "/../app/include/clientjs.php";
?>
</body>
</html>
