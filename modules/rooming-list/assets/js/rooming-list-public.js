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
        if (typeof roomingListPublic === 'undefined') {
            return;
        }

        // Load Config
        state.bookingId = parseInt(roomingListPublic.booking_id);
        state.list = roomingListPublic.rooming_list || [];
        state.maxPerRoom = parseInt(roomingListPublic.max_per_room) || 4;
        state.roomsAllotted = parseInt(roomingListPublic.rooms_allotted) || 0;
        state.nonce = roomingListPublic.nonce;

        renderTable();
        bindEvents();
    }

    /**
     * Bind DOM Events
     */
    function bindEvents() {
        $('#btn-add-room-public').on('click', handleAddRoom);
        $('#btn-save-list-public').on('click', handleSaveList);
        $('#btn-save-lock-list-public').on('click', handleSaveAndLockList);

        // Event Delegation for dynamic elements
        $(document).on('click', '.add-occupant-btn', handleAddOccupant);
        $(document).on('click', '.delete-room-btn', handleDeleteRoom);
        $(document).on('click', '.delete-item-btn', handleDeleteItem);
        $(document).on('click', '.lock-item-btn', handleLockItem);
    }

    /**
     * Group list by Room Number (using shared core)
     */
    function groupByRoom(list) {
        return window.RoomingListCore.groupByRoom(list);
    }

    /**
     * Helper: Scrape DOM to update State
     */
    function scrapeDOM() {
        var newList = [];
        $('.room-table tbody tr, #rooming-list-table-public tbody tr').each(function () {
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
     * Render the Table - Each room gets its own wrapper with header and table
     */
    function renderTable() {
        // Clear previous room sections
        $('.room-section-wrapper').remove();

        var mainContainer = $('#rooming-list-table-public').parent();
        $('#rooming-list-table-public').hide(); // Hide the main table

        var grouped = groupByRoom(state.list);
        var roomNumbers = Object.keys(grouped).sort(function (a, b) {
            return a.localeCompare(b, undefined, { numeric: true, sensitivity: 'base' });
        });

        if (roomNumbers.length === 0) {
            $('#rooming-list-table-public').show();
            var tbody = $('#rooming-list-table-public tbody');
            tbody.empty();
            tbody.append('<tr class="no-data-row"><td colspan="3" style="text-align:center; padding: 20px;">No rooms added yet. Click "Add Room" to start.</td></tr>');
            return;
        }

        roomNumbers.forEach(function (roomNum) {
            var occupants = grouped[roomNum];

            // Create room wrapper
            var roomWrapper = $('<div class="room-section-wrapper">').css({
                'margin-bottom': '25px'
            });

            // Mark if this is a newly added room
            if (state.newRoomAdded && state.newRoomAdded === roomNum) {
                roomWrapper.data('new-room', true);
                state.newRoomAdded = null;
            }

            // Create room header with theme class
            var roomHeader = $('<div class="bg-theme3">').css({
                'color': 'white',
                'padding': '12px 15px',
                'border-radius': '8px 8px 0 0',
                'display': 'flex',
                'justify-content': 'space-between',
                'align-items': 'center'
            });

            var roomInfo = $('<div>').css({ 'font-weight': '600', 'font-size': '15px' })
                .html('Room ' + roomNum + ' <span style="font-size: 12px; opacity: 0.9;">(' + occupants.length + '/' + state.maxPerRoom + ')</span>');

            var roomActions = $('<div>').css({ 'display': 'flex', 'gap': '8px' });
            var addBtn = $('<button type="button" class="add-occupant-btn btn  bg-white ">+ Add Occupant</button>')
                .data('room', roomNum)
                .css({
                    'border': '1px solid rgba(255,255,255,0.3)',
                    'padding': '6px 12px',
                    'border-radius': '5px',
                    'font-size': '12px',
                    'cursor': 'pointer'
                });

            var delBtn = $('<button type="button" class="delete-room-btn btn bg-danger text-white">Delete Room</button>')
                .data('room', roomNum)
                .css({
                    'border': '1px solid rgba(255,255,255,0.3)',
                    'padding': '6px 12px',
                    'border-radius': '5px',
                    'font-size': '12px',
                    'cursor': 'pointer'
                });

            roomActions.append(addBtn).append(delBtn);
            roomHeader.append(roomInfo).append(roomActions);

            // Create table for this room
            var roomTable = $('<table class="table table-bordered table-striped room-table">').css({
                'width': '100%',
                'border-collapse': 'collapse',
                'margin-bottom': '0'
            });

            // Table header
            var thead = $('<thead>');
            var headerRow = $('<tr>');
            headerRow.append($('<th>').css({ 'width': '40%', 'font-weight': '600', 'font-size': '14px', 'background-color': '#f1f1f1', 'border': '1px solid #ddd', 'padding': '8px 12px' }).text('Name'));
            headerRow.append($('<th>').css({ 'width': '30%', 'font-weight': '600', 'font-size': '14px', 'background-color': '#f1f1f1', 'border': '1px solid #ddd', 'padding': '8px 12px' }).text('Type'));
            headerRow.append($('<th>').css({ 'width': '30%', 'font-weight': '600', 'font-size': '14px', 'background-color': '#f1f1f1', 'border': '1px solid #ddd', 'padding': '8px 12px' }).text('Action'));
            thead.append(headerRow);
            roomTable.append(thead);

            // Table body
            var tbody = $('<tbody>');

            // Render each occupant row
            occupants.forEach(function (occ, index) {
                var tr = $('<tr>').data('id', occ.id || '').data('room', roomNum);

                // Name Column (REQUIRED)
                var tdName = $('<td>').css({ 'padding': '8px 12px', 'border': '1px solid #ddd' }).attr('data-label', 'Name');
                var inputName = $('<input type="text" class="widefat occupant-name-input" required>').val(occ.occupant_name);
                if (occ.is_locked == 1) inputName.prop('disabled', true);
                tdName.append(inputName);
                tr.append(tdName);

                // Type Column
                var tdType = $('<td>').css({ 'padding': '8px 12px', 'border': '1px solid #ddd' }).attr('data-label', 'Type');
                var selectType = $('<select class="widefat occupant-type-select">');
                ['Student', 'Chaperone', 'Director', 'Other'].forEach(function (t) {
                    selectType.append(new Option(t, t, false, t === occ.occupant_type));
                });
                if (occ.is_locked == 1) selectType.prop('disabled', true);
                tdType.append(selectType);
                tr.append(tdType);

                // Actions Column
                var tdActions = $('<td>').css({ 'padding': '8px 12px', 'border': '1px solid #ddd' }).attr('data-label', 'Actions');
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

            roomTable.append(tbody);

            // Assemble room wrapper
            roomWrapper.append(roomHeader).append(roomTable);
            mainContainer.append(roomWrapper);
        });

        // Scroll to last room if a new one was added
        if (roomNumbers.length > 0 && $('.room-section-wrapper').length > 0) {
            var lastRoom = $('.room-section-wrapper').last();
            if (lastRoom.length && lastRoom.data('new-room')) {
                $('html, body').animate({
                    scrollTop: lastRoom.offset().top - 100
                }, 500);
                lastRoom.removeData('new-room');
            }
        }
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

        // Mark this as a new room for scroll-to
        state.newRoomAdded = nextNum.toString();
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

        // Renumber rooms sequentially
        state.list = window.RoomingListCore.renumberRooms(state.list);

        renderTable();
    }

    /**
     * Handle Delete Item
     */
    function handleDeleteItem(e) {
        e.preventDefault();
        scrapeDOM();

        var row = $(this).closest('tr');
        var allRows = $('.room-table tbody tr, #rooming-list-table-public tbody tr');
        var rowIndex = allRows.index(row);

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
        scrapeDOM();

        var btn = $(this);
        var row = btn.closest('tr');
        var allRows = $('.room-table tbody tr, #rooming-list-table-public tbody tr');
        var rowIndex = allRows.index(row);

        if (rowIndex === -1 || !state.list[rowIndex]) return;

        var item = state.list[rowIndex];
        item.is_locked = (item.is_locked == 1) ? 0 : 1;

        renderTable();
    }

    /**
     * Handle Save List
     */
    function handleSaveList(e) {
        e.preventDefault();
        scrapeDOM();

        // Validate required fields
        var errors = window.RoomingListCore.validateList(state.list);
        if (errors.length > 0) {
            alert('Please fill in all required fields:\n\n' + errors.join('\n'));
            return;
        }

        var btn = $(this);
        btn.prop('disabled', true).text('Saving...');

        $.post(roomingListPublic.ajaxUrl, {
            action: 'save_rooming_list_public',
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

        // Validate required fields
        var errors = window.RoomingListCore.validateList(state.list);
        if (errors.length > 0) {
            alert('Please fill in all required fields before locking:\n\n' + errors.join('\n'));
            return;
        }

        if (!confirm('Are you sure you want to save and lock the entire list?')) return;

        var btn = $(this);
        btn.prop('disabled', true).text('Saving...');

        // Set all items to locked
        state.list.forEach(function (item) {
            item.is_locked = 1;
        });

        // Re-render to show locked state immediately
        renderTable();

        $.post(roomingListPublic.ajaxUrl, {
            action: 'save_rooming_list_public',
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
