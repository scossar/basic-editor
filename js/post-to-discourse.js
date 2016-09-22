jQuery(document).ready(function () {

    jQuery("#submit-post-to-discourse").click(function (event) {

        var postContent = jQuery( "#x" ).val(),
            topicID = jQuery('#topic_id').val(),
            nonce = jQuery('#post_to_discourse_nonce').val(),
            data = {
                'action': 'create_discourse_post',
                'nonce': nonce,
                'post_content': postContent,
                'topic_id': topicID
            },
            url = post_to_discourse_script.ajaxurl;

        jQuery.post(url, data, function(response) {
            console.log(response);
        });
        event.preventDefault();
    });
});