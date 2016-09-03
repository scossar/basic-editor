jQuery(document).ready(function () {

    jQuery("#submit-post-to-discourse").click(function (event) {

        // var postContent = jQuery( "#x" ).val(),
        var url = post_to_discourse_script.ajaxurl,
            data = jQuery('#post-to-discourse').serialize();

        jQuery.post(url, data, function(response) {
            console.log(response);
        });
        event.preventDefault();
    });
});