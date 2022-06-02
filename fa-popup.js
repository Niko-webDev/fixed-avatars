(function($) {
    // initalise the dialog
    $('#fa_plugin_modal').dialog({
        title: 'Imagenes',
        dialogClass: 'fa_plugin_modal',
        autoOpen: false,
        draggable: false,
        width: '80%',
        modal: true,
        resizable: false,
        closeOnEscape: true,
        position: {
          my: "center",
          at: "center",
          of: window
        },
        open: function () {
          // close dialog by clicking the overlay behind it
          $('.ui-widget-overlay').bind('click', function(){
            $('#fa_plugin_modal').dialog('close');
          })
        },
        create: function () {
          // style fix for WordPress admin
          $('.ui-dialog-titlebar-close').addClass('ui-button');
        },
    });

  // bind a button or a link to open the dialog
  $('#fa_readme').click(function(e) {
    e.preventDefault();
    $('#fa_plugin_modal').dialog('open');
    });
})(jQuery);
