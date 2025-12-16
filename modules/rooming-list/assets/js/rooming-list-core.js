/**
 * Rooming List - Shared Core Logic
 * Used by both admin and public interfaces
 */

(function ($) {
    'use strict';

    // Export to global namespace
    window.RoomingListCore = {

        /**
         * Group list by Room Number
         */
        groupByRoom: function (list) {
            var rooms = {};
            if (!Array.isArray(list)) return rooms;
            list.forEach(function (item) {
                if (!rooms[item.room_number]) rooms[item.room_number] = [];
                rooms[item.room_number].push(item);
            });
            return rooms;
        },

        /**
         * Renumber rooms sequentially after deletion
         */
        renumberRooms: function (list) {
            var grouped = this.groupByRoom(list);
            var oldRoomNumbers = Object.keys(grouped).sort(function (a, b) {
                return parseInt(a) - parseInt(b);
            });

            var newList = [];
            oldRoomNumbers.forEach(function (oldNum, index) {
                var newNum = (index + 1).toString();
                grouped[oldNum].forEach(function (item) {
                    item.room_number = newNum;
                    newList.push(item);
                });
            });

            return newList;
        },

        /**
         * Validate required fields
         */
        validateList: function (list) {
            var errors = [];

            list.forEach(function (item, index) {
                if (!item.occupant_name || item.occupant_name.trim() === '') {
                    errors.push('Room ' + item.room_number + ', Member ' + ((index % 4) + 1) + ': Name is required');
                }
            });

            return errors;
        },

        /**
         * Create room header element
         */
        createRoomHeader: function (roomNum, occupantCount, maxPerRoom, callbacks) {
            var roomHeader = $('<div class="bg-theme3">').css({
                'color': 'white',
                'padding': '12px 15px',
                'border-radius': '8px 8px 0 0',
                'display': 'flex',
                'justify-content': 'space-between',
                'align-items': 'center'
            });

            var roomInfo = $('<div>').css({ 'font-weight': '600', 'font-size': '15px' })
                .html('Room ' + roomNum + ' <span style="font-size: 12px; opacity: 0.9;">(' + occupantCount + '/' + maxPerRoom + ')</span>');

            var roomActions = $('<div>').css({ 'display': 'flex', 'gap': '8px' });

            var addBtn = $('<button type="button" class="add-occupant-btn btn bg-white">+ Add Occupant</button>')
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

            if (callbacks && callbacks.onAddOccupant) {
                addBtn.on('click', callbacks.onAddOccupant);
            }

            if (callbacks && callbacks.onDeleteRoom) {
                delBtn.on('click', callbacks.onDeleteRoom);
            }

            roomActions.append(addBtn).append(delBtn);
            roomHeader.append(roomInfo).append(roomActions);

            return roomHeader;
        },

        /**
         * Create occupant row
         */
        createOccupantRow: function (occ, roomNum, callbacks) {
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

            if (callbacks && callbacks.onDeleteItem) {
                removeBtn.on('click', callbacks.onDeleteItem);
            }

            if (callbacks && callbacks.onLockItem) {
                lockBtn.on('click', callbacks.onLockItem);
            }

            actionDiv.append(editBtn).append(removeBtn).append(lockBtn);
            tdActions.append(actionDiv);
            tr.append(tdActions);

            return tr;
        }
    };

})(jQuery);
