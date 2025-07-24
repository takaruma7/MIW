<!-- Document Management Modal -->
<div class="modal fade" id="documentModal" tabindex="-1" aria-labelledby="documentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="documentModalLabel">
                    <i class="bi bi-file-earmark-text"></i> Document Management - <span id="jamaahName"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    <strong>Accepted file types:</strong> JPG, JPEG, PNG, PDF (Max size: 2MB per file)
                </div>
                
                <form id="documentForm" enctype="multipart/form-data">
                    <input type="hidden" id="jamaahNik" name="nik">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="bk_kuning" class="form-label">
                                    <i class="bi bi-book"></i> Buku Kuning
                                </label>
                                <div id="bk_kuning_preview" class="document-preview mb-2">
                                    <div class="text-muted">No file uploaded</div>
                                </div>
                                <input type="file" class="form-control" id="bk_kuning" name="bk_kuning" 
                                       accept=".pdf,.jpg,.jpeg,.png">
                            </div>
                            
                            <div class="mb-3">
                                <label for="foto" class="form-label">
                                    <i class="bi bi-person-square"></i> Foto
                                </label>
                                <div id="foto_preview" class="document-preview mb-2">
                                    <div class="text-muted">No file uploaded</div>
                                </div>
                                <input type="file" class="form-control" id="foto" name="foto" 
                                       accept=".jpg,.jpeg,.png">
                            </div>
                            
                            <div class="mb-3">
                                <label for="fc_ktp" class="form-label">
                                    <i class="bi bi-card-text"></i> Fotocopy KTP
                                </label>
                                <div id="fc_ktp_preview" class="document-preview mb-2">
                                    <div class="text-muted">No file uploaded</div>
                                </div>
                                <input type="file" class="form-control" id="fc_ktp" name="fc_ktp" 
                                       accept=".pdf,.jpg,.jpeg,.png">
                            </div>
                            
                            <div class="mb-3">
                                <label for="fc_ijazah" class="form-label">
                                    <i class="bi bi-award"></i> Fotocopy Ijazah
                                </label>
                                <div id="fc_ijazah_preview" class="document-preview mb-2">
                                    <div class="text-muted">No file uploaded</div>
                                </div>
                                <input type="file" class="form-control" id="fc_ijazah" name="fc_ijazah" 
                                       accept=".pdf,.jpg,.jpeg,.png">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="fc_kk" class="form-label">
                                    <i class="bi bi-people"></i> Fotocopy KK
                                </label>
                                <div id="fc_kk_preview" class="document-preview mb-2">
                                    <div class="text-muted">No file uploaded</div>
                                </div>
                                <input type="file" class="form-control" id="fc_kk" name="fc_kk" 
                                       accept=".pdf,.jpg,.jpeg,.png">
                            </div>
                            
                            <div class="mb-3">
                                <label for="fc_bk_nikah" class="form-label">
                                    <i class="bi bi-heart"></i> Fotocopy Buku Nikah
                                </label>
                                <div id="fc_bk_nikah_preview" class="document-preview mb-2">
                                    <div class="text-muted">No file uploaded</div>
                                </div>
                                <input type="file" class="form-control" id="fc_bk_nikah" name="fc_bk_nikah" 
                                       accept=".pdf,.jpg,.jpeg,.png">
                            </div>
                            
                            <div class="mb-3">
                                <label for="fc_akta_lahir" class="form-label">
                                    <i class="bi bi-file-person"></i> Fotocopy Akta Kelahiran
                                </label>
                                <div id="fc_akta_lahir_preview" class="document-preview mb-2">
                                    <div class="text-muted">No file uploaded</div>
                                </div>
                                <input type="file" class="form-control" id="fc_akta_lahir" name="fc_akta_lahir" 
                                       accept=".pdf,.jpg,.jpeg,.png">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Upload Progress -->
                    <div id="uploadProgress" class="progress d-none" style="height: 25px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" 
                             role="progressbar" style="width: 0%" aria-valuenow="0" 
                             aria-valuemin="0" aria-valuemax="100">
                            0% - Preparing upload...
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle"></i> Close
                </button>
                <button type="button" class="btn btn-primary" id="uploadDocumentsBtn">
                    <i class="bi bi-cloud-upload"></i> Upload Documents
                </button>
            </div>
        </div>
    </div>
</div>
