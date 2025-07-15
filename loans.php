<?php
require_once 'includes/header.php';
require_once 'includes/functions.php';

$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$borrowerId = $_GET['borrower_id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add') {
        $borrowerId = $_POST['borrower_id'];
        $amount = $_POST['amount'];
        $startDate = $_POST['start_date'];
        $interestRate = $_POST['interest_rate'];
        $duration = $_POST['duration_months'] ?: null;
        $purpose = $_POST['purpose'] ?: null;
        
        if (addLoan($borrowerId, $amount, $startDate, $interestRate, $duration, $purpose)) {
            $_SESSION['message'] = ['type' => 'success', 'text' => 'Loan added successfully!'];
            header('Location: loans.php?action=view&id=' . $pdo->lastInsertId());
            exit;
        } else {
            $_SESSION['message'] = ['type' => 'danger', 'text' => 'Failed to add loan.'];
        }
    }
}

if ($action === 'calculate_interest' && $id) {
    if (calculateInterest($id)) {
        $_SESSION['message'] = ['type' => 'success', 'text' => 'Interest calculated successfully!'];
    } else {
        $_SESSION['message'] = ['type' => 'danger', 'text' => 'Failed to calculate interest.'];
    }
    header('Location: loans.php?action=view&id=' . $id);
    exit;
}
?>

<?php if (isset($_SESSION['message'])): ?>
    <div class="alert alert-<?= $_SESSION['message']['type'] ?> alert-dismissible fade show">
        <?= $_SESSION['message']['text'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['message']); ?>
<?php endif; ?>

<?php if ($action === 'list'): ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Loans</h2>
        <a href="loans.php?action=add" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add Loan
        </a>
    </div>
    
    <div class="card">
        <div class="card-body">
            <?php
            $allLoans = [];
            $borrowers = getAllBorrowers();
            foreach ($borrowers as $borrower) {
                $loans = getLoansByBorrower($borrower['id']);
                foreach ($loans as $loan) {
                    $loan['borrower_name'] = $borrower['name'];
                    $allLoans[] = $loan;
                }
            }
            ?>
            
            <?php if (count($allLoans) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Borrower</th>
                                <th>Amount</th>
                                <th>Interest Rate</th>
                                <th>Start Date</th>
                                <th>Remaining</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allLoans as $loan): ?>
                                <tr>
                                    <td><?= htmlspecialchars($loan['borrower_name']) ?></td>
                                    <td>₹<?= number_format($loan['amount'], 2) ?></td>
                                    <td><?= $loan['interest_rate'] ?>%</td>
                                    <td><?= date('M d, Y', strtotime($loan['start_date'])) ?></td>
                                    <td>₹<?= number_format($loan['remaining_balance'], 2) ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="loans.php?action=view&id=<?= $loan['id'] ?>" class="btn btn-outline-primary">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    No loans found. <a href="loans.php?action=add" class="alert-link">Add your first loan</a>.
                </div>
            <?php endif; ?>
        </div>
    </div>

