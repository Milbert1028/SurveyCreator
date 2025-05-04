<!-- Link to survey builder stylesheet -->
<link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/survey-builder.css">

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="previewModalLabel">Survey Preview</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="preview-container">
          <h1 id="preview-title" class="text-center mb-3">Survey Title</h1>
          <p id="preview-description" class="text-center text-muted mb-4">Survey description will appear here.</p>
          <div id="preview-questions" class="mt-4">
            <!-- Preview questions will be inserted here -->
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal backdrop fix script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Global handler for all modals to ensure proper cleanup when hidden
  document.addEventListener('hidden.bs.modal', function(event) {
    if (event.target.classList.contains('modal')) {
      setTimeout(function() {
        // Remove any lingering backdrops
        document.querySelectorAll('.modal-backdrop').forEach(function(backdrop) {
          backdrop.remove();
        });
        
        // Ensure body classes and styles are reset
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('overflow');
        document.body.style.removeProperty('padding-right');
      }, 100);
    }
  }, true);

  // Add click handler for modal close buttons to ensure proper cleanup
  document.querySelectorAll('[data-bs-dismiss="modal"]').forEach(function(button) {
    button.addEventListener('click', function() {
      setTimeout(function() {
        document.querySelectorAll('.modal-backdrop').forEach(function(backdrop) {
          backdrop.remove();
        });
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('overflow');
        document.body.style.removeProperty('padding-right');
      }, 150);
    });
  });
});
</script>
