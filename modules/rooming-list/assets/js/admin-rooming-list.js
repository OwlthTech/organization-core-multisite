(function ($) {
    'use strict';

    // State
    var state = {
        bookingId: 0,
        list: [],
        maxPerRoom: 4,
        roomsAllotted: 0,
        nonce: ''
    };

    /**
     * Initialize the Rooming List
     */
    function init() {
        if (typeof ocRoomingListConfig === 'undefined') {
            return;
        }

        // Load Config
        state.bookingId = parseInt(ocRoomingListConfig.booking_id);
        state.list = ocRoomingListConfig.rooming_list || [];
        state.maxPerRoom = parseInt(ocRoomingListConfig.max_per_room) || 4;
        state.roomsAllotted = parseInt(ocRoomingListConfig.rooms_allotted) || 0;
        state.nonce = ocRoomingListConfig.nonce;

        renderTable();
        bindEvents();
    }

    /**
     * Bind DOM Events
     */
    function bindEvents() {
        // Using new Test IDs
        $('#btn-add-room-test').on('click', handleAddRoom);
        $('#btn-save-list-test').on('click', handleSaveList);
        $('#btn-save-lock-list-test').on('click', handleSaveAndLockList); // New Handler

        $('#btn-import-list-test').on('click', function (e) {
            e.preventDefault();
            $('#import-file-input').trigger('click');
        });

        $('#import-file-input').on('change', function (e) {
            if (this.files.length === 0) return;

            var file = this.files[0];
            var formData = new FormData();
            formData.append('action', 'import_rooming_list');
            formData.append('booking_id', state.bookingId);
            formData.append('nonce', state.nonce);
            formData.append('import_file', file);

            var btn = $('#btn-import-list-test');
            var originalText = btn.text();
            btn.prop('disabled', true).text('Importing...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (res) {
                    btn.prop('disabled', false).text(originalText);
                    if (res.success) {
                        alert('Import successful! Imported ' + (res.data.count || 0) + ' items.');
                        location.reload();
                    } else {
                        alert('Import Error: ' + (res.data || 'Unknown error'));
                        $('#import-file-input').val(''); // Reset input
                    }
                },
                error: function (xhr, status, error) {
                    btn.prop('disabled', false).text(originalText);
                    alert('Server error during import.');
                    $('#import-file-input').val(''); // Reset input
                }
            });
        });

        $('#btn-export-list-test').on('click', function (e) {
            e.preventDefault();
            var url = ajaxurl + '?action=export_rooming_list&booking_id=' + state.bookingId + '&nonce=' + state.nonce;
            // Open in new tab to avoid white screen on current page if error occurs
            window.open(url, '_blank');
        });

        // Event Delegation for dynamic elements
        $(document).on('click', '.add-occupant-btn', handleAddOccupant);
        $(document).on('click', '.delete-room-btn', handleDeleteRoom);
        $(document).on('click', '.delete-item-btn', handleDeleteItem);
        $(document).on('click', '.lock-item-btn', handleLockItem);
    }

    /**
     * Group list by Room Number
     */
    function groupByRoom(list) {
        var rooms = {};
        if (!Array.isArray(list)) return rooms;
        list.forEach(function (item) {
            if (!rooms[item.room_number]) rooms[item.room_number] = [];
            rooms[item.room_number].push(item);
        });
        return rooms;
    }

    /**
     * Helper: Scrape DOM to update State
     * This ensures we don't lose typed data when re-rendering
     */
    function scrapeDOM() {
        var newList = [];
        $('#rooming-list-table tbody tr').each(function () {
            if ($(this).hasClass('no-data-row')) return;
            newList.push({
                id: $(this).data('id'),
                room_number: $(this).data('room'),
                occupant_name: $(this).find('.occupant-name-input').val(),
                occupant_type: $(this).find('.occupant-type-select').val(),
                is_locked: $(this).find('.lock-item-btn').data('locked') || 0
            });
        });
        state.list = newList;
    }

    /**
     * Render the Table
     */
    function renderTable() {
        var tbody = $('#rooming-list-table tbody');
        tbody.empty();

        var grouped = groupByRoom(state.list);
        var roomNumbers = Object.keys(grouped).sort(function (a, b) {
            return a.localeCompare(b, undefined, { numeric: true, sensitivity: 'base' });
        });

        if (roomNumbers.length === 0) {
            tbody.append('<tr class="no-data-row"><td colspan="4" style="text-align:center; padding: 20px;">No rooms added yet. Click "Add Room" to start.</td></tr>');
            return;
        }

        roomNumbers.forEach(function (roomNum) {
            var occupants = grouped[roomNum];
            occupants.forEach(function (occ, index) {
                var tr = $('<tr>').data('id', occ.id || '').data('room', roomNum);

                // Room Number Column (Rowspan)
                if (index === 0) {
                    var tdRoom = $('<td>').attr('rowspan', occupants.length)
                        .css({
                            'text-align': 'center',
                            'vertical-align': 'middle',
                            'font-weight': '600',
                            'border-right': '1px solid #ddd'
                        });

                    var roomText = roomNum;
                    var countDiv = $('<div style="font-size: 11px; color: #666; font-weight: normal;">(' + occupants.length + '/' + state.maxPerRoom + ')</div>');

                    var actionsDiv = $('<div style="margin-top:5px; display:flex; justify-content:center; gap:5px;">');
                    var addOccBtn = $('<button type="button" class="button button-small add-occupant-btn" data-room="' + roomNum + '" title="Add Occupant"><span class="dashicons dashicons-plus"></span></button>');
                    var delRoomBtn = $('<button type="button" class="button button-small delete-room-btn" data-room="' + roomNum + '" title="Delete Room"><span class="dashicons dashicons-trash"></span></button>');
                    actionsDiv.append(addOccBtn).append(delRoomBtn);

                    tdRoom.append(roomText).append(countDiv).append(actionsDiv);
                    tr.append(tdRoom);
                }

                // Name Column
                var tdName = $('<td>').css('padding', '8px 12px');
                var inputName = $('<input type="text" class="widefat occupant-name-input">').val(occ.occupant_name);
                if (occ.is_locked == 1) inputName.prop('disabled', true);
                tdName.append(inputName);
                tr.append(tdName);

                // Type Column
                var tdType = $('<td>').css('padding', '8px 12px');
                var selectType = $('<select class="widefat occupant-type-select">');
                ['Student', 'Chaperone', 'Director', 'Other'].forEach(function (t) {
                    selectType.append(new Option(t, t, false, t === occ.occupant_type));
                });
                if (occ.is_locked == 1) selectType.prop('disabled', true);
                tdType.append(selectType);
                tr.append(tdType);

                // Actions Column
                var tdActions = $('<td>');
                var actionDiv = $('<div style="display: flex; width: 100%; gap:8px;">');

                // Edit Button
                var editBtn = $('<button style="display: flex; justify-content: center; align-items: center;" type="button" class="button" title="edit"><span class="material-symbols-outlined">ink_pen</span></button>')
                    .on('click', function () { $(this).closest('tr').find('.occupant-name-input').focus(); });

                // Remove Button
                var removeBtn = $('<button style="display: flex; justify-content: center; align-items: center;" type="button" class="button delete-item-btn" title="remove"><span class="material-symbols-outlined">cancel</span></button>');

                // Lock Button
                var isLocked = occ.is_locked == 1;
                var lockIcon = isLocked ? 'lock' : 'lock_open_right';
                var lockTitle = isLocked ? 'locked' : 'unlocked';
                var lockStyle = isLocked ? 'border-color:transparent; display: flex; justify-content: center; align-items: center;' : 'display: flex; justify-content: center; align-items: center;';

                var lockBtn = $('<button type="button" class="button lock-item-btn" title="' + lockTitle + '" style="' + lockStyle + '"><span class="material-symbols-outlined">' + lockIcon + '</span></button>')
                    .data('id', occ.id).data('locked', occ.is_locked);

                if (isLocked) {
                    removeBtn.prop('disabled', true);
                    editBtn.prop('disabled', true);
                }

                actionDiv.append(editBtn).append(removeBtn).append(lockBtn);
                tdActions.append(actionDiv);
                tr.append(tdActions);

                tbody.append(tr);
            });
        });
    }

    /**
     * Handle Add Room
     */
    function handleAddRoom(e) {
        e.preventDefault();
        scrapeDOM();

        var grouped = groupByRoom(state.list);
        var roomNumbers = Object.keys(grouped);

        if (state.roomsAllotted > 0 && roomNumbers.length >= state.roomsAllotted) {
            alert('Maximum number of rooms reached (' + state.roomsAllotted + ').');
            return;
        }

        var nextNum = 1;
        if (roomNumbers.length > 0) {
            var nums = roomNumbers.map(function (n) { return parseInt(n) || 0; });
            nextNum = Math.max.apply(null, nums) + 1;
        }

        state.list.push({
            id: null,
            room_number: nextNum.toString(),
            occupant_name: '',
            occupant_type: 'Student',
            is_locked: 0
        });
        renderTable();
    }

    /**
     * Handle Add Occupant
     */
    function handleAddOccupant(e) {
        e.preventDefault();
        scrapeDOM();

        var roomNum = $(this).data('room');
        var grouped = groupByRoom(state.list);
        var currentCount = grouped[roomNum] ? grouped[roomNum].length : 0;

        if (currentCount >= state.maxPerRoom) {
            alert('Maximum occupants per room reached (' + state.maxPerRoom + ').');
            return;
        }

        state.list.push({
            id: null,
            room_number: roomNum,
            occupant_name: '',
            occupant_type: 'Student',
            is_locked: 0
        });
        renderTable();
    }

    /**
     * Handle Delete Room
     */
    function handleDeleteRoom(e) {
        e.preventDefault();
        scrapeDOM();

        var roomNum = $(this).data('room');
        if (!confirm('Delete Room ' + roomNum + ' and all occupants?')) return;

        state.list = state.list.filter(function (item) {
            return item.room_number != roomNum;
        });
        renderTable();
    }

    /**
     * Handle Delete Item
     */
    function handleDeleteItem(e) {
        e.preventDefault();
        scrapeDOM();

        var row = $(this).closest('tr');
        var rowIndex = $('#rooming-list-table tbody tr').index(row);

        if (rowIndex > -1) {
            var r = row.data('room');
            var grouped = groupByRoom(state.list);

            if (grouped[r] && grouped[r].length === 1) {
                if (!confirm('This is the last occupant. Removing them will delete the room. Continue?')) return;
            } else {
                if (!confirm('Remove this occupant?')) return;
            }

            state.list.splice(rowIndex, 1);
            renderTable();
        }
    }

    /**
     * Handle Lock Item
     */
    function handleLockItem(e) {
        e.preventDefault();
        scrapeDOM(); // Save current inputs first

        var btn = $(this);
        var row = btn.closest('tr');
        var rowIndex = $('#rooming-list-table tbody tr').index(row);

        if (rowIndex === -1 || !state.list[rowIndex]) return;

        var item = state.list[rowIndex];
        // Toggle lock state locally
        item.is_locked = (item.is_locked == 1) ? 0 : 1;

        renderTable();
    }

    /**
     * Handle Save List
     */
    function handleSaveList(e) {
        scrapeDOM();

        var btn = $(this);
        btn.prop('disabled', true).text('Saving...');

        $.post(ajaxurl, {
            action: 'save_rooming_list',
            booking_id: state.bookingId,
            rooming_list: state.list,
            nonce: state.nonce
        }, function (res) {
            btn.prop('disabled', false).text('Save list');
            if (res.success) {
                alert('Saved successfully!');
                location.reload();
            } else {
                alert('Error: ' + (res.data || 'Unknown error'));
            }
        }).fail(function (xhr, status, error) {
            btn.prop('disabled', false).text('Save list');
            alert('Server error.');
        });
    }

    /**
     * Handle Save & Lock List
     */
    function handleSaveAndLockList(e) {
        e.preventDefault();
        scrapeDOM();

        if (!confirm('Are you sure you want to save and lock the entire list?')) return;

        var btn = $(this);
        btn.prop('disabled', true).text('Saving...');

        // Set all items to locked
        state.list.forEach(function (item) {
            item.is_locked = 1;
        });

        // Re-render to show locked state immediately
        renderTable();

        $.post(ajaxurl, {
            action: 'save_rooming_list',
            booking_id: state.bookingId,
            rooming_list: state.list,
            nonce: state.nonce
        }, function (res) {
            btn.prop('disabled', false).text('Save & lock list');
            if (res.success) {
                alert('List saved and locked successfully!');
                location.reload();
            } else {
                alert('Error: ' + (res.data || 'Unknown error'));
            }
        }).fail(function (xhr, status, error) {
            btn.prop('disabled', false).text('Save & lock list');
            alert('Server error.');
        });
    }

    // Run Init
    $(document).ready(init);

})(jQuery);
