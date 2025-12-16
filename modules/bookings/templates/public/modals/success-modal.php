<div class="modal fade" id="successModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-theme text-white">
                <h5 class="modal-title text-white ">
                    <i class="fas fa-check-circle me-2"></i>Booking Confirmed!
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <div class="mb-4">
                    <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                    <h4 class="mt-3">Thank You!</h4>
                    <p class="lead">Your booking has been confirmed successfully.</p>
                </div>
                <div id="booking-confirmation-details" class="alert alert-success"></div>
                <p>You will receive a confirmation email shortly with all the details.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary bg-theme" onclick="redirectToPackage()">
                    <i class="fas fa-eye me-2"></i>View Package
                </button>
            </div>
        </div>
    </div>
</div>