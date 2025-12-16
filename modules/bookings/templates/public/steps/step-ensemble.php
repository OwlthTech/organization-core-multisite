<div class="step-container">
    <div class="step-header mb-4 text-center">
        <h3 class="step-title">Ensemble Details</h3>
        <p class="step-description text-muted">
            Press "Create New Ensemble" to add your ensembles. When finished adding all your ensembles, press "Review Registration" to finalize.
        </p>
    </div>

    <form id="form-6" class="needs-validation" novalidate>
        <!-- Ensembles List Container -->
        <div id="ensembles-list-container" class="mb-5">
            <div id="ensembles-list" class="row"></div>
        </div>

        <!-- Create New Ensemble Button -->
        <div class="text-center mb-5">
            <button type="button" class="btn btn-primary bg-theme px-5 py-2" id="btnCreateEnsemble">
                <i class="fas fa-plus me-2"></i>Create New Ensemble
            </button>
        </div>

        <!-- Ensemble Form Modal -->
        <div class="ensemble-form-modal" id="ensembleFormModal" style="display: none; background: #f8f9fa; padding: 30px; border-radius: 8px; margin-bottom: 30px; border: 2px solid #e0e0e0;">

            <h5 class="mb-4"><i class="fas fa-edit me-2"></i>Ensemble Information</h5>

            <!-- Ensemble Name -->
            <div class="mb-4">
                <label for="ensemble_name" class="form-label">
                    <strong>Ensemble Name</strong> <span class="text-danger">*</span>
                </label>
                <input type="text" class="form-control" id="ensemble_name" name="ensemble_name"
                    placeholder="Enter ensemble name" required>
                <p class="text-muted small mt-2">
                    <i class="fas fa-info-circle me-1"></i>
                    The ensemble name provided here will appear on all communications and materials relating to the festival.
                    Please ensure it is accurate and contact Forum Music Festivals directly in the event of any changes.
                </p>
                <small class="validation-error" id="ensemble_name-error" style="display: none; color: #f44336;"></small>
            </div>

            <!-- Number of Students -->
            <div class="mb-4">
                <label for="ensemble_students" class="form-label">
                    <strong>Number of Students in Ensemble</strong> <span class="text-danger">*</span>
                </label>
                <input type="number" class="form-control" id="ensemble_students" name="ensemble_students"
                    min="1" placeholder="Enter number of students" required>
                <small class="validation-error" id="ensemble_students-error" style="display: none; color: #f44336;"></small>
            </div>

            <!-- Grade Level -->
            <div class="mb-4">
                <label for="ensemble_grade" class="form-label">
                    <strong>Grade Level</strong> <span class="text-danger">*</span>
                </label>
                <select class="form-control" id="ensemble_grade" name="ensemble_grade" required>
                    <option value="">-- Select One --</option>
                    <option value="elementary">Elementary</option>
                    <option value="middle">Middle School</option>
                    <option value="high">High School</option>
                    <option value="combo">Combo</option>
                </select>
                <small class="validation-error" id="ensemble_grade-error" style="display: none; color: #f44336;"></small>
            </div>

            <!-- Ensemble Type -->
            <div class="mb-4">
                <label for="ensemble_type" class="form-label">
                    <strong>Ensemble Type</strong> <span class="text-danger">*</span>
                </label>
                <select class="form-control" id="ensemble_type" name="ensemble_type" required>
                    <option value="">-- Select Type Ensemble --</option>
                    <option value="concert_band">Concert Band</option>
                    <option value="concert_choir">Concert/Chamber Choir</option>
                    <option value="orchestra">Orchestra</option>
                    <option value="jazz_band">Jazz Band</option>
                    <option value="show_choir">Show Choir</option>
                    <option value="jazz_choir">Jazz Choir</option>
                    <option value="other">Other</option>
                </select>
                <small class="validation-error" id="ensemble_type-error" style="display: none; color: #f44336;"></small>
            </div>

            <!-- Director Details Section -->
            <div class="mb-4 p-4" style="background: #fff; border-radius: 6px; border-left: 4px solid #007bff;">
                <h6 class="mb-3"><i class="fas fa-user-tie me-2"></i>Director Information</h6>

                <div class="row mb-4">
                    <div class="col-md-3">
                        <label for="director_prefix" class="form-label">
                            <strong>Prefix</strong> <span class="text-danger">*</span>
                        </label>
                        <select class="form-control" id="director_prefix" name="director_prefix" required>
                            <option value="">-- Select Prefix --</option>
                            <option value="dr">Dr.</option>
                            <option value="mr">Mr.</option>
                            <option value="mrs">Mrs.</option>
                            <option value="ms">Ms.</option>
                            <option value="mx">Mx.</option>
                            <option value="tr">Tr.</option>
                        </select>
                        <small class="validation-error" id="director_prefix-error" style="display: none; color: #f44336;"></small>
                    </div>

                    <div class="col-md-9">
                        <label for="director_first_name" class="form-label">
                            <strong>First Name</strong> <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="director_first_name" name="director_first_name"
                            placeholder="Director first name" required>
                        <small class="validation-error" id="director_first_name-error" style="display: none; color: #f44336;"></small>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="director_last_name" class="form-label">
                        <strong>Last Name</strong> <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control" id="director_last_name" name="director_last_name"
                        placeholder="Director last name" required>
                    <small class="validation-error" id="director_last_name-error" style="display: none; color: #f44336;"></small>
                </div>

                <div class="mb-4">
                    <label for="director_email" class="form-label">
                        <strong>Email</strong> <span class="text-danger">*</span>
                    </label>
                    <input type="email" class="form-control" id="director_email" name="director_email"
                        placeholder="director@example.com" required>
                    <small class="validation-error" id="director_email-error" style="display: none; color: #f44336;"></small>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="text-center gap-3" style="display: flex; gap: 10px; justify-content: center;">
                <button type="button" class="btn btn-secondary" id="btnBackEnsemble">
                    <i class="fas fa-arrow-left me-2"></i>Back
                </button>
                <button type="button" class="btn btn-success bg-theme" id="btnAddEnsemble">
                    <i class="fas fa-check me-2"></i>Add Ensemble
                </button>
            </div>

            <!-- Hidden field for edit mode -->
            <input type="hidden" id="editingEnsembleId" value="">
        </div>

        <!-- Hidden field to store all ensembles -->
        <input type="hidden" id="ensembles_data" name="ensembles_data" value="[]">
    </form>
