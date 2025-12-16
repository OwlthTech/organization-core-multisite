<div class="step-container">
    <div class="step-header mb-4 text-center">
        <h3 class="step-title">Select Location</h3>
        <p class="step-description text-muted">Choose your preferred festival location.</p>
    </div>

    <form id="form-2" class="needs-validation" novalidate>
        <?php if (!empty($locations)): ?>
            <div class="location-options">
                <?php foreach ($locations as $location): ?>
                    <div class="option-card" data-location-id="<?php echo esc_attr($location->term_id); ?>">
                        <div class="radio-wrapper">
                            <input type="radio"
                                class="form-check-input"
                                name="location_id"
                                value="<?php echo esc_attr($location->term_id); ?>"
                                id="location-<?php echo esc_attr($location->term_id); ?>"
                                required>
                        </div>
                        <div class="content-wrapper">
                            <label class="form-check-label w-100" for="location-<?php echo esc_attr($location->term_id); ?>">
                                <div class="location-name">
                                    <strong><?php echo esc_html($location->name); ?></strong>
                                </div>
                                <?php if (!empty($location->description)): ?>
                                    <div class="location-description mt-1">
                                        <small class="text-muted"><?php echo esc_html($location->description); ?></small>
                                    </div>
                                <?php endif; ?>
                            </label>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="compact-error-message text-center">
                <small class="validation-error" id="location_id-error" style="display: none; color: #f44336;">
                    Please select a location.
                </small>
            </div>
        <?php else: ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                No locations available for this package.
            </div>
        <?php endif; ?>
    </form>
</div>