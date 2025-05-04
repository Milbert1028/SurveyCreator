<?php include '../includes/header.php'; ?>
<?php include '../includes/nav.php'; ?>

<main class="admin-main">
    <h1>Analytics</h1>
    <div id="chart-container">
        <canvas id="survey-analytics"></canvas>
    </div>
    <div id="user-stats">
        <h2>User Statistics</h2>
        <p>Total Users: 100</p>
        <p>Active Users: 80</p>
    </div>
</main>

<!-- Modal Structure -->
<div class="modal" id="analytics-modal">
    <div class="modal-content">
        <h2>Analytics Details</h2>
        <form>
            <label for="total-users-analytics">Total Users</label>
            <input type="number" id="total-users-analytics" name="total_users_analytics">
            
            <label for="active-users-analytics">Active Users</label>
            <input type="number" id="active-users-analytics" name="active_users_analytics">
            
            <button type="submit" class="btn btn-primary">Save</button>
            <button type="button" class="btn btn-close">Close</button>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
