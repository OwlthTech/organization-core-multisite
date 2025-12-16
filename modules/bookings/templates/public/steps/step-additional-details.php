<div class="step-container">
    <div class="step-header mb-4 text-center">
        <h3 class="step-title">Additional Details</h3>
        <p class="step-description text-muted">Provide your group size, transportation, and preferences.</p>
    </div>

    <form id="form-5" class="needs-validation" novalidate>
        <div class="additional-details-section">

            <!-- Group Size Section -->
            <div class="section-header mb-4">
                <h5 class="text-theme"><i class="fas fa-users me-2"></i>Group Information</h5>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <label for="total_students" class="form-label">
                        <strong>Total Students</strong> <span class="text-danger">*</span>
                    </label>
                    <input type="number" class="form-control" id="total_students" name="total_students"
                        min="1" required placeholder="Number of students">
                    <small class="validation-error" id="total_students-error" style="display: none; color: #f44336;"></small>
                </div>

                <div class="col-md-6">
                    <label for="total_chaperones" class="form-label">
                        <strong>Total Chaperones</strong> <span class="text-danger">*</span>
                    </label>
                    <input type="number" class="form-control" id="total_chaperones" name="total_chaperones"
                        min="1" required placeholder="Number of chaperones">
                    <small class="validation-error" id="total_chaperones-error" style="display: none; color: #f44336;"></small>
                </div>
            </div>

            <!-- Transportation Section -->
            <div class="section-header mb-4 mt-5">
                <h5 class="text-theme"><i class="fas fa-bus me-2"></i>Transportation</h5>
            </div>

            <div class="mb-4">
                <div class="form-check">
                    <input class="form-check-input transport-radio" type="radio" name="transportation"
                        id="transport_own" value="own">
                    <label class="form-check-label" for="transport_own">
                        I will arrange my own transportation.
                    </label>
                </div>
                <div class="form-check mt-2">
                    <input class="form-check-input transport-radio" type="radio" name="transportation"
                        id="transport_quote" value="quote">
                    <label class="form-check-label" for="transport_quote">
                        Please provide a quote for transportation.
                    </label>
                </div>
            </div>

            <!-- Meal Preferences Section -->
            <div class="section-header mb-4 mt-5">
                <h5 class="text-theme"><i class="fas fa-utensils me-2"></i>Meal Preferences</h5>
            </div>

            <div class="mb-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="include_meal_vouchers"
                        name="include_meal_vouchers" value="1">
                    <label class="form-check-label" for="include_meal_vouchers">
                        <strong>Include meal vouchers?</strong>
                    </label>
                </div>
            </div>

            <!-- Meal Options Section (Hidden by default) -->
            <div id="meal_options_section" style="display: none; padding: 20px; background: #f8f9fa; border-radius: 4px; margin-bottom: 20px; border: 1px solid #e0e0e0;">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <label for="meals_per_day" class="form-label">
                            <strong>Meals Per Day</strong>
                        </label>
                        <input type="number" class="form-control" id="meals_per_day" name="meals_per_day"
                            min="1" max="3" placeholder="1-3 meals">
                        <small class="form-text text-muted">Enter number of meals per day (1-3)</small>
                        <small class="validation-error" id="meals_per_day-error" style="display: none; color: #f44336;"></small>
                    </div>
                </div>

                <div id="park_meal_options_container"></div>
            </div>

            <!-- ✅ FIXED: Lodging Section (Overnight packages only) -->
            <div class="row mb-4" id="lodging_dates_container" style="display: none;">
                <div class="col-md-6">
                    <label for="lodging_dates" class="form-label">
                        <strong>Lodging Date(s)</strong>
                    </label>
                    <input type="text" class="form-control" id="lodging_dates" name="lodging_dates"
                        placeholder="e.g., March 15-17, 2025">
                    <small class="form-text text-muted">Enter your lodging dates</small>
                </div>
            </div>

            <!-- ✅ FIXED: Additional Notes Section - Changed from booking_notes to special_notes -->
            <div class="section-header mb-4 mt-5">
                <h5 class="text-theme"><i class="fas fa-sticky-note me-2"></i>Additional Information</h5>
            </div>

            <div class="mb-4">
                <label for="special_notes" class="form-label">
                    <strong>Special Requests or Notes</strong>
                </label>
                <textarea class="form-control" id="special_notes" name="special_notes" rows="5"
                    placeholder="Enter any special requirements, requests, or additional information..."></textarea>
                <small class="form-text text-muted">Optional: Any special requests or information we should know</small>
            </div>

        </div>
    </form>
