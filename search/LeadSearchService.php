<?php

declare(strict_types=1);

class LeadSearchService
{
    private const DEFAULT_SEARCH_BY = 'fname';
    private const ALLOWED_SEARCH_VALUES = [
        'fname',
        'lname',
        'phone_number',
        'email',
        'crm_id',
        'mkt_id',
        'company_name', // New criterion
    ];

    private DB_Common_Functions $db;
    private Common_Utilities $util;

    private string $searchBy;
    private string $searchText;
    private int $currentPage;
    private int $ownerId;
    private int $agentId;

    private array $leads = [];
    private int $totalCount = 0;

    public function __construct(DB_Common_Functions $db, Common_Utilities $util)
    {
        $this->db = $db;
        $this->util = $util;
    }

    public function handleRequest(array $request, array $session): void
    {
        $this->ownerId = (int)($session['owner_id'] ?? 0);
        $this->agentId = (int)($session['admin_id'] ?? 0);

        $searchBy = trim($request['searchValue'] ?? self::DEFAULT_SEARCH_BY);
        $this->searchBy = in_array($searchBy, self::ALLOWED_SEARCH_VALUES, true) ? $searchBy : self::DEFAULT_SEARCH_BY;

        // Sanitize searchText to prevent XSS. Using htmlspecialchars is a good practice.
        $this->searchText = htmlspecialchars(trim($request['searchText'] ?? ''), ENT_QUOTES, 'UTF-8');

        $this->currentPage = (int)($request['current_page'] ?? 1);
        if ($this->currentPage < 1) {
            $this->currentPage = 1;
        }
    }

    public function executeSearch(): void
    {
        if ($this->searchText === '' || $this->ownerId === 0) {
            return;
        }

        // Log the search action if a search is being performed
        if (isset($_REQUEST['searchBtn'])) { // Using $_REQUEST as in original for this specific check
            $this->logSearch();
        }

        $this->totalCount = $this->fetchTotalCount();
        $this->leads = $this->fetchLeads();
    }

    public function getLeads(): array
    {
        return $this->leads;
    }

    public function getPaginationData(): array
    {
        if ($this->totalCount === 0) {
            return [
                'total_pages' => 0,
                'current_page' => 1,
                'connstr' => '',
                'pagination_html' => ''
            ];
        }

        $totalPages = (int)ceil($this->totalCount / PAGE_SIZE);
        if ($this->currentPage > $totalPages) {
            $this->currentPage = $totalPages;
        }

        // The complex pagination link calculation from the original script
        $pageLinkDisplaySize = LINK_DISPLAY_SIZE;
        $linkGapSize = LINK_GAP_SIZE;

        if (($this->currentPage - $linkGapSize) <= 0) {
            $startPage = 1;
            $endPage = $this->currentPage + $linkGapSize;
            if ($endPage <= $pageLinkDisplaySize) {
                $endPage = $pageLinkDisplaySize + 1;
            }
        } else {
            $startPage = $this->currentPage - $linkGapSize;
            $endPage = $this->currentPage + $linkGapSize;
        }

        if ($endPage > $totalPages) {
            $endPage = $totalPages;
        }
        if ((($endPage - $startPage) + 1) < $pageLinkDisplaySize) {
            $startPage = $endPage - $pageLinkDisplaySize;
        }
        if ($startPage <= 0) {
            $startPage = 1;
        }

        $previousPage = $this->currentPage - 1;
        $nextPage = $this->currentPage + 1;

        $connstr = sprintf("&searchValue=%s&searchText=%s", urlencode($this->searchBy), urlencode($this->searchText));

        $paginationHtml = $this->util->construct_pagination(
            "/search/search_leads.php",
            9, // display_clients_headers from original
            $this->currentPage,
            $previousPage,
            $nextPage,
            $startPage,
            $endPage,
            $totalPages,
            $connstr
        );

        return [
            'total_pages' => $totalPages,
            'current_page' => $this->currentPage,
            'connstr' => $connstr,
            'pagination_html' => $paginationHtml
        ];
    }

    private function logSearch(): void
    {
        if ($this->agentId > 0 && $this->searchText !== '') {
            $searchCriteriaText = $this->db->getSearchCriteriaText($this->searchBy);
            $this->db->store_data(
                [$this->agentId, $this->searchText, $searchCriteriaText],
                ['agent_id', 'search_value', 'search_criteria'],
                'log_agent_searches'
            );
        }
    }

    private function fetchTotalCount(): int
    {
        $leads = $this->dispatchSearchCall(false);
        return count($leads);
    }

    private function fetchLeads(): array
    {
        return $this->dispatchSearchCall(true);
    }

    private function dispatchSearchCall(bool $withOffset): array
    {
        $searchText = $this->searchText; // Use the sanitized version
        $offset = ($this->currentPage - 1) * PAGE_SIZE;

        // Note: The original code modifies $searchtext inside the switch.
        // This is a side effect that should be avoided, but I replicate it for compatibility.
        $phoneSearchText = $this->util->formatPhoneNumber($searchText);
        $emailSearchText = urldecode($searchText);

        switch ($this->searchBy) {
            case 'fname':
                return $withOffset
                    ? $this->db->searchLeadsByFirstNameWithOffset($searchText, $this->ownerId, $offset)
                    : $this->db->searchLeadsByFirstName($searchText, $this->ownerId);
            case 'lname':
                return $withOffset
                    ? $this->db->searchLeadsByLastNameWithOffset($searchText, $this->ownerId, $offset)
                    : $this->db->searchLeadsByLastName($searchText, $this->ownerId);
            case 'phone_number':
                return $withOffset
                    ? $this->db->searchLeadsByPhoneWithOffset($phoneSearchText, $this->ownerId, $offset)
                    : $this->db->searchLeadsByPhone($phoneSearchText, $this->ownerId);
            case 'email':
                return $withOffset
                    ? $this->db->searchLeadsByEmailWithOffset($emailSearchText, $this->ownerId, $offset)
                    : $this->db->searchLeadsByEmail($emailSearchText, $this->ownerId);
            case 'crm_id':
                // Original code didn't have an offset version for crm_id
                return $this->db->searchLeadsByCRMId($searchText, $this->ownerId);
            case 'mkt_id':
                // Original code didn't have an offset version for mkt_id
                return $this->db->searchLeadsByMktId($searchText, $this->ownerId);
            case 'company_name':
                // Assuming existence of these methods as per instructions
                return $withOffset
                    ? $this->db->searchLeadsByCompanyNameWithOffset($searchText, $this->ownerId, $offset)
                    : $this->db->searchLeadsByCompanyName($searchText, $this->ownerId);
            default:
                return [];
        }
    }
}
