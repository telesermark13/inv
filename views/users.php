<?php
// views/users.php

// Session should already be started in auth.php, but for safety:
if (session_status() == PHP_SESSION_NONE) session_start();

if (!isset($conn)) {
    die("Database connection not established");
}

// Ensure CSRF token exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle all CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed.");
    }

    // Add new user - Only admin allowed
    if (isset($_POST['add_user']) && $_SESSION['role'] === 'admin') {
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'];
        $role = in_array($_POST['role'], ['admin', 'manager', 'client']) ? $_POST['role'] : 'client';

        if (strlen($password) < 8) {
            $_SESSION['error'] = "Password must be at least 8 characters";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $username, $email, $hashed_password, $role);

            if ($stmt->execute()) {
                $_SESSION['success'] = "User added successfully";
            } else {
                $_SESSION['error'] = "Error adding user: " . $conn->error;
            }
        }
    }
    // Update existing user - Only admin allowed to change role
    elseif (isset($_POST['update_user'])) {
        $id = intval($_POST['id']);
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);

        // Only admin can update the role
        if ($_SESSION['role'] === 'admin') {
            $role = in_array($_POST['role'], ['admin', 'manager', 'client']) ? $_POST['role'] : 'client';
        } else {
            // If not admin, can't change role—keep previous
            $user_row = $conn->query("SELECT role FROM users WHERE id = $id")->fetch_assoc();
            $role = $user_row ? $user_row['role'] : 'client';
        }

        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, password = ?, role = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $username, $email, $password, $role, $id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?");
            $stmt->bind_param("sssi", $username, $email, $role, $id);
        }

        if ($stmt->execute()) {
            $_SESSION['success'] = "User updated successfully";
        } else {
            $_SESSION['error'] = "Error updating user: " . $conn->error;
        }
    }

    header("Location: users.php");
    exit;
}

// Handle delete action - Only admin allowed
if (isset($_GET['delete']) && $_SESSION['role'] === 'admin') {
    $id = intval($_GET['delete']);
    $has_requests = $conn->query("SELECT COUNT(*) FROM materials_requests WHERE user_id = $id")->fetch_row()[0];
    $has_deliveries = $conn->query("SELECT COUNT(*) FROM delivery_receipts WHERE prepared_by = $id")->fetch_row()[0];
    $has_movements = $conn->query("SELECT COUNT(*) FROM inventory_movements WHERE user_id = $id")->fetch_row()[0];

    if ($has_requests > 0 || $has_deliveries > 0 || $has_movements > 0) {
        $_SESSION['error'] = "Cannot delete user because they have associated records in the system.";
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $_SESSION['success'] = "User deleted successfully";
        } else {
            $_SESSION['error'] = "Error deleting user: " . $conn->error;
        }
    }
    header("Location: users.php");
    exit;
}

// Fetch all users for the table
$users = $conn->query("SELECT id, username, email, role FROM users ORDER BY username");
?>

<!-- Users Management Card -->
<div class="card shadow">
    <div class="card-header bg-primary text-white">
        <h3 class="card-title mb-0">Users Management</h3>
    </div>
    <div class="card-body">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($_SESSION['success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show"><?= htmlspecialchars($_SESSION['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="d-flex justify-content-between mb-4">
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="fas fa-plus"></i> Add New User
                </button>
            <?php endif; ?>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-hover" id="usersTable">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = $users->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['id']) ?></td>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td>
                                <span class="badge bg-<?= $user['role'] === 'admin' ? 'primary' : ($user['role'] === 'manager' ? 'info' : 'secondary') ?>">
                                    <?= htmlspecialchars(ucfirst($user['role'])) ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <?php if ($_SESSION['role'] === 'admin'): ?>
                                    <button type="button" class="btn btn-sm btn-primary edit-user"
                                        data-id="<?= $user['id'] ?>"
                                        data-username="<?= htmlspecialchars($user['username'], ENT_QUOTES) ?>"
                                        data-email="<?= htmlspecialchars($user['email'], ENT_QUOTES) ?>"
                                        data-role="<?= htmlspecialchars($user['role'], ENT_QUOTES) ?>">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <a href="users.php?delete=<?= $user['id'] ?>"
                                        class="btn btn-sm btn-danger"
                                        onclick="return confirm('Are you sure you want to delete this user?')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                <?php else: ?>
                                    <!-- Managers and clients cannot edit or delete users -->
                                    <span class="text-muted">No Actions</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add User Modal (admin only) -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addUserModalLabel">Add New User</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="users.php" class="needs-validation" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="add_user" value="1">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" name="username" required>
                            <div class="invalid-feedback">Please enter a username</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required>
                            <div class="invalid-feedback">Please enter a valid email</div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" name="password" minlength="8" required>
                            <div class="invalid-feedback">Password must be at least 8 characters</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Role</label>
                            <select class="form-select" name="role" required>
                                <option value="client">Client</option>
                                <option value="manager">Manager</option>
                                <option value="admin">Admin</option>
                            </select>
                            <div class="invalid-feedback">Please select a role</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="users.php" class="needs-validation" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="update_user" value="1">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" name="username" id="edit_username" required>
                            <div class="invalid-feedback">Please enter a username</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="edit_email" required>
                            <div class="invalid-feedback">Please enter a valid email</div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">New Password</label>
                            <input type="password" class="form-control" name="password" id="edit_password" placeholder="Leave blank to keep current password">
                            <small class="form-text text-muted">Minimum 8 characters if changing</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Role</label>
                            <select class="form-select" name="role" id="edit_role" <?= $_SESSION['role'] !== 'admin' ? 'disabled' : '' ?> required>
                                <option value="client">Client</option>
                                <option value="manager">Manager</option>
                                <option value="admin">Admin</option>
                            </select>
                            <div class="invalid-feedback">Please select a role</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Required JS: Bootstrap 5, jQuery, DataTables -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script>
    $(document).ready(function() {
        $('#usersTable').DataTable({
            responsive: true
        });

        // Show Edit Modal with populated data
        $(document).on('click', '.edit-user', function() {
            $('#edit_id').val($(this).data('id'));
            $('#edit_username').val($(this).data('username'));
            $('#edit_email').val($(this).data('email'));
            $('#edit_role').val($(this).data('role'));
            var editModal = new bootstrap.Modal(document.getElementById('editUserModal'));
            editModal.show();
        });

        // Bootstrap 5 form validation
        (function() {
            var forms = document.querySelectorAll('.needs-validation');
            Array.prototype.slice.call(forms).forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        })();
    });
</script>
