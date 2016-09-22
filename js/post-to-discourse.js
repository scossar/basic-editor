jQuery(document).ready(function () {

    jQuery("#submit-post-to-discourse").click(function (event) {

        var postContent = jQuery( "#x" ).val(),
            topicID = jQuery('#topic_id').val(),
            nonce = jQuery('#post_to_discourse_nonce').val(),
            url = post_to_discourse_script.ajaxurl,
            data = {
                'action': 'create_discourse_post',
                'nonce': nonce,
                'post_content': postContent,
                'topic_id': topicID
            };

        jQuery.post(url, data, function(response) {

            // Clear the editor.
            var element = document.querySelector("trix-editor");
            element.editor.setSelectedRange([0, element.editor.getDocument().getLength()]);
            element.editor.deleteInDirection("backward");
        });
        event.preventDefault();
    });
});