<div class="modal fade confirmation-modal" id="confirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-theme text-white">
                <h5 class="modal-title text-white">
                    <i class="fas fa-clipboard-check me-2"></i>Review Your Booking
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="booking-review-content">
                    <div class="package-info mb-4">
                        <h4 class="theme"><?php echo esc_html($package->post_title); ?></h4>
                    </div>

                    <div class="order-summary-section mb-4">
                        <h5 class="mb-3">Booking Summary</h5>
                        <div class="card border-info">
                            <div class="card-body">
                                <div id="booking-summary-content"></div>
                            </div>
                        </div>
                    </div>

                    <div class="terms-section">
                        <div class="card border-warning">
                            <div class="card-body">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="modal-terms-agreement" required>
                                    <label class="form-check-label" for="modal-terms-agreement">
                                        <strong>Terms & Conditions:</strong> I have read the <a href="<?php echo home_url('/faq/'); ?>" target="_blank" class="text-primary">FAQ page</a> on this website and agree to all terms listed therein.
                                    </label>
                                </div>
                                <div class="compact-error-message my-0">
                                    <small class="validation-error text-danger" id="modal-terms-error" style="display: none;">
                                        <i class="fas fa-exclamation-triangle me-1"></i>You must agree to the terms and conditions.
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-edit me-2"></i>Edit Booking
                </button>
                <button type="button" class="btn btn-success bg-theme" id="confirm-booking-btn">
                    <i class="fas fa-check me-2"></i>Confirm Booking
                </button>
            </div>
        </div>
    </div>
</div>