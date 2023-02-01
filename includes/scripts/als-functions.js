(function($) {

    /**
     * Product Highlight Menu Widget Navigator functionality.
     */
    ( function() {

        $( 'li[id^=hl_]' ).on( 'click.sidereal', function() {

            var clicked_item = $(this);

            var hlwidget = $( '#highlight-widget-module' );
            if ( ! hlwidget ) { return; }

            var clicked = clicked_item.attr("id");

            target = clicked.substring(3);
            target = '#hw_' + target;
            hlwidget.find( '.highlight-show' ).toggleClass( 'highlight-show highlight-hide' );
            hlwidget.find( target ).toggleClass( 'highlight-hide highlight-show' );

            hlwidget.find( '.highlight-selected' ).toggleClass( 'highlight-selected highlight-unselected' );
            clicked_item.toggleClass('highlight-unselected highlight-selected' );
        } );

    } )();


} )( jQuery );