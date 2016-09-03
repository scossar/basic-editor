jQuery(document).ready(function () {

    jQuery("#post-to-discourse").submit(function (event) {

        var url = post_to_discourse_script.ajaxurl,
            data = jQuery('#post-to-discourse').serialize();

        jQuery.post(url, data, function(response) {
            console.log(response);
        });
        event.preventDefault();
    });
});