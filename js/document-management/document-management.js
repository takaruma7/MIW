/**
 * Document Management Module
 * Handles document upload, validation, and preview functionality for MIW Travel
 */
const DocumentManager = {
    config: {
        uploadEndpoint: 'handle_document_upload.php',
        maxFileSize: 2097152, // 2MB in bytes
        allowedExtensions: ['pdf', 'jpg', 'jpeg', 'png'],
        documentTypes: ['ktp', 'kk', 'paspor', 'meningitis', 'yellow_card', 'foto']
    },
    
    // Cache for DOM elements
    elements: {},
    
    /**
     * Initialize the document management system
     */
    init: function() {
        console.log('Initializing DocumentManager...');
        
        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                this.setupEventListeners();
                this.cacheElements();
            });
        } else {
            this.setupEventListeners();
            this.cacheElements();
        }
        
        return true;
    },
    
    /**
     * Cache frequently used DOM elements
     */
    cacheElements: function() {
        this.elements.modal = document.getElementById('documentModal');
        this.elements.form = document.getElementById('documentForm');
        this.elements.uploadBtn = document.getElementById('uploadDocumentsBtn');
        this.elements.progressContainer = document.getElementById('uploadProgress');
        this.elements.jamaahNik = document.getElementById('jamaahNik');
        this.elements.jamaahName = document.getElementById('jamaahName');
    },
    
    /**
     * Set up all event listeners for document management
     */
    setupEventListeners: function() {
        console.log('Setting up DocumentManager event listeners...');
        
        // Use event delegation for dynamic elements
        document.addEventListener('click', (e) => {
            // Handle open document modal buttons
            if (e.target.matches('[data-action="open-document-modal"]') || 
                e.target.closest('[data-action="open-document-modal"]')) {
                const button = e.target.matches('[data-action="open-document-modal"]') ? 
                              e.target : e.target.closest('[data-action="open-document-modal"]');
                const nik = button.dataset.nik;
                const name = button.dataset.name;
                this.openDocumentModal(nik, name);
            }
            
            // Handle upload button
            if (e.target.matches('#uploadDocumentsBtn')) {
                e.preventDefault();
                this.uploadDocuments();
            }
        });
        
        // Add event listeners for file inputs with validation
        document.addEventListener('change', (e) => {
            if (this.config.documentTypes.includes(e.target.id)) {
                this.validateFile(e.target);
            }
        });
        
        // Handle form submission
        document.addEventListener('submit', (e) => {
            if (e.target.matches('#documentForm')) {
                e.preventDefault();
                if (e.target.checkValidity()) {
                    this.uploadDocuments();
                }
            }
        });
        
        console.log('DocumentManager event listeners set up successfully');
    },
    
    /**
     * Open document management modal
     * @param {string} nik - Jamaah NIK
     * @param {string} name - Jamaah name
     */
    openDocumentModal: function(nik, name) {
        if (!nik || !name) {
            console.error('NIK and name are required to open document modal');
            return;
        }
        
        // Set NIK and name in form
        if (this.elements.jamaahNik) {
            this.elements.jamaahNik.value = nik;
        }
        if (this.elements.jamaahName) {
            this.elements.jamaahName.textContent = name;
        }
        
        // Reset form and validation messages
        this.resetForm();
        
        // Fetch existing documents
        this.fetchDocuments(nik);
        
        // Show modal
        if (this.elements.modal && typeof bootstrap !== 'undefined') {
            const modal = new bootstrap.Modal(this.elements.modal);
            modal.show();
        }
    },
    
    /**
     * Reset form and clear validation messages
     */
    resetForm: function() {
        if (this.elements.form) {
            this.elements.form.reset();
        }
        
        // Remove validation classes and messages
        document.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
        document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        
        // Hide progress container
        if (this.elements.progressContainer) {
            this.elements.progressContainer.classList.add('d-none');
        }
    },
    
    /**
     * Fetch documents for a jamaah
     * @param {string} nik - Jamaah NIK
     */
    fetchDocuments: function(nik) {
        // Reset preview containers
        document.querySelectorAll('.document-preview').forEach(container => {
            container.innerHTML = '<div class="text-muted small">No file uploaded</div>';
        });
        
        // Fetch documents from server
        fetch(`${this.config.uploadEndpoint}?action=get_documents&nik=${encodeURIComponent(nik)}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Update preview for each document type
                    Object.entries(data.data).forEach(([docType, filePath]) => {
                        if (filePath) {
                            this.updateDocumentPreview(docType, filePath);
                        }
                    });
                } else {
                    console.error('Error fetching documents:', data.message);
                }
            })
            .catch(error => {
                console.error('Error fetching documents:', error);
            });
    },
    
    /**
     * Update document preview container
     * @param {string} docType - Document type
     * @param {string} filePath - File path
     */
    updateDocumentPreview: function(docType, filePath) {
        const previewContainer = document.getElementById(`${docType}_preview`);
        if (!previewContainer) return;
        
        const filename = filePath.split('/').pop();
        
        // Create button container
        const buttonContainer = document.createElement('div');
        buttonContainer.className = 'd-flex gap-2 mb-2';
        
        // Create preview button
        const previewBtn = document.createElement('button');
        previewBtn.type = 'button';
        previewBtn.className = 'btn btn-sm btn-outline-primary';
        previewBtn.innerHTML = '<i class="bi bi-eye"></i> Preview';
        previewBtn.onclick = () => this.previewFile(filePath, 'documents');
        
        // Create download button
        const downloadBtn = document.createElement('button');
        downloadBtn.type = 'button';
        downloadBtn.className = 'btn btn-sm btn-outline-success';
        downloadBtn.innerHTML = '<i class="bi bi-download"></i> Download';
        downloadBtn.onclick = () => this.downloadFile(filePath, 'documents');
        
        // Create file info
        const fileInfo = document.createElement('div');
        fileInfo.className = 'small text-muted';
        fileInfo.textContent = filename;
        
        // Update container
        buttonContainer.appendChild(previewBtn);
        buttonContainer.appendChild(downloadBtn);
        
        previewContainer.innerHTML = '';
        previewContainer.appendChild(buttonContainer);
        previewContainer.appendChild(fileInfo);
    },
    
    /**
     * Validate a file input
     * @param {HTMLElement} fileInput - The file input element
     * @returns {boolean} - Whether the file is valid
     */
    validateFile: function(fileInput) {
        if (!fileInput || !fileInput.files || !fileInput.files[0]) {
            return true; // No file selected
        }
        
        const file = fileInput.files[0];
        const parent = fileInput.parentElement;
        
        // Remove existing validation messages
        const existingFeedback = parent.querySelector('.invalid-feedback');
        if (existingFeedback) {
            existingFeedback.remove();
        }
        fileInput.classList.remove('is-invalid');
        
        let isValid = true;
        let errorMessage = '';
        
        // Check file size
        if (file.size > this.config.maxFileSize) {
            isValid = false;
            errorMessage = `File size exceeds 2MB limit (${(file.size / 1024 / 1024).toFixed(2)}MB)`;
        }
        
        // Check file extension
        const extension = file.name.split('.').pop().toLowerCase();
        if (!this.config.allowedExtensions.includes(extension)) {
            isValid = false;
            errorMessage = `Invalid file type. Allowed: ${this.config.allowedExtensions.join(', ')}`;
        }
        
        // Show validation feedback
        if (!isValid) {
            fileInput.classList.add('is-invalid');
            const feedback = document.createElement('div');
            feedback.className = 'invalid-feedback';
            feedback.textContent = errorMessage;
            parent.appendChild(feedback);
        }
        
        return isValid;
    },
    
    /**
     * Upload documents
     */
    uploadDocuments: function() {
        // Validate all files first
        let isValid = true;
        const hasFiles = this.config.documentTypes.some(docType => {
            const fileInput = document.getElementById(docType);
            if (fileInput && fileInput.files && fileInput.files[0]) {
                if (!this.validateFile(fileInput)) {
                    isValid = false;
                }
                return true;
            }
            return false;
        });
        
        if (!hasFiles) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Files Selected',
                    text: 'Please select at least one file to upload.',
                    confirmButtonColor: '#ffc107'
                });
            } else {
                alert('Please select at least one file to upload.');
            }
            return;
        }
        
        if (!isValid) {
            return; // Stop if validation failed
        }
        
        // Prepare form data
        const formData = new FormData(this.elements.form);
        formData.append('action', 'upload');
        
        // Disable form during upload
        this.setFormState(false);
        
        // Show progress
        this.showProgress();
        
        // Upload files
        fetch(this.config.uploadEndpoint, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            this.handleUploadResponse(data);
        })
        .catch(error => {
            console.error('Upload error:', error);
            this.handleUploadError(error);
        })
        .finally(() => {
            this.setFormState(true);
        });
    },
    
    /**
     * Set form state (enabled/disabled)
     * @param {boolean} enabled - Whether the form should be enabled
     */
    setFormState: function(enabled) {
        if (this.elements.uploadBtn) {
            this.elements.uploadBtn.disabled = !enabled;
        }
        
        const closeButton = document.querySelector('#documentModal .btn-secondary');
        if (closeButton) {
            closeButton.disabled = !enabled;
        }
        
        // Disable/enable file inputs
        this.config.documentTypes.forEach(docType => {
            const fileInput = document.getElementById(docType);
            if (fileInput) {
                fileInput.disabled = !enabled;
            }
        });
    },
    
    /**
     * Show upload progress
     */
    showProgress: function() {
        if (!this.elements.progressContainer) return;
        
        this.elements.progressContainer.classList.remove('d-none');
        const progressBar = this.elements.progressContainer.querySelector('.progress-bar');
        
        if (progressBar) {
            progressBar.style.width = '0%';
            progressBar.textContent = 'Preparing upload...';
            
            // Simulate progress
            let progress = 0;
            const progressInterval = setInterval(() => {
                progress += Math.random() * 15;
                if (progress <= 90) {
                    progressBar.style.width = `${Math.round(progress)}%`;
                    progressBar.textContent = `${Math.round(progress)}% Complete`;
                } else {
                    clearInterval(progressInterval);
                }
            }, 200);
            
            // Store interval reference for cleanup
            this.progressInterval = progressInterval;
        }
    },
    
    /**
     * Handle successful upload response
     * @param {Object} data - Response data
     */
    handleUploadResponse: function(data) {
        // Complete progress bar
        if (this.progressInterval) {
            clearInterval(this.progressInterval);
        }
        
        const progressBar = this.elements.progressContainer?.querySelector('.progress-bar');
        if (progressBar) {
            progressBar.style.width = '100%';
            progressBar.textContent = '100% Complete';
        }
        
        if (data.success) {
            // Show success message
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: data.message || 'Documents uploaded successfully',
                    confirmButtonColor: '#28a745'
                }).then(() => {
                    window.location.reload();
                });
            } else {
                alert(data.message || 'Documents uploaded successfully');
                window.location.reload();
            }
        } else {
            this.handleUploadError(new Error(data.message || 'Upload failed'));
        }
    },
    
    /**
     * Handle upload error
     * @param {Error} error - Error object
     */
    handleUploadError: function(error) {
        // Clear progress interval
        if (this.progressInterval) {
            clearInterval(this.progressInterval);
        }
        
        // Hide progress after delay
        setTimeout(() => {
            if (this.elements.progressContainer) {
                this.elements.progressContainer.classList.add('d-none');
            }
        }, 1000);
        
        // Show error message
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Upload Failed',
                text: error.message || 'An unexpected error occurred. Please try again.',
                confirmButtonColor: '#dc3545'
            });
        } else {
            alert(`Upload failed: ${error.message || 'An unexpected error occurred. Please try again.'}`);
        }
    },
    
    /**
     * Preview a file
     * @param {string} filePath - Path to the file
     * @param {string} type - File type category
     */
    previewFile: function(filePath, type) {
        if (!filePath) return;
        
        const filename = filePath.split('/').pop();
        const url = `file_handler.php?file=${encodeURIComponent(filename)}&type=${type}&action=preview`;
        const extension = filename.split('.').pop().toLowerCase();
        
        if (['pdf', 'jpg', 'jpeg', 'png'].includes(extension)) {
            // Check if preview modal exists
            let modal = document.getElementById('filePreviewModal');
            if (!modal) {
                this.createPreviewModal();
                modal = document.getElementById('filePreviewModal');
            }
            
            const modalBody = modal.querySelector('.modal-body');
            const modalTitle = modal.querySelector('.modal-title');
            
            modalTitle.textContent = `File Preview: ${filename}`;
            
            if (extension === 'pdf') {
                modalBody.innerHTML = `<embed src="${url}" type="application/pdf" width="100%" height="600px">`;
            } else {
                modalBody.innerHTML = `<img src="${url}" class="img-fluid" alt="Preview" style="max-height: 600px;">`;
            }
            
            if (typeof bootstrap !== 'undefined') {
                const bsModal = new bootstrap.Modal(modal);
                bsModal.show();
            }
        } else {
            // Download directly for unsupported files
            this.downloadFile(filePath, type);
        }
    },
    
    /**
     * Create preview modal if it doesn't exist
     */
    createPreviewModal: function() {
        const modalHtml = `
            <div class="modal fade" id="filePreviewModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">File Preview</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body text-center">
                            <!-- Preview content will be inserted here -->
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHtml);
    },
    
    /**
     * Download a file
     * @param {string} filePath - Path to the file
     * @param {string} type - File type category
     */
    downloadFile: function(filePath, type) {
        if (!filePath) return;
        
        const filename = filePath.split('/').pop();
        const url = `file_handler.php?file=${encodeURIComponent(filename)}&type=${type}&action=download`;
        
        // Create temporary link to trigger download
        const link = document.createElement('a');
        link.href = url;
        link.download = filename;
        link.style.display = 'none';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
};

// Global function for backward compatibility
function openDocumentModal(nik, name) {
    DocumentManager.openDocumentModal(nik, name);
}

// Auto-initialize when script loads
DocumentManager.init();

console.log('Document Management Module loaded successfully');
