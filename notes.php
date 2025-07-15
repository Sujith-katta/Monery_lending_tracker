<?php
require_once 'includes/header.php';
require_once 'includes/functions.php';

$action = $_GET['action'] ?? 'list';
$borrowerId = $_GET['borrower_id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add') {
        $borrowerId = $_POST['borrower_id'];
        $note = $_POST['note'];
        $reminderDate = $_POST['reminder_date'] ?: null;
        
        if (addNote($borrowerId, $note, $reminderDate)) {
            $_SESSION['message'] = ['type' => 'success', 'text' => 'Note added successfully!'];
            header('Location: borrowers.php?action=view&id=' . $borrowerId);
            exit;
        } else {
            $_SESSION['message'] = ['type' => 'danger', 'text' => 'Failed to add note.'];
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

<?php if ($action === 'add' && $borrowerId): ?>
    <?php
    $borrower = getBorrowerById($borrowerId);
    if (!$borrower) {
        $_SESSION['message'] = ['type' => 'danger', 'text' => 'Borrower not found.'];
        header('Location: borrowers.php');
        exit;
    }
    ?>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Add Note for <?= htmlspecialchars($borrower['name']) ?></h2>
        <a href="borrowers.php?action=view&id=<?= $borrowerId ?>" class="btn btn-outline-primary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
    
    <div class="card">
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="borrower_id" value="<?= $borrowerId ?>">
                
                <div class="mb-3">
                    <label for="note" class="form-label">Note</label>
                    <textarea class="form-control" id="note" name="note" rows="3" required></textarea>
                </div>
                <div class="mb-3">
                    <label for="reminder_date" class="form-label">Reminder Date (optional)</label>
                    <input type="date" class="form-control" id="reminder_date" name="reminder_date">
                </div>
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="borrowers.php?action=view&id=<?= $borrowerId ?>" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>