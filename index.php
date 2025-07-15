<?php
require_once 'includes/header.php';
require_once 'includes/functions.php';

$borrowers = getAllBorrowers();
?>

<h2 class="mb-4">Dashboard</h2>

<div class="row">
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Recent Borrowers</h5>
            </div>
            <div class="card-body">
                <?php if (count($borrowers) > 0): ?>
                    <div class="list-group">
                        <?php foreach (array_slice($borrowers, 0, 5) as $borrower): ?>
                            <a href="borrowers.php?action=view&id=<?= $borrower['id'] ?>" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?= htmlspecialchars($borrower['name']) ?></h6>
                                    <small><?= htmlspecialchars($borrower['phone']) ?></small>
                                </div>
                                <p class="mb-1 text-muted"><?= htmlspecialchars(substr($borrower['address'], 0, 50)) ?>...</p>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <a href="borrowers.php" class="btn btn-sm btn-outline-primary mt-3">View All Borrowers</a>
                <?php else: ?>
                    <p class="text-muted">No borrowers found.</p>
                    <a href="borrowers.php?action=add" class="btn btn-primary">Add First Borrower</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">Recent Loans</h5>
            </div>
            <div class="card-body">
                <?php
                $recentLoans = [];
                foreach ($borrowers as $borrower) {
                    $loans = getLoansByBorrower($borrower['id']);
                    foreach ($loans as $loan) {
                        $loan['borrower_name'] = $borrower['name'];
                        $recentLoans[] = $loan;
                    }
                }
                
                // Sort by date
                usort($recentLoans, function($a, $b) {
                    return strtotime($b['start_date']) - strtotime($a['start_date']);
                });
                ?>
                
                <?php if (count($recentLoans) > 0): ?>
                    <div class="list-group">
                        <?php foreach (array_slice($recentLoans, 0, 5) as $loan): ?>
                            <a href="loans.php?action=view&id=<?= $loan['id'] ?>" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?= htmlspecialchars($loan['borrower_name']) ?></h6>
                                    <small>₹<?= number_format($loan['amount'], 2) ?></small>
                                </div>
                                <p class="mb-1 text-muted">
                                    <?= date('M d, Y', strtotime($loan['start_date'])) ?> • 
                                    <?= $loan['interest_rate'] ?>% • 
                                    Remaining: ₹<?= number_format($loan['remaining_balance'], 2) ?>
                                </p>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <a href="loans.php" class="btn btn-sm btn-outline-success mt-3">View All Loans</a>
                <?php else: ?>
                    <p class="text-muted">No loans found.</p>
                    <a href="loans.php?action=add" class="btn btn-success">Add First Loan</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">Upcoming Payments</h5>
            </div>
            <div class="card-body">
                <?php
                $upcomingInterests = [];
                foreach ($recentLoans as $loan) {
                    $interests = getInterestRecords($loan['id']);
                    foreach ($interests as $interest) {
                        if (!$interest['is_paid']) {
                            $interest['loan'] = $loan;
                            $upcomingInterests[] = $interest;
                        }
                    }
                }
                ?>
                
                <?php if (count($upcomingInterests) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Borrower</th>
                                    <th>Loan Amount</th>
                                    <th>Interest For</th>
                                    <th>Amount</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($upcomingInterests as $interest): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($interest['loan']['borrower_name']) ?></td>
                                        <td>₹<?= number_format($interest['loan']['amount'], 2) ?></td>
                                        <td><?= date('F Y', strtotime($interest['for_month'])) ?></td>
                                        <td>₹<?= number_format($interest['interest_amount'], 2) ?></td>
                                        <td>
                                            <a href="payments.php?action=add_interest&loan_id=<?= $interest['loan']['id'] ?>&interest_id=<?= $interest['id'] ?>" class="btn btn-sm btn-success">
                                                <i class="bi bi-check-circle"></i> Mark Paid
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No upcoming payments found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>