(function($) {

    /**
     * Adds "Select All" checkbox toggle to Product Quality taxonomy editor for the Product Type.
     */
    ( function() {
        var qualities_checklist = $( 'ul#al_product_qualitychecklist' );
        if ( ! qualities_checklist ) return;

        qualities_checklist.append( '<li><label class="selectit"><input type="checkbox" class="toggle-all-terms"/> Select All</label></li>' );

        $( '.toggle-all-terms' ).on( 'change', function(){
            $(this).closest( 'ul' ).find( ':checkbox' ).prop( 'checked', this.checked );
        });
    } )();

} )( jQuery );