<?php elseif ($action === 'add'): ?>
    <h2 class="mb-4">Add New Loan</h2>
    
    <div class="card">
        <div class="card-body">
            <form method="POST">
                <div class="mb-3">
                    <label for="borrower_id" class="form-label">Borrower</label>
                    <select class="form-select" id="borrower_id" name="borrower_id" required>
                        <option value="">Select Borrower</option>
                        <?php foreach (getAllBorrowers() as $borrower): ?>
                            <option value="<?= $borrower['id'] ?>" <?= $borrowerId == $borrower['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($borrower['name']) ?> (<?= htmlspecialchars($borrower['phone']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="amount" class="form-label">Loan Amount (₹)</label>
                    <input type="number" class="form-control" id="amount" name="amount" step="0.01" min="0" required>
                </div>
                <div class="mb-3">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" required>
                </div>
                <div class="mb-3">
                    <label for="interest_rate" class="form-label">Monthly Interest Rate (%)</label>
                    <input type="number" class="form-control" id="interest_rate" name="interest_rate" step="0.01" min="0" required>
                </div>
                <div class="mb-3">
                    <label for="duration_months" class="form-label">Duration (months, optional)</label>
                    <input type="number" class="form-control" id="duration_months" name="duration_months" min="1">
                </div>
                <div class="mb-3">
                    <label for="purpose" class="form-label">Purpose (optional)</label>
                    <textarea class="form-control" id="purpose" name="purpose" rows="2"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="loans.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>

<?php elseif ($action === 'view' && $id): ?>
    <?php
    $loan = getLoanById($id);
    if (!$loan) {
        $_SESSION['message'] = ['type' => 'danger', 'text' => 'Loan not found.'];
        header('Location: loans.php');
        exit;
    }
    
    $borrower = getBorrowerById($loan['borrower_id']);
    $payments = getPaymentsByLoan($id);
    $interestRecords = getInterestRecords($id);
    ?>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Loan Details</h2>
        <div class="btn-group">
            <a href="payments.php?action=add&loan_id=<?= $id ?>" class="btn btn-outline-success">
                <i class="bi bi-cash"></i> Add Payment
            </a>
            <a href="loans.php" class="btn btn-outline-primary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Loan Information</h5>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-4">Borrower</dt>
                        <dd class="col-sm-8">
                            <a href="borrowers.php?action=view&id=<?= $borrower['id'] ?>">
                                <?= htmlspecialchars($borrower['name']) ?>
                            </a>
                        </dd>
                        
                        <dt class="col-sm-4">Amount</dt>
                        <dd class="col-sm-8">₹<?= number_format($loan['amount'], 2) ?></dd>
                        
                        <dt class="col-sm-4">Start Date</dt>
                        <dd class="col-sm-8"><?= date('M d, Y', strtotime($loan['start_date'])) ?></dd>
                        
                        <dt class="col-sm-4">Interest Rate</dt>
                        <dd class="col-sm-8"><?= $loan['interest_rate'] ?>% monthly</dd>
                        
                        <?php if ($loan['duration_months']): ?>
                            <dt class="col-sm-4">Duration</dt>
                            <dd class="col-sm-8"><?= $loan['duration_months'] ?> months</dd>
                        <?php endif; ?>
                        
                        <dt class="col-sm-4">Remaining Balance</dt>
                        <dd class="col-sm-8">₹<?= number_format($loan['remaining_balance'], 2) ?></dd>
                        
                        <?php if ($loan['purpose']): ?>
                            <dt class="col-sm-4">Purpose</dt>
                            <dd class="col-sm-8"><?= htmlspecialchars($loan['purpose']) ?></dd>
                        <?php endif; ?>
                    </dl>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Payments</h5>
                        <a href="payments.php?action=add&loan_id=<?= $id ?>" class="btn btn-sm btn-light">
                            <i class="bi bi-plus"></i> Add Payment
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (count($payments) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Mode</th>
                                        <th>Type</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($payments as $payment): ?>
                                        <tr>
                                            <td><?= date('M d, Y', strtotime($payment['payment_date'])) ?></td>
                                            <td>₹<?= number_format($payment['amount'], 2) ?></td>
                                            <td><?= $payment['payment_mode'] ?></td>
                                            <td>
                                                <?= $payment['is_interest_payment'] ? 'Interest' : 'Principal' ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No payments found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Interest Calculation</h5>
                        <form method="POST" action="loans.php?action=calculate_interest&id=<?= $id ?>">
                            <button type="submit" class="btn btn-sm btn-light">
                                <i class="bi bi-calculator"></i> Calculate Interest
                            </button>
                        </form>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (count($interestRecords) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>For Month</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($interestRecords as $interest): ?>
                                        <tr>
                                            <td><?= date('F Y', strtotime($interest['for_month'])) ?></td>
                                            <td>₹<?= number_format($interest['interest_amount'], 2) ?></td>
                                            <td>
                                                <?php if ($interest['is_paid']): ?>
                                                    <span class="badge bg-success">Paid</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark">Pending</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!$interest['is_paid']): ?>
                                                    <a href="payments.php?action=add_interest&loan_id=<?= $id ?>&interest_id=<?= $interest['id'] ?>" class="btn btn-sm btn-success">
                                                        <i class="bi bi-check-circle"></i> Mark Paid
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <?php
                        $totalInterest = 0;
                        $pendingInterest = 0;
                        foreach ($interestRecords as $interest) {
                            $totalInterest += $interest['interest_amount'];
                            if (!$interest['is_paid']) {
                                $pendingInterest += $interest['interest_amount'];
                            }
                        }
                        ?>
                        
                        <div class="alert alert-info mt-3">
                            <strong>Total Interest:</strong> ₹<?= number_format($totalInterest, 2) ?><br>
                            <strong>Pending Interest:</strong> ₹<?= number_format($pendingInterest, 2) ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No interest records found. Click "Calculate Interest" to calculate for the current month.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>