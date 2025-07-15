<?php
require_once 'includes/header.php';
require_once 'includes/functions.php';

$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add') {
        $name = $_POST['name'];
        $phone = $_POST['phone'];
        $address = $_POST['address'];
        
        if (addBorrower($name, $phone, $address)) {
            $_SESSION['message'] = ['type' => 'success', 'text' => 'Borrower added successfully!'];
            header('Location: borrowers.php');
            exit;
        } else {
            $_SESSION['message'] = ['type' => 'danger', 'text' => 'Failed to add borrower.'];
        }
    } elseif ($action === 'edit' && $id) {
        $name = $_POST['name'];
        $phone = $_POST['phone'];
        $address = $_POST['address'];
        
        if (updateBorrower($id, $name, $phone, $address)) {
            $_SESSION['message'] = ['type' => 'success', 'text' => 'Borrower updated successfully!'];
            header('Location: borrowers.php');
            exit;
        } else {
            $_SESSION['message'] = ['type' => 'danger', 'text' => 'Failed to update borrower.'];
        }
    }
}

if ($action === 'delete' && $id) {
    if (deleteBorrower($id)) {
        $_SESSION['message'] = ['type' => 'success', 'text' => 'Borrower deleted successfully!'];
    } else {
        $_SESSION['message'] = ['type' => 'danger', 'text' => 'Failed to delete borrower.'];
    }
    header('Location: borrowers.php');
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
        <h2>Borrowers</h2>
        <a href="borrowers.php?action=add" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add Borrower
        </a>
    </div>
    
    <div class="card">
        <div class="card-body">
            <?php $borrowers = getAllBorrowers(); ?>
            
            <?php if (count($borrowers) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Phone</th>
                                <th>Address</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($borrowers as $borrower): ?>
                                <tr>
                                    <td><?= htmlspecialchars($borrower['name']) ?></td>
                                    <td><?= htmlspecialchars($borrower['phone']) ?></td>
                                    <td><?= htmlspecialchars(substr($borrower['address'], 0, 50)) ?><?= strlen($borrower['address']) > 50 ? '...' : '' ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="borrowers.php?action=view&id=<?= $borrower['id'] ?>" class="btn btn-outline-primary">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                            <a href="borrowers.php?action=edit&id=<?= $borrower['id'] ?>" class="btn btn-outline-secondary">
                                                <i class="bi bi-pencil"></i> Edit
                                            </a>
                                            <a href="borrowers.php?action=delete&id=<?= $borrower['id'] ?>" class="btn btn-outline-danger" onclick="return confirm('Are you sure?')">
                                                <i class="bi bi-trash"></i> Delete
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
                    No borrowers found. <a href="borrowers.php?action=add" class="alert-link">Add your first borrower</a>.
                </div>
            <?php endif; ?>
        </div>
    </div>

<?php elseif ($action === 'add' || $action === 'edit'): ?>
    <?php
    $borrower = null;
    if ($action === 'edit' && $id) {
        $borrower = getBorrowerById($id);
        if (!$borrower) {
            $_SESSION['message'] = ['type' => 'danger', 'text' => 'Borrower not found.'];
            header('Location: borrowers.php');
            exit;
        }
    }
    ?>
    
    <h2 class="mb-4"><?= $action === 'add' ? 'Add New' : 'Edit' ?> Borrower</h2>
    
    <div class="card">
        <div class="card-body">
            <form method="POST">
                <div class="mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" class="form-control" id="name" name="name" required 
                           value="<?= $borrower ? htmlspecialchars($borrower['name']) : '' ?>">
                </div>
                <div class="mb-3">
                    <label for="phone" class="form-label">Phone Number</label>
                    <input type="tel" class="form-control" id="phone" name="phone" required 
                           value="<?= $borrower ? htmlspecialchars($borrower['phone']) : '' ?>">
                </div>
                <div class="mb-3">
                    <label for="address" class="form-label">Address</label>
                    <textarea class="form-control" id="address" name="address" rows="3"><?= $borrower ? htmlspecialchars($borrower['address']) : '' ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="borrowers.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>

<?php elseif ($action === 'view' && $id): ?>
    <?php
    $borrower = getBorrowerById($id);
    if (!$borrower) {
        $_SESSION['message'] = ['type' => 'danger', 'text' => 'Borrower not found.'];
        header('Location: borrowers.php');
        exit;
    }
    
    $loans = getLoansByBorrower($id);
    $notes = getNotesByBorrower($id);
    ?>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Borrower Details</h2>
        <div class="btn-group">
            <a href="borrowers.php?action=edit&id=<?= $id ?>" class="btn btn-outline-secondary">
                <i class="bi bi-pencil"></i> Edit
            </a>
            <a href="borrowers.php" class="btn btn-outline-primary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Basic Information</h5>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-3">Name</dt>
                        <dd class="col-sm-9"><?= htmlspecialchars($borrower['name']) ?></dd>
                        
                        <dt class="col-sm-3">Phone</dt>
                        <dd class="col-sm-9"><?= htmlspecialchars($borrower['phone']) ?></dd>
                        
                        <dt class="col-sm-3">Address</dt>
                        <dd class="col-sm-9"><?= nl2br(htmlspecialchars($borrower['address'])) ?></dd>
                    </dl>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Notes</h5>
                        <a href="notes.php?action=add&borrower_id=<?= $id ?>" class="btn btn-sm btn-light">
                            <i class="bi bi-plus"></i> Add Note
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (count($notes) > 0): ?>
                        <div class="list-group">
                            <?php foreach ($notes as $note): ?>
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between">
                                        <p class="mb-1"><?= nl2br(htmlspecialchars($note['note'])) ?></p>
                                        <small><?= date('M d, Y', strtotime($note['created_at'])) ?></small>
                                    </div>
                                    <?php if ($note['reminder_date']): ?>
                                        <small class="text-muted">Reminder: <?= date('M d, Y', strtotime($note['reminder_date'])) ?></small>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No notes found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Loans</h5>
                        <a href="loans.php?action=add&borrower_id=<?= $id ?>" class="btn btn-sm btn-light">
                            <i class="bi bi-plus"></i> Add Loan
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (count($loans) > 0): ?>
                        <div class="list-group">
                            <?php foreach ($loans as $loan): ?>
                                <a href="loans.php?action=view&id=<?= $loan['id'] ?>" class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1">₹<?= number_format($loan['amount'], 2) ?></h6>
                                        <small><?= $loan['interest_rate'] ?>%</small>
                                    </div>
                                    <p class="mb-1"><?= date('M d, Y', strtotime($loan['start_date'])) ?></p>
                                    <small class="text-muted">
                                        Remaining: ₹<?= number_format($loan['remaining_balance'], 2) ?>
                                        <?php if ($loan['purpose']): ?>
                                            • <?= htmlspecialchars($loan['purpose']) ?>
                                        <?php endif; ?>
                                    </small>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No loans found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>