// The CMB2 metabox needs to be reinitialized after it's been
// added to a widget for the javascript controls to work
(function($) {
    $(document).on('widget-updated widget-added', function(e, widget){
        // FIXME: Workaround for issue of color pickers being created twice
        // when reinitializing the metabox
        $(widget).find('.wp-color-result').remove();

        // Reinitalize the widget's metabox
        CMB2.$metabox = $(widget).find('.cmb2-wrap > .cmb2-metabox');
        CMB2.init();
    });
})(jQuery);

// Workaround for issue: https://github.com/CMB2/CMB2-Snippet-Library/issues/66
(function(window, document, $) {
    $(document).on('widget-updated widget-added', function( event, widget ) {
        var $metabox = $(widget).find('.cmb2-wrap > .cmb2-metabox'),
            cmb = window.CMB2;

        $metabox
            .on('click', '.cmb-add-group-row', cmb.addGroupRow)
            .on('click', '.cmb-add-row-button', cmb.addAjaxRow)
            .on('click', '.cmb-remove-group-row', cmb.removeGroupRow)
            .on('click', '.cmb-remove-row-button', cmb.removeAjaxRow)
            .on('click', '.cmbhandle, .cmbhandle + .cmbhandle-title', cmb.toggleHandle);
    });
})(window, document, jQuery);
