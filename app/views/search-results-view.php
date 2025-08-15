<?php
/**
 * View for displaying lead search results and pagination.
 *
 * Expects the following variables to be in scope:
 * @var array $leads The array of lead objects to display.
 * @var array $paginationData An array containing pagination details.
 * @var Common_Utilities $util The utility class instance.
 */
?>
<div class="row">
    <div class="col-md-1"></div>
    <div class="col-md-10">
        <div class="row">
            <div class="table-responsive">

                <div class="table-center">
                    <?php echo $paginationData['pagination_html'] ?? ''; ?>
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
                    <?php if (!empty($leads)): ?>
                        <?php foreach ($leads as $lead): ?>
                            <?php
                                $fullName = $lead->full_name ?: ($lead->fname . " " . $lead->lname);
                                $realDate = date("m/d/Y", strtotime($lead->real_date));
                                $phone = $util->formatDisplayPhone($lead->mainPhoneArea, $lead->mainPhone);
                                $secondPhone = '';
                                if (!empty($lead->secondPhoneArea) && !empty($lead->secondPhone)) {
                                    $secondPhone = $util->formatDisplayPhone($lead->secondPhoneArea, $lead->secondPhone);
                                }
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($realDate, ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <a href="/leads/lead_details.php?crm_lead_id=<?= (int)$lead->lead_id ?>">
                                        <?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                </td>
                                <td><?= htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($secondPhone, ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($lead->sex, ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($lead->email, ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($lead->city, ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($lead->state, ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($lead->name, ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($lead->current_status, ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10">No result found.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>

                <div class="table-center">
                    <?php echo $paginationData['pagination_html'] ?? ''; ?>
                </div>

            </div>
        </div>
    </div>
    <div class="col-md-1">
        <!-- Right side column from original file -->
    </div>
</div>
