/**
 * Toggle del menú móvil de site-header (ver header.php).
 * Vanilla JS, sin dependencia de jQuery.
 */
( function () {
	var toggle = document.getElementById( 'site-header-toggle' );
	var nav = document.getElementById( 'site-header-nav' );

	if ( ! toggle || ! nav ) {
		return;
	}

	toggle.addEventListener( 'click', function () {
		var isOpen = nav.classList.toggle( 'is-open' );
		toggle.classList.toggle( 'is-open', isOpen );
		toggle.setAttribute( 'aria-expanded', isOpen ? 'true' : 'false' );
	} );
} )();
