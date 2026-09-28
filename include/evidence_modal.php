<!-- PSG PTC ERP — Interactive Evidence Modal Component -->
<div class="modal fade" id="evidencePreviewModal" tabindex="-1" aria-labelledby="evidencePreviewModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content shadow-lg border-0 rounded-4">
      <div class="modal-header bg-primary text-white py-3 rounded-top-4">
        <h5 class="modal-title fw-bold" id="evidencePreviewModalLabel">
          <i class="bi bi-file-earmark-text me-2"></i>Evidence Preview
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4 text-center">
        <!-- Media Container -->
        <div id="evidenceMediaContainer" class="mb-3 d-flex justify-content-center align-items-center bg-light rounded-3 p-3 min-vh-40 overflow-auto">
          <!-- Dynamically populated iframe or img -->
        </div>

        <!-- Action Controls -->
        <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
          <div>
            <a id="evidenceDownloadBtn" href="#" download class="btn btn-sm btn-outline-success px-3 rounded-pill me-2">
              <i class="bi bi-download me-1"></i> Download File
            </a>
            <?php if (isset($user) && iqac_can_manage_evidence($user)): ?>
            <button id="evidenceReplaceTrigger" type="button" class="btn btn-sm btn-outline-secondary px-3 rounded-pill" onclick="toggleReplaceForm()">
              <i class="bi bi-arrow-repeat me-1"></i> Replace File
            </button>
            <?php endif; ?>
          </div>
          <button type="button" class="btn btn-sm btn-secondary px-4 rounded-pill" data-bs-dismiss="modal">Close</button>
        </div>

        <!-- Hidden Replace Upload Form -->
        <?php if (isset($user) && iqac_can_manage_evidence($user)): ?>
        <div id="evidenceReplaceFormContainer" class="mt-3 p-3 bg-light rounded-3 d-none text-start">
          <h6 class="fw-bold mb-2 text-primary">Upload Replacement Evidence (JPG, PNG, PDF - Max 20MB)</h6>
          <form id="evidenceReplaceForm" enctype="multipart/form-data">
            <input type="hidden" name="action" value="replace">
            <input type="hidden" name="table" id="evidenceFormTable" value="">
            <input type="hidden" name="id" id="evidenceFormId" value="0">
            <input type="hidden" name="category" id="evidenceFormCategory" value="certificates">
            
            <div class="input-group">
              <input type="file" class="form-control" name="evidence_file" id="evidenceFileInput" accept=".jpg,.jpeg,.png,.pdf" required>
              <button class="btn btn-primary" type="submit" id="evidenceSubmitBtn">Upload & Replace</button>
            </div>
            <div id="evidenceUploadStatus" class="form-text mt-2"></div>
          </form>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script>
function openEvidenceModal(url, type, label, table, recordId) {
    var modalLabel = document.getElementById('evidencePreviewModalLabel');
    var mediaContainer = document.getElementById('evidenceMediaContainer');
    var downloadBtn = document.getElementById('evidenceDownloadBtn');
    
    var formTable = document.getElementById('evidenceFormTable');
    var formId = document.getElementById('evidenceFormId');
    var replaceContainer = document.getElementById('evidenceReplaceFormContainer');
    
    if (modalLabel) modalLabel.innerHTML = '<i class="bi bi-file-earmark-text me-2"></i> ' + (label || 'Evidence Document');
    if (downloadBtn) {
        downloadBtn.href = url;
        downloadBtn.setAttribute('download', url.split('/').pop());
    }
    if (formTable) formTable.value = table || '';
    if (formId) formId.value = recordId || 0;
    if (replaceContainer) replaceContainer.classList.add('d-none');

    var ext = type ? type.toLowerCase() : url.split('.').pop().toLowerCase();
    
    if (ext === 'pdf') {
        mediaContainer.innerHTML = '<iframe src="' + url + '" style="width:100%; height:500px; border:none;" class="rounded-3"></iframe>';
    } else {
        mediaContainer.innerHTML = '<img src="' + url + '" class="img-fluid rounded-3 shadow-sm max-vh-70" alt="Evidence Preview">';
    }

    var bsModal = new bootstrap.Modal(document.getElementById('evidencePreviewModal'));
    bsModal.show();
}

function toggleReplaceForm() {
    var container = document.getElementById('evidenceReplaceFormContainer');
    if (container) {
        container.classList.toggle('d-none');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('evidenceReplaceForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(form);
            var statusDiv = document.getElementById('evidenceUploadStatus');
            var submitBtn = document.getElementById('evidenceSubmitBtn');
            
            if (submitBtn) submitBtn.disabled = true;
            if (statusDiv) statusDiv.innerHTML = '<span class="text-info"><i class="bi bi-arrow-repeat spinner-border spinner-border-sm me-1"></i> Uploading file...</span>';

            fetch('upload_evidence.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (submitBtn) submitBtn.disabled = false;
                if (data.success) {
                    if (statusDiv) statusDiv.innerHTML = '<span class="text-success"><i class="bi bi-check-circle me-1"></i> ' + data.message + ' Reloading view...</span>';
                    setTimeout(function() {
                        window.location.reload();
                    }, 1000);
                } else {
                    if (statusDiv) statusDiv.innerHTML = '<span class="text-danger"><i class="bi bi-exclamation-triangle me-1"></i> ' + data.message + '</span>';
                }
            })
            .catch(err => {
                if (submitBtn) submitBtn.disabled = false;
                if (statusDiv) statusDiv.innerHTML = '<span class="text-danger"><i class="bi bi-exclamation-triangle me-1"></i> Upload error occurred.</span>';
            });
        });
    }
});
</script>
