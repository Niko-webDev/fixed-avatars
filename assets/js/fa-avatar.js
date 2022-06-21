(function($) {

    if ($('#fixed_avatars_dash_modal').length > 0) {
        $('#fixed_avatars_dash_modal').dialog({
            title: 'Seleccione un Avatar',
            dialogClass: 'stw_avatar_modal',
            autoOpen: false,
            draggable: false,
            width: '100%',
            modal: true,
            resizable: false,
            closeOnEscape: true,
            position: {
              my: "center",
              at: "center",
              of: window
            },
            open: function () {
              // close dialog
              $('.ui-dialog-titlebar-close').bind('click', function(){
                $('#fixed_avatars_dash_modal').dialog('close');
              })
            },
        });

      //bind a button or a link to open the dialog
      $('#fixed_avatars_modal_open').click(function(e) {
        e.preventDefault();
        e.stopImmediatePropagation();
        $('#fixed_avatars_dash_modal').dialog('open');
        });
    }

    var btn     = $('#fixed_avatar_ajax'),
        msg     = $('#fa_avatar_notice'),
        spinner = $('.fa_spinner');

    btn.click(function(e) {
        e.preventDefault();
        e.stopImmediatePropagation();

        $(this).prop('disabled', true);
        msg.text('');
        spinner.css('visibility', 'visible');

        sendData = {
            _wpnonce:           Favatar.nonce,
            id:                 Favatar.ID,
            avatar_preset:      $('.fa_member_avatar:checked').val(),
            action:             'fa_front_save_avatar',
        }

        $.post(Favatar.ajaxurl, sendData, function(data) {
            if(data == 'unauthorized') {
                msg.text('You don\'t have permission to change this avatar').css('color', 'red');
            }
            if(data == 'not found') {
                msg.text('The avatar you\'ve chosen is not available, please select another').css('color', 'red');
            }

            if(data == 'success') {
                msg.text('Your avatar was successfully changed').css('color', 'green');
                location.reload(true);
            }
        })

        .fail(function(jqXHR, textStatus) {
            textStatus ?
            msg.text(textStatus) :
            msg.text('Connection error, Please try later');
            msg.css('color', 'red');
        })

        .always(function() {
            spinner.css('visibility', 'hidden');
            btn.prop('disabled', false );
        });
    });
})(jQuery);