</div>

<style>
    .ensemble-card {
        background: #fff;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
        position: relative;
        transition: all 0.3s ease;
    }

    .ensemble-card:hover {
        border-color: #007bff;
        box-shadow: 0 4px 12px rgba(0, 123, 255, 0.15);
    }

    .ensemble-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-bottom: 15px;
        border-bottom: 1px solid #f0f0f0;
    }

    .ensemble-card-title {
        font-size: 18px;
        font-weight: 600;
        color: #333;
        margin: 0;
    }

    .ensemble-card-actions {
        display: flex;
        gap: 10px;
    }

    .ensemble-card-actions button {
        padding: 6px 12px;
        font-size: 12px;
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.2s;
    }
</style>
<script>
    (function($) {
        'use strict';

        // ============================================
        // STATE & INITIALIZATION
        // ============================================

        let ensembles = [];
        let editingIndex = null;
        const STORAGE_KEY = 'form-6';

        // Initialize on document ready
        $(document).ready(function() {
            loadEnsembles();
            bindEvents();
            console.log('✅ Ensemble management initialized');
        });

        // ============================================
        // SCROLL HELPERS
        // ============================================

        /**
         * Scroll to ensemble form (when creating/editing)
         */
        function scrollToForm() {
            setTimeout(() => {
                const $form = $('#ensembleFormModal');
                if ($form.length && $form.is(':visible')) {
                    $('html, body').animate({
                        scrollTop: $form.offset().top - 100
                    }, 500);
                    console.log('📜 Scrolled to form');
                }
            }, 350); // Wait for slideDown animation
        }

        /**
         * Scroll to top of ensembles list (after save/update/delete)
         */
        function scrollToTop() {
            setTimeout(() => {
                const $container = $('#ensembles-list-container');
                if ($container.length) {
                    $('html, body').animate({
                        scrollTop: $container.offset().top - 100
                    }, 500);
                    console.log('📜 Scrolled to top');
                }
            }, 350); // Wait for slideUp animation
        }

        // ============================================
        // STORAGE OPERATIONS
        // ============================================

        function loadEnsembles() {
            const stored = sessionStorage.getItem(STORAGE_KEY);
            if (stored) {
                try {
                    const data = JSON.parse(stored);
                    ensembles = data.ensembles || [];
                    renderList();
                    console.log('✅ Loaded ensembles:', ensembles.length);
                } catch (e) {
                    console.error('❌ Load error:', e);
                    ensembles = [];
                }
            }
        }

        function saveEnsembles() {
            sessionStorage.setItem(STORAGE_KEY, JSON.stringify({
                ensembles
            }));
            console.log('💾 Saved ensembles:', ensembles.length);
        }

        // ============================================
        // RENDER FUNCTIONS
        // ============================================

        function renderList() {
            const $list = $('#ensembles-list');
            const formatType = str => str.replace(/_/g, ' ').replace(/\b\w/g, char => char.toUpperCase());
            $list.empty();

            if (ensembles.length === 0) {
                $list.html(`
        <div class="col-12">
          <div class="alert alert-info text-center">
            <i class="fas fa-info-circle me-2"></i>
            No ensembles added yet. Click "Create New Ensemble" to get started.
          </div>
        </div>
      `);
                return;
            }

            ensembles.forEach((ens, idx) => {
                $list.append(`
        <div class="col-12 col-md-6 col-lg-4">
          <div class="ensemble-card">
            <div class="ensemble-card-header">
              <h6 class="ensemble-card-title">${ens.ensemble_name}</h6>
              <div class="ensemble-card-actions">
                <button type="button" class="btn-edit-ensemble bg-theme3 text-white" data-index="${idx}">
                  <i class="fas fa-edit"></i> Edit
                </button>
                <button type="button" class="btn-delete-ensemble bg-theme text-white" data-index="${idx}">
                  <i class="fas fa-trash"></i> Delete
                </button>
              </div>
            </div>
            <div class="mt-3 small text-muted">
              <p class="mb-2"><strong>Type:</strong> ${formatType(ens.ensemble_type)}</p>
              <p class="mb-2"><strong>Students:</strong> ${ens.ensemble_students}</p>
              <p class="mb-0"><strong>Director:</strong> ${ens.director_prefix} ${ens.director_first_name} ${ens.director_last_name}</p>
            </div>
          </div>
        </div>
      `);
            });
        }

        // ============================================
        // EVENT HANDLERS
        // ============================================

        function bindEvents() {
            // ✅ Create new ensemble - with scroll
            $('#btnCreateEnsemble').on('click', () => {
                editingIndex = null;
                clearForm();
                $('#ensembleFormModal').slideDown(300);
                scrollToForm(); // ✅ Scroll to form
                console.log('✅ Create form shown');
            });

            // ✅ Close form - with scroll to top
            $('#btnBackEnsemble').on('click', () => {
                $('#ensembleFormModal').slideUp(300);
                clearForm();
                editingIndex = null;
                scrollToTop(); // ✅ Scroll to top
                console.log('✅ Form closed');
            });

            // ✅ Save/Update ensemble - with scroll to top
            $('#btnAddEnsemble').on('click', () => {
                if (!validate()) return;

                const data = getFormData();

                if (editingIndex !== null) {
                    ensembles[editingIndex] = data;
                    console.log('✏️ Updated ensemble:', editingIndex);
                } else {
                    ensembles.push(data);
                    console.log('✅ Added ensemble');
                }

                saveEnsembles();
                renderList();
                $('#ensembleFormModal').slideUp(300);
                clearForm();
                editingIndex = null;
                scrollToTop(); // ✅ Scroll to top
            });

            // ✅ Edit ensemble (delegated) - with scroll
            $(document).on('click', '.btn-edit-ensemble', function() {
                const idx = $(this).data('index');
                editEnsemble(idx);
            });

            // ✅ Delete ensemble (delegated) - with scroll
            $(document).on('click', '.btn-delete-ensemble', function() {
                const idx = $(this).data('index');
                deleteEnsemble(idx);
            });
        }

        // ============================================
        // CRUD OPERATIONS
        // ============================================

        function editEnsemble(idx) {
            editingIndex = idx;
            const ens = ensembles[idx];

            $('#ensemble_name').val(ens.ensemble_name);
            $('#ensemble_students').val(ens.ensemble_students);
            $('#ensemble_grade').val(ens.ensemble_grade);
            $('#ensemble_type').val(ens.ensemble_type);
            $('#director_prefix').val(ens.director_prefix);
            $('#director_first_name').val(ens.director_first_name);
            $('#director_last_name').val(ens.director_last_name);
            $('#director_email').val(ens.director_email);

            $('#ensembleFormModal').slideDown(300);
            scrollToForm(); // ✅ Scroll to form
            console.log('✏️ Editing ensemble:', idx);
        }

        function deleteEnsemble(idx) {
            if (!confirm('Are you sure you want to delete this ensemble?')) return;

            ensembles.splice(idx, 1);
            saveEnsembles();
            renderList();
            scrollToTop(); // ✅ Scroll to top after delete
            console.log('🗑️ Deleted ensemble. Remaining:', ensembles.length);
        }

        // ============================================
        // FORM HELPERS
        // ============================================

        function getFormData() {
            return {
                ensemble_name: $('#ensemble_name').val().trim(),
                ensemble_students: $('#ensemble_students').val(),
                ensemble_grade: $('#ensemble_grade').val(),
                ensemble_type: $('#ensemble_type').val(),
                director_prefix: $('#director_prefix').val(),
                director_first_name: $('#director_first_name').val().trim(),
                director_last_name: $('#director_last_name').val().trim(),
                director_email: $('#director_email').val().trim()
            };
        }

        function clearForm() {
            $('#ensemble_name, #ensemble_students, #ensemble_grade, #ensemble_type, #director_prefix, #director_first_name, #director_last_name, #director_email').val('');
            $('.validation-error').hide();
        }

        function validate() {
            let valid = true;
            $('.validation-error').hide();

            const fields = [{
                    id: 'ensemble_name',
                    msg: 'Ensemble name is required'
                },
                {
                    id: 'ensemble_students',
                    msg: 'Number of students is required'
                },
                {
                    id: 'ensemble_grade',
                    msg: 'Grade level is required'
                },
                {
                    id: 'ensemble_type',
                    msg: 'Ensemble type is required'
                },
                {
                    id: 'director_prefix',
                    msg: 'Prefix is required'
                },
                {
                    id: 'director_first_name',
                    msg: 'First name is required'
                },
                {
                    id: 'director_last_name',
                    msg: 'Last name is required'
                },
                {
                    id: 'director_email',
                    msg: 'Valid email is required'
                }
            ];

            fields.forEach(field => {
                const $input = $(`#${field.id}`);
                const val = $input.val().trim();

                if (!val || (field.id === 'director_email' && !isValidEmail(val))) {
                    $(`#${field.id}-error`).text(field.msg).show();
                    valid = false;
                }
            });

            if (!valid) console.warn('⚠️ Validation failed');
            return valid;
        }

        function isValidEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        }

    })(jQuery);
</script>