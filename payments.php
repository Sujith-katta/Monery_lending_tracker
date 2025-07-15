<?php
require_once 'includes/header.php';
require_once 'includes/functions.php';

$action = $_GET['action'] ?? 'list';
$loanId = $_GET['loan_id'] ?? null;
$interestId = $_GET['interest_id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add') {
        $loanId = $_POST['loan_id'];
        $amount = $_POST['amount'];
        $paymentDate = $_POST['payment_date'];
        $paymentMode = $_POST['payment_mode'];
        
        if (addPayment($loanId, $amount, $paymentDate, $paymentMode)) {
            $_SESSION['message'] = ['type' => 'success', 'text' => 'Payment added successfully!'];
            header('Location: loans.php?action=view&id=' . $loanId);
            exit;
        } else {
            $_SESSION['message'] = ['type' => 'danger', 'text' => 'Failed to add payment.'];
        }
    } elseif ($action === 'add_interest') {
        $loanId = $_POST['loan_id'];
        $interestId = $_POST['interest_id'];
        
        // Get interest amount
        $stmt = $pdo->prepare("SELECT interest_amount FROM interest_records WHERE id = ?");
        $stmt->execute([$interestId]);
        $interest = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($interest && markInterestAsPaid($interestId)) {
            // Record the interest payment
            addPayment($loanId, $interest['interest_amount'], date('Y-m-d'), 'Cash', true);
            
            $_SESSION['message'] = ['type' => 'success', 'text' => 'Interest marked as paid successfully!'];
            header('Location: loans.php?action=view&id=' . $loanId);
            exit;
        } else {
            $_SESSION['message'] = ['type' => 'danger', 'text' => 'Failed to mark interest as paid.'];
        }
    }
}
?>

<?php if (isset($_SESSION['message'])): ?>
    <div class="alert alert-<?= $_SESSION['message']['type'] ?> alert-dismissible fade show">
        <?= $_SESSION['message']['text'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['message']); ?>
<?php endif; ?>

<?php if ($action === 'add' && $loanId): ?>
    <?php
    $loan = getLoanById($loanId);
    if (!$loan) {
        $_SESSION['message'] = ['type' => 'danger', 'text' => 'Loan not found.'];
        header('Location: loans.php');
        exit;
    }
    
    $borrower = getBorrowerById($loan['borrower_id']);
    ?>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Add Payment</h2>
        <a href="loans.php?action=view&id=<?= $loanId ?>" class="btn btn-outline-primary">
            <i class="bi bi-arrow-left"></i> Back to Loan
        </a>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="mb-4">
                <h5>Loan Details</h5>
                <p class="mb-1">
                    <strong>Borrower:</strong> <?= htmlspecialchars($borrower['name']) ?><br>
                    <strong>Amount:</strong> ₹<?= number_format($loan['amount'], 2) ?><br>
                    <strong>Remaining:</strong> ₹<?= number_format($loan['remaining_balance'], 2) ?>
                </p>
            </div>
            
            <form method="POST">
                <input type="hidden" name="loan_id" value="<?= $loanId ?>">
                
                <div class="mb-3">
                    <label for="amount" class="form-label">Amount (₹)</label>
                    <input type="number" class="form-control" id="amount" name="amount" step="0.01" min="0" max="<?= $loan['remaining_balance'] ?>" required>
                    <div class="form-text">Maximum: ₹<?= number_format($loan['remaining_balance'], 2) ?></div>
                </div>
                <div class="mb-3">
                    <label for="payment_date" class="form-label">Payment Date</label>
                    <input type="date" class="form-control" id="payment_date" name="payment_date" required value="<?= date('Y-m-d') ?>">
                </div>
                <div class="mb-3">
                    <label for="payment_mode" class="form-label">Payment Mode</label>
                    <select class="form-select" id="payment_mode" name="payment_mode" required>
                        <option value="Cash">Cash</option>
                        <option value="UPI">UPI</option>
                        <option value="Bank">Bank Transfer</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="loans.php?action=view&id=<?= $loanId ?>" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>

<?php elseif ($action === 'add_interest' && $loanId && $interestId): ?>
    <?php
    $loan = getLoanById($loanId);
    if (!$loan) {
        $_SESSION['message'] = ['type' => 'danger', 'text' => 'Loan not found.'];
        header('Location: loans.php');
        exit;
    }
    
    $borrower = getBorrowerById($loan['borrower_id']);
    
    $stmt = $pdo->prepare("SELECT * FROM interest_records WHERE id = ?");
    $stmt->execute([$interestId]);
    $interest = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$interest) {
        $_SESSION['message'] = ['type' => 'danger', 'text' => 'Interest record not found.'];
        header('Location: loans.php?action=view&id=' . $loanId);
        exit;
    }
    ?>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Mark Interest as Paid</h2>
        <a href="loans.php?action=view&id=<?= $loanId ?>" class="btn btn-outline-primary">
            <i class="bi bi-arrow-left"></i> Back to Loan
        </a>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="mb-4">
                <h5>Loan Details</h5>
                <p class="mb-1">
                    <strong>Borrower:</strong> <?= htmlspecialchars($borrower['name']) ?><br>
                    <strong>Amount:</strong> ₹<?= number_format($loan['amount'], 2) ?><br>
                    <strong>Remaining:</strong> ₹<?= number_format($loan['remaining_balance'], 2) ?>
                </p>
            </div>
            
            <div class="alert alert-info mb-4">
                <h5>Interest Details</h5>
                <p class="mb-0">
                    <strong>For Month:</strong> <?= date('F Y', strtotime($interest['for_month'])) ?><br>
                    <strong>Amount:</strong> ₹<?= number_format($interest['interest_amount'], 2) ?>
                </p>
            </div>
            
            <form method="POST">
                <input type="hidden" name="loan_id" value="<?= $loanId ?>">
                <input type="hidden" name="interest_id" value="<?= $interestId ?>">
                
                <div class="mb-3">
                    <label for="payment_date" class="form-label">Payment Date</label>
                    <input type="date" class="form-control" id="payment_date" name="payment_date" required value="<?= date('Y-m-d') ?>">
                </div>
                <div class="mb-3">
                    <label for="payment_mode" class="form-label">Payment Mode</label>
                    <select class="form-select" id="payment_mode" name="payment_mode" required>
                        <option value="Cash">Cash</option>
                        <option value="UPI">UPI</option>
                        <option value="Bank">Bank Transfer</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Mark as Paid</button>
                <a href="loans.php?action=view&id=<?= $loanId ?>" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>