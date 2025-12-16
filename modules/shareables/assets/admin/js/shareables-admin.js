jQuery(document).ready(function ($) {
    var wrapper = $('#shareables-repeater-list');
    var addBtn = $('#add-shareable-item');
    var template = $('#tmpl-shareable-item').html();

    var titleDisplayUpdate = function (input) {
        var item = input.closest('.shareable-item');
        var val = input.val() || 'New Item';
        item.find('.item-title-display').text(val);
    };

    // Initialize with data
    if (window.shareablesInitialData && window.shareablesInitialData.length) {
        window.shareablesInitialData.forEach(function (item) {
            addItem(item);
        });
    } else {
        // addItem(); // Optional: Start empty?
    }

    // Add Item Click (both buttons)
    addBtn.on('click', function () {
        addItem();
    });

    $('#add-shareable-item-top').on('click', function (e) {
        e.preventDefault();
        addItem();
        // Scroll to the newly added item
        setTimeout(function () {
            var lastItem = wrapper.find('.shareable-item').last();
            if (lastItem.length) {
                $('html, body').animate({
                    scrollTop: lastItem.offset().top - 100
                }, 500);
            }
        }, 100);
    });

    // Remove Item Click
    wrapper.on('click', '.delete-item', function (e) {
        e.preventDefault();
        if (confirm(oc_shareables.strings.confirm_delete)) {
            $(this).closest('.shareable-item').slideUp(200, function () {
                $(this).remove();
                renumberItems();
            });
        }
    });

    // Live Title Update
    wrapper.on('input', '.item-title', function () {
        titleDisplayUpdate($(this));
    });

    function addItem(data) {
        data = data || {};
        var index = wrapper.find('.shareable-item').length;
        var displayIndex = index + 1;

        // Ensure URLs are available (backward compatibility)
        // If URLs are not in JSON, they might be fetched by media uploader, or missing if old data.
        // For existing data without URLs, we rely on rendering (which might need ID lookup if we were strict, but we assume data has IDs).

        var html = template
            .replace(/{{index}}/g, index)
            .replace(/{{number}}/g, displayIndex)
            .replace(/{{title}}/g, data.title || '')
            .replace(/{{description}}/g, data.description || '')
            .replace(/{{media_ids}}/g, data.media_ids ? JSON.stringify(data.media_ids) : '[]')
            .replace(/{{media_urls}}/g, data.media_urls ? JSON.stringify(data.media_urls) : '[]') // Handle URLs
            .replace(/{{media_html}}/g, generateMediaHtml(data.media_details || [])); // Render from details if available (usually constructed server side or js side)

        var newItem = $(html);
        wrapper.append(newItem);

        // If coming from DB, data.media_details usually populated. If new, empty.
    }

    function renumberItems() {
        wrapper.find('.shareable-item').each(function (idx) {
            $(this).find('.item-number').text(idx + 1);
            $(this).attr('data-index', idx);
        });
    }

    function generateMediaHtml(mediaDetails) {
        var html = '';
        if (mediaDetails && mediaDetails.length) {
            mediaDetails.forEach(function (media) {
                var src = media.url;
                if (media.type !== 'image') {
                    src = media.icon || '';
                }
                html += '<div class="media-preview-item" data-id="' + media.id + '" data-url="' + media.url + '" data-type="' + media.type + '" data-icon="' + (media.icon || '') + '" style="position: relative; display: inline-block; margin: 5px;">';
                html += '<img src="' + src + '" style="max-width: 80px; max-height: 80px; border: 2px solid #ddd; border-radius: 4px;">';
                html += '<button type="button" class="remove-media" style="position: absolute; top: -8px; right: -8px; background: #dc3232; color: white; border: none; border-radius: 50%; width: 20px; height: 20px; cursor: pointer; font-size: 12px; line-height: 1; padding: 0;" title="Remove">&times;</button>';
                html += '</div>';
            });
        }
        return html;
    }

    // Media Uploader
    wrapper.on('click', '.select-media', function (e) {
        e.preventDefault();
        var btn = $(this);
        var container = btn.closest('tr.media-row');
        var previewContainer = container.find('.media-preview-container');
        var inputIds = container.find('.item-media-ids');
        var inputUrls = container.find('.item-media-urls');

        // Create a new frame instance for each click to avoid conflicts
        var frame = wp.media({
            title: 'Select Media',
            multiple: true,
            library: { type: ['image', 'video', 'application/pdf'] },
            button: { text: 'Add Selected Media' }
        });

        // Remove any previous event handlers to prevent duplicates
        frame.off('select');

        frame.on('select', function () {
            var selection = frame.state().get('selection');
            var currentIds = JSON.parse(inputIds.val() || '[]');
            var currentUrls = JSON.parse(inputUrls.val() || '[]');

            selection.map(function (attachment) {
                attachment = attachment.toJSON();
                if (currentIds.indexOf(attachment.id) === -1) {
                    currentIds.push(attachment.id);

                    var src = attachment.url;
                    var icon = '';
                    if (attachment.type !== 'image') {
                        src = attachment.icon;
                        icon = attachment.icon;
                    }

                    // Store full details for frontend use
                    currentUrls.push({
                        id: attachment.id,
                        url: attachment.url,
                        type: attachment.type,
                        icon: icon,
                        mime: attachment.mime
                    });

                    var html = '<div class="media-preview-item" data-id="' + attachment.id + '" data-url="' + attachment.url + '" data-type="' + attachment.type + '" data-icon="' + icon + '" style="position: relative; display: inline-block; margin: 5px;">';
                    html += '<img src="' + src + '" style="max-width: 80px; max-height: 80px; border: 2px solid #ddd; border-radius: 4px;">';
                    html += '<button type="button" class="remove-media" style="position: absolute; top: -8px; right: -8px; background: #dc3232; color: white; border: none; border-radius: 50%; width: 20px; height: 20px; cursor: pointer; font-size: 12px; line-height: 1; padding: 0;" title="Remove">&times;</button>';
                    html += '</div>';

                    previewContainer.append(html);
                }
            });

            inputIds.val(JSON.stringify(currentIds));
            inputUrls.val(JSON.stringify(currentUrls));
        });

        frame.open();
    });

    // Remove Media
    wrapper.on('click', '.remove-media', function () {
        var item = $(this).closest('.media-preview-item');
        var id = item.data('id');
        var container = item.closest('tr.media-row');
        var inputIds = container.find('.item-media-ids');
        var inputUrls = container.find('.item-media-urls');

        var ids = JSON.parse(inputIds.val() || '[]');
        var urls = JSON.parse(inputUrls.val() || '[]'); // Array of objects

        var index = ids.indexOf(id);
        if (index > -1) {
            ids.splice(index, 1);
            // Remove from urls array where id matches
            urls = urls.filter(function (u) { return u.id !== id; });

            inputIds.val(JSON.stringify(ids));
            inputUrls.val(JSON.stringify(urls));
        }

        item.remove();
    });

    // Handle "Save Draft" Click
    $('#save-post').on('click', function (e) {
        e.preventDefault();
        $('#post_status').val('draft');
        $('#shareables-form').submit();
    });

    // Handle "Publish/Update" Click (Default Submit)
    $('#publish').on('click', function (e) {
        // e.preventDefault(); // Let submit happen but we want to intercept
        // Set status to publish if not already
        $('#post_status').val('publish');
    });

    // Form Submission
    $('#shareables-form').on('submit', function (e) {
        e.preventDefault();
        var form = $(this);
        var spinner = $('.spinner');
        var publishBtn = $('#publish');
        var saveDraftBtn = $('#save-post');

        var title = $('#title').val();
        if (!title) {
            alert('Please enter a title');
            $('#title').focus();
            return;
        }

        var items = [];
        wrapper.find('.shareable-item').each(function () {
            var item = $(this);
            var mediaDetails = [];
            // Try to get FULL details from the hidden input if available
            try {
                mediaDetails = JSON.parse(item.find('.item-media-urls').val() || '[]');
            } catch (e) { }

            items.push({
                title: item.find('.item-title').val(),
                description: item.find('.item-description').val(),
                media_ids: JSON.parse(item.find('.item-media-ids').val() || '[]'),
                media_details: mediaDetails // Store full details
            });
        });

        publishBtn.prop('disabled', true);
        saveDraftBtn.prop('disabled', true);
        spinner.addClass('is-active');

        $.ajax({
            url: oc_shareables.ajax_url,
            type: 'POST',
            data: {
                action: 'shareables_save_item',
                nonce: oc_shareables.nonce,
                id: form.find('input[name="id"]').val(),
                title: title,
                items: JSON.stringify(items),
                status: $('#post_status').val()
            },
            success: function (response) {
                if (response.success) {
                    if (response.data.redirect) {
                        window.location.href = response.data.redirect;
                    } else {
                        form.find('input[name="id"]').val(response.data.id);

                        // Update UI to reflect saved state
                        var status = $('#post_status').val();
                        if (status === 'publish') {
                            $('#post-status-display').text('Published');
                            $('#publish').val('Update');

                            // Show generated link if available
                            if (response.data.uuid) {
                                var linkHtml = '<div class="misc-pub-section" id="shareable-link-section">';
                                linkHtml += '<a href="' + response.data.public_url + '" target="_blank" class="button button-small" style="width: 100%; text-align: center; margin-top: 5px;">View Public Page <span class="dashicons dashicons-external" style="vertical-align: middle; font-size: 14px;"></span></a>';
                                linkHtml += '</div>';

                                // Remove existing link section if any
                                $('#shareable-link-section').remove();

                                // Add new link section
                                $('#misc-publishing-actions').append(linkHtml);
                            }
                        } else {
                            $('#post-status-display').text('Draft');
                        }
                    }
                } else {
                    alert(response.data || oc_shareables.strings.error);
                }
            },
            error: function () {
                alert(oc_shareables.strings.error);
            },
            complete: function () {
                publishBtn.prop('disabled', false);
                saveDraftBtn.prop('disabled', false);
                spinner.removeClass('is-active');
            }
        });
    });

});
