<?php
/**
 * Plugin Name: WP-Discourse Editor
 * Description: Hooks into the wp-discourse plugin to allow Discourse comments to be created on the WordPress site.
 * Version: 0.1
 * Author: scossar
 */

namespace WPDiscourseEditor;

use \WPDiscourse\Utilities\Utilities as DiscourseUtilities;

add_action( 'plugins_loaded', __NAMESPACE__ . '\\init' );

function init() {
	if ( class_exists( '\WPDiscourse\Discourse\Discourse' ) ) {
		$wp_discourse_editor = new \WPDiscourseEditor\Editor();
	}
}

class Editor {
	protected $options;

	public function __construct() {
		add_action( 'init', array( $this, 'setup_options' ) );
		add_action( 'wp_discourse_after_comments', array( $this, 'comment_form' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'wp_ajax_create_discourse_post', array( $this, 'ajax_post_to_discourse' ) );
		add_action( 'wp_ajax__nopriv_create_discourse_post', array( $this, 'ajax_post_to_discourse' ) );
	}

	public function setup_options() {
		$this->options = DiscourseUtilities::get_options();
	}

	public function enqueue_scripts() {
		wp_register_script( 'trix_js', plugins_url( '/vendor/trix/dist/trix.js', __FILE__ ) );
		wp_enqueue_script( 'trix_js' );
		wp_register_script( 'post_to_discourse_js', plugins_url( '/js/post-to-discourse.js', __FILE__ ), array( 'jquery') );
		wp_localize_script( 'post_to_discourse_js', 'post_to_discourse_script', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
		wp_enqueue_script( 'post_to_discourse_js' );
	}

	public function enqueue_styles() {
		wp_register_style( 'trix_css', plugins_url( '/vendor/trix/dist/trix.css', __FILE__ ) );
		wp_register_style( 'custom_css', plugins_url( '/css/styles.css', __FILE__ ) );
		wp_enqueue_style( 'trix_css' );
		wp_enqueue_style( 'custom_css' );
	}

	public function comment_form( $topic_id ) { ?>
		<h3>Post a comment</h3>
		<form class="wp-discourse-create-comment" id="post-to-discourse" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
			<?php wp_nonce_field( 'post_to_discourse', 'post_to_discourse_nonce' ); ?>
			<input type="hidden" id="topic_id" name="topic_id" value="<?php echo $topic_id; ?>">
			<input class="test-class" id="x" value="" type="hidden" name="content">
			<trix-editor input="x" class="trix-content"></trix-editor>
			<input type="submit" value="Post to Discourse" id="submit-post-to-discourse">
		</form>

		<?php
	}

	public function ajax_post_to_discourse() {
		if ( ! isset( $_POST['nonce'] ) ||
		     ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['nonce'] ) ), 'post_to_discourse' ) ) {
			exit();
		}

		$current_user = wp_get_current_user();
		$user_id = $current_user->ID;
		$base_url = $this->options['url'];
		$api_key = $this->options['api-key'];
		$api_username = $this->options['publish-username'];
		// This section is to retrieve the Discourse user_id. It would also be possible to retrieve Discourse
		// user info on login to WordPress and store it in the user_metadata table.
		$user_url = $base_url . "/users/by-external/$user_id.json";
		$user_url = add_query_arg( array(
			'api_key'      => $api_key,
			'api_username' => $api_username,
		), $user_url );
		$user_url = esc_url_raw( $user_url );
		$user_data = wp_remote_get( $user_url );
		if ( ! DiscourseUtilities::validate( $user_data ) ) {
			return new \WP_Error( 'unable_to_retrieve_user_data', 'There was an error in retrieving the current user data from Discourse.' );
		}
		$user_data = json_decode( wp_remote_retrieve_body( $user_data ), true );
		if ( array_key_exists( 'user', $user_data ) ) {
			$discourse_username = $user_data['user']['username'];
			$discourse_userid = $user_data['user']['id'];
			$api_key_url = "{$base_url}/admin/users/{$discourse_userid}/generate_api_key.json";
			$api_key_url = add_query_arg( array(
				'api_key' => $api_key,
				'api_username' => $api_username,
			), $api_key_url );

			$response = wp_remote_post( $api_key_url );

			if ( ! DiscourseUtilities::validate( $response ) ) {
				// Do something.
				exit();
			}

			$response = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( array_key_exists( 'api_key', $response ) ) {
				$user_api_key = $response['api_key']['key'];
			} else {
				// Do something.
				exit();
			}

			$topic_id = intval( $_POST['topic_id'] );
			$raw = wp_unslash( $_POST['post_content'] );
			error_log($raw);
			$posts_url = $base_url . '/posts';
			$posts_url = add_query_arg( array(
				'api_key'      => $user_api_key,
				'api_username' => $discourse_username,
				'topic_id' => $topic_id,
				'raw' => $raw,
			), $posts_url );

			$result = wp_remote_post( $posts_url );
			if ( ! DiscourseUtilities::validate( $result ) ) {
				// Do something.
				exit();
			}
		}

		exit();
	}
}