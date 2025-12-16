<div class="step-container">
    <div class="step-header mb-4 text-center">
        <h3 class="step-title">Select School</h3>
        <p class="step-description text-muted">Choose your school from the list below.</p>
    </div>

    <form id="form-1" class="needs-validation" novalidate>
        <?php if (!empty($user_schools_details)): ?>
            <div class="school-options mb-4">
                <?php foreach ($user_schools_details as $school): ?>
                    <div class="option-card minimal" data-school-id="<?php echo esc_attr($school['id']); ?>">
                        <input type="radio"
                            class="form-check-input"
                            name="school_id"
                            value="<?php echo esc_attr($school['id']); ?>"
                            id="school-<?php echo esc_attr($school['id']); ?>"
                            required>
                        <label class="form-check-label w-100" for="school-<?php echo esc_attr($school['id']); ?>">
                            <div class="school-info">
                                <strong class="school-name d-block mb-2"><?php echo esc_html($school['school_name']); ?></strong>
                                <small class="school-location text-muted d-block">
                                    <i class="fas fa-map-marker-alt me-1"></i>
                                    <?php echo esc_html($school['school_city']); ?>
                                    <?php if (!empty($school['school_state'])): ?>, <?php echo esc_html($school['school_state']); ?><?php endif; ?>
                                    <?php if (!empty($school['school_zip'])): ?> - <?php echo esc_html($school['school_zip']); ?><?php endif; ?>
                                </small>
                                <?php if (!empty($school['school_phone'])): ?>
                                    <small class="school-phone text-muted d-block mt-1">
                                        <i class="fas fa-phone me-1"></i><?php echo esc_html($school['school_phone']); ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="text-center my-4">
                <a href="<?php echo home_url('/my-account/schools/add/'); ?>?ref=<?php echo urlencode($current_booking_url); ?>"
                    class="btn text-white bg-theme3">
                    <i class="fas fa-plus me-2"></i>Add New School
                </a>
            </div>
        <?php else: ?>
            <div class="no-schools-container text-center py-5">
                <div class="mb-4">
                    <i class="fas fa-school fa-5x text-muted mb-4"></i>
                    <h4 class="text-muted">No Schools Found</h4>
                    <p class="text-muted mb-4">You need to add at least one school to continue with this booking.</p>
                </div>
                <div class="school-actions">
                    <a href="<?php echo home_url('/my-account/schools/add'); ?>" class="btn btn-primary btn-lg px-5 py-3">
                        <i class="fas fa-plus me-2"></i>Add Your First School
                    </a>
                </div>
                <div class="mt-4">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        After adding your school, you'll be redirected back to continue booking.
                    </small>
                </div>
            </div>
        <?php endif; ?>

        <div class="compact-error-message text-center">
            <small class="validation-error" id="school_key-error" style="display: none; color: #f44336;">
                Please select a school.
            </small>
        </div>
    </form>
</div>