</div>

<script>
    (function($) {
        'use strict';

        // ============================================
        // WAIT FOR WINDOW VARIABLES TO BE READY
        // ============================================

        function initAdditionalDetails() {
            console.log('🚀 Initializing Additional Details...');
            console.log('📦 window.packageTitle:', window.packageTitle);
            console.log('📦 typeof window.packageTitle:', typeof window.packageTitle);

            // ============================================
            // CONFIGURATION
            // ============================================

            const config = {
                packageTitle: window.packageTitle || '',
                packageSlug: window.packageSlug || ''
            };

            console.log('🔍 DEBUG - Package Config:', config);

            // ============================================
            // PACKAGE TYPE DETECTION
            // ============================================

            const packageTitleLower = (config.packageTitle || '').toLowerCase();
            const packageType = {
                isOvernight: packageTitleLower.includes('overnight'),
                isJudges: packageTitleLower.includes('judges')
            };

            console.log('🔍 DEBUG - Package Type:', packageType);
            console.log('🔍 DEBUG - Package Title Lowercase:', packageTitleLower);

            // ============================================
            // CONDITIONAL FIELDS (Lodging Dates)
            // ============================================

            const $lodgingContainer = $('#lodging_dates_container');
            const $lodgingInput = $('#lodging_dates');

            console.log('🔍 DEBUG - Lodging Container:', {
                exists: $lodgingContainer.length > 0,
                isOvernight: packageType.isOvernight,
                currentDisplay: $lodgingContainer.css('display')
            });

            if ($lodgingContainer.length) {
                if (packageType.isOvernight) {
                    $lodgingContainer.css('display', 'flex'); // Force display using CSS
                    $lodgingInput.prop('required', true);
                    console.log('✅ Lodging dates field shown (overnight package)');
                    console.log('📍 Field display after show():', $lodgingContainer.css('display'));
                } else {
                    $lodgingContainer.hide();
                    $lodgingInput.prop('required', false);
                    console.log('ℹ️ Lodging dates field hidden (day package)');
                }
            } else {
                console.error('❌ Lodging container not found!');
            }

            // ============================================
            // MEAL VOUCHERS HANDLER
            // ============================================

            const $mealCheckbox = $('#include_meal_vouchers');
            const $mealSection = $('#meal_options_section');

            // Initial state - hidden
            $mealSection.hide();

            // Handle checkbox change
            $mealCheckbox.on('change', function() {
                if (this.checked) {
                    $mealSection.slideDown(300);
                    console.log('✅ Meal options section shown');

                    // Load park meal options if function exists
                    if (typeof handleParkMealOptions === 'function') {
                        handleParkMealOptions();
                    }
                } else {
                    $mealSection.slideUp(300);
                    $('#meals_per_day').val('');
                    $('#park_meal_options_container').html('');
                    console.log('✅ Meal options section hidden');
                }
            });

            // Restore state on page load
            if ($mealCheckbox.is(':checked')) {
                $mealSection.show();
                if (typeof handleParkMealOptions === 'function') {
                    handleParkMealOptions();
                }
            }

            console.log('✅ Additional details initialized');
        }

        // ============================================
        // INITIALIZATION WITH RETRY LOGIC
        // ============================================

        let initAttempts = 0;
        const maxAttempts = 10;

        function tryInit() {
            initAttempts++;
            console.log(`🔄 Init attempt ${initAttempts}/${maxAttempts}`);

            if (typeof window.packageTitle !== 'undefined' && window.packageTitle) {
                console.log('✅ window.packageTitle found:', window.packageTitle);
                initAdditionalDetails();
            } else if (initAttempts < maxAttempts) {
                console.log('⏳ window.packageTitle not ready, retrying in 100ms...');
                setTimeout(tryInit, 100);
            } else {
                console.warn('⚠️ window.packageTitle not found after 10 attempts, initializing anyway');
                initAdditionalDetails();
            }
        }

        // Start initialization when DOM is ready
        $(document).ready(function() {
            console.log('📄 DOM Ready, starting initialization...');
            tryInit();
        });

    })(jQuery);
</script>