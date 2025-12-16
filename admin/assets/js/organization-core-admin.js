(function ($) {
	'use strict';

	// organizationCore object is injected via wp_localize_script()
	// make a safe local reference and small helper functions
	if ( typeof window.organizationCore === 'undefined' ) {
		console.error( 'organizationCore config missing — check wp_localize_script()' );
		window.organizationCore = {};
	}

	var OC = window.organizationCore;

	// Validate presence of a key and type (minimal validation)
	function validateShape(obj, shape) {
		// shape: { key: 'string' | 'object' | 'array', ... }
		shape = shape || {};
		for ( var k in shape ) {
			if ( ! Object.prototype.hasOwnProperty.call( shape, k ) ) continue;
			var t = shape[k];
			if ( typeof obj[k] === 'undefined' ) {
				return { ok: false, message: 'Missing key: ' + k };
			}
			if ( t === 'array' && ! Array.isArray( obj[k] ) ) {
				return { ok: false, message: 'Key ' + k + ' must be array' };
			}
			if ( t === 'object' && ( typeof obj[k] !== 'object' || Array.isArray( obj[k] ) ) ) {
				return { ok: false, message: 'Key ' + k + ' must be object' };
			}
		}
		return { ok: true };
	}

	// Get action config by name and do basic checks
	function getActionConfig( name ) {
		if ( ! OC.actions || ! OC.actions[name] ) {
			console.error( 'Action not found in organizationCore.actions:', name );
			return null;
		}
		var cfg = OC.actions[name];

		// basic shape validation
		var check = validateShape( cfg, { action: 'string', nonce: 'string', url: 'string' } );
		if ( ! check.ok ) {
			console.error( 'Action config invalid for', name, check.message );
			return null;
		}
		return cfg;
	}

	// A central AJAX helper -- adds nonce automatically if action expects it
	function ajax( actionName, data, opts ) {
		opts = opts || {};
		data = data || {};

		var cfg = getActionConfig( actionName );
		if ( ! cfg ) {
			// defensive fallback
			return $.Deferred().reject({ message: 'Invalid action config: ' + actionName }).promise();
		}

		// If the schema declared that 'nonce' is required, set it (unless caller provided)
		if ( cfg.requires && cfg.requires.indexOf( 'nonce' ) !== -1 ) {
			// Some code stores nonce in data attribute on button; prefer the nonce from cfg
			if ( typeof data.nonce === 'undefined' ) {
				data.nonce = cfg.nonce;
			}
		}

		// always include the action name so WP admin-ajax recognizes it
		data.action = cfg.action;

		var ajaxParams = {
			url: cfg.url,
			type: ( cfg.method || 'POST' ),
			data: data,
			dataType: 'json'
		};

		// allow callers to override/extend ajax params
		$.extend( ajaxParams, opts.ajax || {} );

		return $.ajax( ajaxParams ).then( function ( response ) {
			// Normalize server errors: we expect { success: boolean, data: {...} }
			if ( response && typeof response.success !== 'undefined' ) {
				return response; // resolved
			}
			// if server sent plain JSON without success flag, treat as success true
			return { success: true, data: response };
		}, function ( jqXHR, textStatus, errorThrown ) {
			// Reject with normalized object
			var parsed = { success: false, error: textStatus || errorThrown, status: jqXHR.status || 0 };
			try {
				// if server returned JSON error message, parse it
				var json = jqXHR.responseJSON || ( jqXHR.responseText ? JSON.parse( jqXHR.responseText ) : null );
				if ( json && json.data ) {
					parsed.data = json.data;
				}
			} catch (e) {
				// ignore parse error
			}
			return $.Deferred().reject( parsed ).promise();
		} );
	}

	// Expose helpers under OC (non-breaking if OC already exists)
	OC.getActionConfig = getActionConfig;
	OC.ajax = ajax;

	// Example: wire up the sync button using the new helpers
	$( function () {
		$( '#sync-all-users-btn' ).on( 'click', function ( e ) {
			e.preventDefault();

			// check role flag first
			if ( ! OC.roleFlags || ! OC.roleFlags.can_sync ) {
				alert( 'You do not have permission to perform this action.' );
				return;
			}

			if ( ! confirm( OC.strings.confirm_sync ) ) {
				return;
			}

			var $button = $( this );
			var $spinner = $( '#sync-spinner' );
			var $status = $( '#sync-status' );
			var $progressContainer = $( '#sync-progress-container' );

			$button.prop( 'disabled', true );
			$spinner.show();
			$status.show().text( OC.strings.starting );
			$progressContainer.show();

			// call centralized ajax helper
			OC.ajax( 'bulk_sync_all_users', {} ).done( function ( response ) {
				if ( response.success ) {
					$( '#progress-fill' ).css( 'width', '100%' );
					$( '#progress-text' ).text( response.data.message || '' );
					$status.text( '✓ ' + OC.strings.complete ).css( 'color', 'green' );
					alert( OC.strings.success_alert );
				} else {
					$status.text( '✗ ' + ( response.data && response.data.message ? response.data.message : OC.strings.sync_error_prefix ) ).css( 'color', 'red' );
					alert( OC.strings.sync_error_prefix + ( response.data && response.data.message ? response.data.message : '' ) );
				}
			} ).fail( function ( err ) {
				$status.text( '✗ ' + OC.strings.ajax_error ).css( 'color', 'red' );
				alert( OC.strings.ajax_error_alert );
				// optional: log more details
				console.error( 'Sync AJAX failed:', err );
			} ).always( function () {
				$button.prop( 'disabled', false );
				$spinner.hide();
			} );
		} );
	} );

})( jQuery );
