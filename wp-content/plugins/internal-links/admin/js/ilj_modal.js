function ilj_create_modal(title, content) {
    var modal = jQuery('<div/>').addClass('ilj_modal show');
    var modal_wrapper = jQuery('<div/>').addClass('ilj_modal_wrap').append(modal);

    var header = jQuery('<div/>').addClass('ilj_modal_header').append(
            jQuery('<h2 />').text(title)
        )
    ;

    var body = jQuery('<div/>').addClass('ilj_modal_body').html(content);

    var footer = jQuery('<div/>').addClass('ilj_modal_footer').append(
            jQuery('<button/>').text('OK').addClass('button button-primary').on('click', function(e) {closeModal(e);})
        )
    ;

    var closeModal = function () {
        modal.removeClass('show').addClass('hide');
        jQuery('body').css({overflowY: 'auto'});
        setTimeout(function() {
            modal_wrapper.remove();
        },200);
    }.bind(modal_wrapper);

    modal.append(header);
    modal.append(body);
    modal.append(footer);

    jQuery('body').append(modal_wrapper).css({
        overflowY: 'hidden'
    });
}
// Export the function to the global scope
window.ilj_create_modal = ilj_create_modal;