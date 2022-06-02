jQuery(document).ready(function($) {
    var btn     = $('#fixed_avatar_ajax'),
        msg     = $('#fa_avatar_notice'),
        overlay = $('.change-avatar .overlay');

    btn.click(function(e) {
        e.preventDefault();
        e.stopImmediatePropagation();

        $(this).prop('disabled', true);
        msg.text('');
        overlay.show();

        sendData = {
            _wpnonce:           Favatar.nonce,
            id:                 Favatar.ID,
            avatar_preset:      $('.fa_member_avatar:checked').val(),
            action:             'fa_front_save_avatar',
        }

        $.post(Favatar.ajaxurl, sendData, function(data) {
            if(data == 'unauthorized') {
                msg.text('No tiene permiso para cambiar su avatar').css('color', 'red');
            }
            if(data == 'not found') {
                msg.text('El avatar que seleccionó no esta disponible, por favor escoja otro').css('color', 'red');
            }

            if(data == 'success') {
                msg.text('Su avatar se cambió con éxito').css('color', 'green');
                location.reload(true);
            }
        })

        .fail(function(jqXHR, textStatus) {
            textStatus ?
            msg.text(textStatus) :
            msg.text('Error de conexión, inténtelo más tarde');
            msg.css('color', 'red');
        })

        .always(function() {
            overlay.hide();
            btn.prop('disabled', false );
        });
    });
});
