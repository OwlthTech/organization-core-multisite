<div class="step-container">
    <div class="step-header mb-4 text-center">
        <h3 class="step-title">Select Parks</h3>
        <p class="step-description text-muted">Choose which parks you'd like to visit.</p>
    </div>

    <form id="form-4" class="needs-validation" novalidate>
        <div class="parks-selection" id="parks-selection-container">
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                Please select a location first to see available parks.
            </div>
        </div>

        <div class="form-check park-option mb-3">
            <input type="checkbox" class="form-check-input park-checkbox"
                name="parks[]" value="other" id="park-other">
            <label class="form-check-label" for="park-other">
                <strong>Other (Please specify)</strong>
            </label>
        </div>

        <div id="other-park-container" class="mt-3" style="display:none;">
            <label for="other_park_name" class="form-label">
                <strong>Specify Park Name:</strong>
            </label>
            <input type="text" class="form-control" id="other_park_name"
                name="other_park_name" placeholder="Enter park name">
        </div>
    </form>
</div>