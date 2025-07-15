<?php
require_once 'config.php';

function getAllBorrowers() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM borrowers ORDER BY name");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getBorrowerById($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM borrowers WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function addBorrower($name, $phone, $address) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO borrowers (name, phone, address) VALUES (?, ?, ?)");
    return $stmt->execute([$name, $phone, $address]);
}

function updateBorrower($id, $name, $phone, $address) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE borrowers SET name = ?, phone = ?, address = ? WHERE id = ?");
    return $stmt->execute([$name, $phone, $address, $id]);
}

function deleteBorrower($id) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM borrowers WHERE id = ?");
    return $stmt->execute([$id]);
}

function getLoansByBorrower($borrowerId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM loans WHERE borrower_id = ?");
    $stmt->execute([$borrowerId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function addLoan($borrowerId, $amount, $startDate, $interestRate, $duration, $purpose) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO loans (borrower_id, amount, start_date, interest_rate, duration_months, purpose, remaining_balance) VALUES (?, ?, ?, ?, ?, ?, ?)");
    return $stmt->execute([$borrowerId, $amount, $startDate, $interestRate, $duration, $purpose, $amount]);
}

function getLoanById($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM loans WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function addPayment($loanId, $amount, $paymentDate, $paymentMode, $isInterestPayment = false) {
    global $pdo;
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Add payment record
        $stmt = $pdo->prepare("INSERT INTO payments (loan_id, amount, payment_date, payment_mode, is_interest_payment) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$loanId, $amount, $paymentDate, $paymentMode, $isInterestPayment]);
        
        // Update remaining balance if not interest payment
        if (!$isInterestPayment) {
            $stmt = $pdo->prepare("UPDATE loans SET remaining_balance = remaining_balance - ? WHERE id = ?");
            $stmt->execute([$amount, $loanId]);
        }
        
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}

function calculateInterest($loanId) {
    global $pdo;
    
    // Get loan details
    $loan = getLoanById($loanId);
    if (!$loan) return false;
    
    // Calculate interest
    $interestAmount = ($loan['remaining_balance'] * $loan['interest_rate']) / 100;
    
    // Determine the month for which we're calculating interest
    $stmt = $pdo->prepare("SELECT MAX(for_month) as last_month FROM interest_records WHERE loan_id = ?");
    $stmt->execute([$loanId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $forMonth = date('Y-m-01');
    if ($result['last_month']) {
        $forMonth = date('Y-m-01', strtotime($result['last_month'] . ' +1 month'));
    } else {
        $forMonth = date('Y-m-01', strtotime($loan['start_date']));
    }
    
    // Record the interest
    $stmt = $pdo->prepare("INSERT INTO interest_records (loan_id, interest_amount, for_month) VALUES (?, ?, ?)");
    return $stmt->execute([$loanId, $interestAmount, $forMonth]);
}

function getInterestRecords($loanId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM interest_records WHERE loan_id = ? ORDER BY for_month");
    $stmt->execute([$loanId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function markInterestAsPaid($interestId) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE interest_records SET is_paid = TRUE WHERE id = ?");
    return $stmt->execute([$interestId]);
}

function getPaymentsByLoan($loanId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM payments WHERE loan_id = ? ORDER BY payment_date DESC");
    $stmt->execute([$loanId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function addNote($borrowerId, $note, $reminderDate = null) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO notes (borrower_id, note, reminder_date) VALUES (?, ?, ?)");
    return $stmt->execute([$borrowerId, $note, $reminderDate]);
}

function getNotesByBorrower($borrowerId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM notes WHERE borrower_id = ? ORDER BY created_at DESC");
    $stmt->execute([$borrowerId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>