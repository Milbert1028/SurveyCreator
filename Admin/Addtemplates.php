<?php include '../includes/header.php'; ?>
<?php include '../includes/nav.php'; ?>

<main class="admin-main">
    <h1>Add Template</h1>
    <form>
        <label for="template-name">Template Name</label>
        <input type="text" id="template-name" name="template_name">

        <label for="template-content">Template Content</label>
        <textarea id="template-content" name="template_content"></textarea>

        <button type="submit" class="btn btn-primary">Save Template</button>
    </form>
</main>

<!-- Modal Structure -->
<div class="modal" id="template-modal">
    <div class="modal-content">
        <h2>Edit Template</h2>
        <form>
            <label for="template-name-modal">Template Name</label>
            <input type="text" id="template-name-modal" name="template_name_modal">
            
            <label for="template-content-modal">Template Content</label>
            <textarea id="template-content-modal" name="template_content_modal"></textarea>
            
            <button type="submit" class="btn btn-primary">Save</button>
            <button type="button" class="btn btn-close">Close</button>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
