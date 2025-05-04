<?php include '../includes/header.php'; ?>
<?php include '../includes/nav.php'; ?>

<main class="admin-main">
    <h1>Settings</h1>
    <form id="settings-form">
        <label for="site-name">Site Name</label>
        <input type="text" id="site-name" name="site_name" value="Survey Creator" required>

        <label for="email">Admin Email</label>
        <input type="email" id="email" name="email" value="admin@example.com" required>

        <label for="site-description">Site Description</label>
        <textarea id="site-description" name="site_description" required></textarea>

        <button type="submit" class="btn btn-primary">Save Settings</button>
    </form>
</main>

<!-- Modal Structure -->
<div class="modal" id="settings-modal">
    <div class="modal-content">
        <h2>Edit Settings</h2>
        <form id="settings-modal-form">
            <label for="site-name-modal">Site Name</label>
            <input type="text" id="site-name-modal" name="site_name_modal" required>
            
            <label for="email-modal">Admin Email</label>
            <input type="email" id="email-modal" name="email_modal" required>
            
            <label for="site-description-modal">Site Description</label>
            <textarea id="site-description-modal" name="site_description_modal" required></textarea>
            
            <button type="submit" class="btn btn-primary">Save</button>
            <button type="button" class="btn btn-close">Close</button>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>