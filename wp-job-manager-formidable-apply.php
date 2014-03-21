<?php
/**
 * Plugin Name: WP Job Manager - Apply With Formidable Forms
 * Plugin URI:  https://github.com/Astoundify/wp-job-manager-gravityforms-apply/
 * Description: Apply to jobs that have added an email address via Formidable Forms
 * Author:      Astoundify
 * Author URI:  http://astoundify.com
 * Version:     1.0
 * Text Domain: job_manager_formidable_apply
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class Astoundify_Job_Manager_Apply_Formidable {

	/**
	 * @var $instance
	 */
	private static $instance;

	/**
	 * @var $jobs_form_id
	 */
	private $jobs_form_id;

	/**
	 * @var $resumes_form_id
	 */
	private $resumes_form_id;

	/**
	 * Make sure only one instance is only running.
	 */
	public static function get_instance() {
		if ( ! class_exists( 'FrmAppHelper' ) ) {
			return;
		}

		if ( ! isset ( self::$instance ) ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Start things up.
	 *
	 * @since WP Job Manager - Apply with Formidable Forms 1.0
	 */
	public function __construct() {
		$this->jobs_form_id    = get_option( 'job_manager_job_apply' );
		$this->resumes_form_id = get_option( 'job_manager_resume_apply' );

		$this->setup_actions();
		$this->setup_globals();
		$this->load_textdomain();
	}

	/**
	 * Set some smart defaults to class variables. Allow some of them to be
	 * filtered to allow for early overriding.
	 *
	 * @since WP Job Manager - Apply with Formidable Forms 1.0
	 *
	 * @return void
	 */
	private function setup_globals() {
		$this->file         = __FILE__;

		$this->basename     = plugin_basename( $this->file );
		$this->plugin_dir   = plugin_dir_path( $this->file );
		$this->plugin_url   = plugin_dir_url ( $this->file );

		$this->lang_dir     = trailingslashit( $this->plugin_dir . 'languages' );
		$this->domain       = 'job_manager_formidable_apply';
	}

	/**
	 * Loads the plugin language files
	 *
 	 * @since WP Job Manager - Apply with Formidable Forms 1.0
	 */
	public function load_textdomain() {
		$locale        = apply_filters( 'plugin_locale', get_locale(), $this->domain );
		$mofile        = sprintf( '%1$s-%2$s.mo', $this->domain, $locale );

		$mofile_local  = $this->lang_dir . $mofile;
		$mofile_global = WP_LANG_DIR . '/' . $this->domain . '/' . $mofile;

		if ( file_exists( $mofile_global ) ) {
			return load_textdomain( $this->domain, $mofile_global );
		} elseif ( file_exists( $mofile_local ) ) {
			return load_textdomain( $this->domain, $mofile_local );
		}

		return false;
	}

	/**
	 * Setup the default hooks and actions
	 *
	 * @since WP Job Manager - Apply with Formidable Forms 1.0
	 *
	 * @return void
	 */
	private function setup_actions() {
		add_filter( 'job_manager_settings', array( $this, 'job_manager_settings' ) );

		add_filter( 'frm_email_value' , array( $this, 'application_email' ) );
		add_filter( 'frm_to_email'    , array( $this, 'notification_email' ), 10, 4 );
	}

	private static function get_forms() {
		$forms = array( 0 => __( 'Please select a form', 'job_manager_formidable_apply' ) );

        $where = apply_filters( 'frm_forms_dropdown', "is_template=0 AND (status is NULL OR status = '' OR status = 'published')", '' );
        
        $frm_form = new FrmForm();
        $_forms   = $frm_form->getAll( $where, ' ORDER BY name' );

		if ( ! empty( $_forms ) ) {
			foreach ( $_forms as $_form ) {
				$forms[ $_form->id ] = $_form->title;
			}
		}

		return $forms;
	}

	/**
	 * Add a setting in the admin panel to enter the ID of the Gravity Form to use.
	 *
	 * @since WP Job Manager - Apply with Formidable Forms 1.0
	 *
	 * @param array $settings
	 * @return array $settings
	 */
	public function job_manager_settings( $settings ) {
		$settings[ 'job_listings' ][1][] = array(
			'name'    => 'job_manager_job_apply',
			'std'     => null,
			'label'   => __( 'Jobs Formidable Form', 'job_manager_formidable_apply' ),
			'desc'    => __( 'The ID of the Formidable Form you created for contacting employers.', 'job_manager_formidable_apply' ),
			'type'    => 'select',
			'options' => self::get_forms()
		);

		if ( class_exists( 'WP_Resume_Manager' ) ) {
			$settings[ 'job_listings' ][1][] = array(
				'name'    => 'job_manager_resume_apply',
				'std'     => null,
				'label'   => __( 'Resumes Formidable Form', 'job_manager_formidable_apply' ),
				'desc'    => __( 'The ID of the Formidable Form you created for contacting employees.', 'job_manager_formidable_apply' ),
				'type'    => 'select',
				'options' => self::get_forms()
			);
		}

		return $settings;
	}

	/**
	 * Dynamically populate the application email field.
	 *
	 * @since WP Job Manager - Apply with Formidable Forms 1.2.0
	 *
	 * @return string The email to notify.
	 */
	public function application_email() {
		global $post;

		if ( $post->_application ) {
			return $post->_application;
		} else {
			return $post->_candidate_email;
		}
	}

	/**
	 * Set the notification email when sending an email.
	 *
	 * @since WP Job Manager - Apply with Formidable Forms 1.0
	 *
	 * @return string The email to notify.
	 */
	public function notification_email( $recipients, $values, $form_id, $args ) {

		if ( $form_id == $this->jobs_form_id || $form_id == $this->resumes_form_id ) {
			foreach ( $values as $value ) {
				if ( is_email( $value ) ) {
					$recipients[] = $value;
				}
			}
		}

		return $recipients;
	}
}
add_action( 'init', array( 'Astoundify_Job_Manager_Apply_Formidable', 'get_instance' ) );