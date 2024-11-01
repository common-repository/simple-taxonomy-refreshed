<?php
/**
 * Simple Taxonomy Admin Import class file.
 *
 * @package simple-taxonomy-refreshed
 * @author Neil James
 */

/**
 * Simple Taxonomy Admin Import class.
 *
 * @package simple-taxonomy-refreshed
 */
class SimpleTaxonomyRefreshed_Admin_Import {
	const IMPORT_SLUG = 'staxo_import';

	/**
	 * Instance variable to ensure singleton.
	 *
	 * @var int
	 */
	private static $instance = null;

	/**
	 * Call to construct the singleton instance.
	 *
	 * @return object
	 */
	final public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new SimpleTaxonomyRefreshed_Admin_Import();
		}
		return self::$instance;
	}

	/**
	 * Protected Constructor
	 *
	 * @return void
	 */
	final protected function __construct() {
		add_action( 'admin_init', array( __CLASS__, 'check_importation' ) );
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ), 20 );
	}

	/**
	 * Add settings menu page.
	 **/
	public static function add_menu() {
		add_submenu_page( SimpleTaxonomyRefreshed_Admin::ADMIN_SLUG, __( 'Terms Import', 'simple-taxonomy-refreshed' ), __( 'Terms Import', 'simple-taxonomy-refreshed' ), 'manage_options', self::IMPORT_SLUG, array( __CLASS__, 'page_importation' ) );

		// help text.
		add_action( 'load-taxonomies_page_' . self::IMPORT_SLUG, array( __CLASS__, 'add_help_tab' ) );
	}

	/**
	 * Check POST datas for bulk importation.
	 *
	 * @return void
	 */
	public static function check_importation() {
		if ( isset( $_POST[ self::IMPORT_SLUG ] ) && isset( $_POST['import_content'] ) && ! empty( $_POST['import_content'] ) ) {
			// check nonce for form submit.
			check_admin_referer( self::IMPORT_SLUG );

			// phpcs:ignore  WordPress.Security.ValidatedSanitizedInput
			$taxonomy = sanitize_text_field( wp_unslash( $_POST['taxonomy'] ) );
			if ( ! taxonomy_exists( $taxonomy ) ) {
				wp_die( esc_html__( 'You cannot import terms into a taxonomy that does not exist.', 'simple-taxonomy-refreshed' ) );
			}

			$taxonomy_obj = get_taxonomy( $taxonomy );
			if ( ! ( current_user_can( 'manage_options' ) || current_user_can( $taxonomy_obj->cap->manage_terms ) ) ) {
				wp_die( esc_html__( 'You do not have the necessary permissions to import terms.', 'simple-taxonomy-refreshed' ) );
			}

			$prev_ids = array();
			// standard sanitizing will remove the newline and tab characters - do it for each term.
			// phpcs:ignore  WordPress.Security.ValidatedSanitizedInput
			$terms = explode( "\n", str_replace( array( "\r\n", "\n\r", "\r" ), "\n", $_POST['import_content'] ) );
			// phpcs:ignore  WordPress.Security.ValidatedSanitizedInput
			$hierarchy = ( isset( $_POST['hierarchy'] ) ? sanitize_text_field( wp_unslash( $_POST['hierarchy'] ) ) : 'no' );
			if ( 'no' !== $hierarchy && ! $taxonomy_obj->hierarchical ) {
				// can't load hierarchical data into a flat taxonomy.
				add_settings_error( 'simple-taxonomy-refreshed', 'hierarchical', esc_html__( 'The taxonomy is non-hierarchical. You cannot load hierarchical data into it.', 'simple-taxonomy-refreshed' ), 'error' );
				return;
			}
			$termlines = 0;
			$added     = 0;
			foreach ( $terms as $term_line ) {
				if ( 'no' !== $hierarchy ) {
					if ( 'space' === $hierarchy ) {
						$sep = ' ';
					} else {
						$sep = "\t";
					}

					$level = strlen( $term_line ) - strlen( ltrim( $term_line, $sep ) );

					if ( 0 === $termlines ) {
						$term = self::create_term( $taxonomy, $term_line, 0 );
						if ( false !== $term ) {
							$prev_ids[0] = $term[0];
							$added      += (int) $term[1];
							++$termlines;
						}
					} else {
						if ( ( $level - 1 ) < 0 ) {
							$parent = 0;
						} else {
							$parent = $prev_ids[ $level - 1 ];
						}

						$term = self::create_term( $taxonomy, $term_line, $parent );
						if ( false !== $term ) {
							$prev_ids[ $level ] = $term[0];
							$added             += (int) $term[1];
							++$termlines;
						}
					}
				} else {
					$term = self::create_term( $taxonomy, $term_line, 0 );
					if ( false !== $term ) {
						$added += (int) $term[1];
						++$termlines;
					}
				}
			}

			if ( 0 === $termlines ) {
				add_settings_error( 'simple-taxonomy-refreshed', 'terms_updated', esc_html__( 'Done, but you have not imported any term.', 'simple-taxonomy-refreshed' ), 'error' );
			} else {
				// translators: %d is the count of terms that were successfully processed.
				add_settings_error( 'simple-taxonomy-refreshed', 'terms_updated', esc_html( sprintf( __( 'Done, %d term lines processed successfully !', 'simple-taxonomy-refreshed' ), $termlines ) ), 'updated' );
			}
			if ( 1 === $added ) {
				add_settings_error( 'simple-taxonomy-refreshed', 'terms_updated', esc_html__( '1 new term was created.', 'simple-taxonomy-refreshed' ), 'updated' );
			} elseif ( $added > 1 ) {
				// translators: %d is the count of terms that were created.
				add_settings_error( 'simple-taxonomy-refreshed', 'terms_updated', esc_html( sprintf( __( ' %d new terms were created.', 'simple-taxonomy-refreshed' ), $added ) ), 'updated' );
			}
		}
	}

	/**
	 * Create term on a taxonomy if necessary.
	 *
	 * @param string  $taxonomy  taxonomy name.
	 * @param string  $term_name term name.
	 * @param integer $par_term  term parent.
	 * @return boolean|array of term_id and whether already existed
	 */
	private static function create_term( $taxonomy = '', $term_name = '', $par_term = 0 ) {
		$term_name = trim( sanitize_text_field( $term_name ) );
		if ( empty( $term_name ) ) {
			return false;
		}

		$id = term_exists( $term_name, $taxonomy, $par_term );
		if ( is_array( $id ) ) {
			$id = (int) $id['term_id'];
		}

		if ( 0 !== (int) $id ) {
			return array( $id, false );
		}

		// Insert on DB.
		$term = wp_insert_term( $term_name, $taxonomy, array( 'parent' => $par_term ) );

		// Cache.
		clean_term_cache( $par_term, $taxonomy );
		clean_term_cache( $term['term_id'], $taxonomy );

		return array( $term['term_id'], true );
	}

	/**
	 * Display page to allow import in custom taxonomies.
	 */
	public static function page_importation() {
		// phpcs:disable  WordPress.Security.NonceVerification.Missing
		if ( ! isset( $_POST['import_content'] ) ) {
			$_POST['import_content'] = '';
		}
		if ( ! isset( $_POST['taxonomy'] ) ) {
			$_POST['taxonomy'] = '';
		}
		if ( ! isset( $_POST['hierarchy'] ) ) {
			$_POST['hierarchy'] = '';
		}
		// phpcs:enable  WordPress.Security.NonceVerification.Missing

		settings_errors( 'simple-taxonomy-refreshed' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Terms Import', 'simple-taxonomy-refreshed' ); ?></h1>
			<p><?php esc_html_e( 'Import a list of words as terms of a taxonomy using this tool.', 'simple-taxonomy-refreshed' ); ?></p>
			<ul style="margin-left:1em; list-style-type:disc"><li><?php esc_html_e( 'Enter one term per line.', 'simple-taxonomy-refreshed' ); ?></li>
			<li><?php esc_html_e( 'You can add terms into a hierarchical taxonomy by entering an existing term to identify where the new terms are to be added.', 'simple-taxonomy-refreshed' ); ?></li>
			<li><?php esc_html_e( 'Existing terms can be entered using either the Term Name or its Slug.', 'simple-taxonomy-refreshed' ); ?></li>
			<li><?php esc_html_e( 'Use leading spaces or tabs to denote the level of the Term in the hierarchy relative to its parent.', 'simple-taxonomy-refreshed' ); ?></li></ul>
			<p><?php esc_html_e( 'See Help above for more detailed information on usage.', 'simple-taxonomy-refreshed' ); ?></p>
			<form action="<?php echo esc_url( admin_url( 'admin.php?page=' . self::IMPORT_SLUG ) ); ?>" method="post">
				<p>
					<label for="taxonomy"><?php esc_html_e( 'Choose a taxonomy', 'simple-taxonomy-refreshed' ); ?></label>
					<br />
					<select name="taxonomy" id="taxonomy">
						<?php
						foreach ( get_taxonomies(
							/**
							 *
							 * Filters the default get_taxonomies selector.
							 *
							 * @param array array default list of taxonomy selection criteria
							 */
							apply_filters(
								'staxo_taxo_import_convert_select',
								array(
									'show_ui' => true,
									'public'  => true,
								)
							),
							'object'
						) as $taxonomy ) {
							// phpcs:ignore WordPress.Security.NonceVerification.Missing
							echo '<option value="' . esc_attr( $taxonomy->name ) . '" ' . selected( sanitize_text_field( wp_unslash( $_POST['taxonomy'] ) ), $taxonomy->name, false ) . '> ' . esc_html( $taxonomy->label ) . ' (' . esc_html( $taxonomy->name ) . ')</option>' . "\n";
						}
						?>
					</select>
				</p>

				<?php
				// phpcs:ignore WordPress.Security.NonceVerification.Missing
				$hierarchy = sanitize_text_field( wp_unslash( $_POST['hierarchy'] ) );
				?>
				<p>
					<label for="hierarchy"><?php esc_html_e( 'Import uses a hierarchy ?', 'simple-taxonomy-refreshed' ); ?></label>
					<br />
					<select name="hierarchy" id="hierarchy">
						<option value="no" <?php selected( $hierarchy, 'no' ); ?>><?php esc_html_e( 'No hierarchy', 'simple-taxonomy-refreshed' ); ?></option>
						<option value="space" <?php selected( $hierarchy, 'space' ); ?>><?php esc_html_e( 'Hierarchy uses space for levels', 'simple-taxonomy-refreshed' ); ?></option>
						<option value="tab" <?php selected( $hierarchy, 'tab' ); ?>><?php esc_html_e( 'Hierarchy uses tab for levels', 'simple-taxonomy-refreshed' ); ?></option>
					</select>
				</p>

				<p>
					<label for="import_content"><?php esc_html_e( 'Terms to import', 'simple-taxonomy-refreshed' ); ?></label>
					<br />
					<?php
					// Output the tag with PHP to avoid these leading format tabs being output in the textarea.
					echo '<textarea name="import_content" id="import_content" rows="20" style="width:100%" onkeydown="insertTab(this, event);">';
					// phpcs:ignore  WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput
					echo esc_html( stripslashes( wp_unslash( $_POST['import_content'] ) ) );
					echo '</textarea>';
					?>
				</p>

				<p class="submit">
					<?php wp_nonce_field( self::IMPORT_SLUG ); ?>
					<input type="submit" name="<?php echo esc_html( self::IMPORT_SLUG ); ?>" value="<?php esc_html_e( 'Import these words as terms', 'simple-taxonomy-refreshed' ); ?>" class="button-primary" />
				</p>
			</form>
		</div>
		<script type="text/javascript">
			function insertTab(o, e)
			{
				var kC = e.keyCode ? e.keyCode : e.charCode ? e.charCode : e.which;
				if (kC == 9 && !e.shiftKey && !e.ctrlKey && !e.altKey)
				{
					var oS = o.scrollTop;
					if (o.setSelectionRange)
					{
						var sS = o.selectionStart;
						var sE = o.selectionEnd;
						o.value = o.value.substring(0, sS) + "\t" + o.value.substr(sE);
						o.setSelectionRange(sS + 1, sS + 1);
						o.focus();
					}
					else if (o.createTextRange)
					{
						document.selection.createRange().text = "\t";
						e.returnValue = false;
					}
					o.scrollTop = oS;
					if (e.preventDefault)
					{
						e.preventDefault();
					}
					return false;
				}
				return true;
			}
		</script>
		<?php
	}

	/**
	 * Adds help tabs to help tab API.
	 *
	 * @since 1.2
	 * @return void
	 */
	public static function add_help_tab() {
		$screen = get_current_screen();

		// parent key is the id of the current screen
		// child key is the title of the tab
		// value is the help text (as HTML).
		$help = array(
			__( 'Overview', 'simple-taxonomy-refreshed' ) =>
				'<p>' . __( 'This tool allows you to import terms into an existing taxonomy.', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'The terms entered are not linked to any posts by this process.', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'As only the term name is entered, its slug is be derived from that and no description is created. These can be edited using standard WordPress functionality if required', 'simple-taxonomy-refreshed' ) . '</p>',
			__( 'Usage', 'simple-taxonomy-refreshed' )    =>
				'<p>' . __( 'By default, you are presented with a list of all publicly available taxonomies, not just those defined by this plugin.', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'The simplest option is to enter a list of terms where each one is its name. Its slug will be generated automatically.', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'This is entered as a list with no hierarchy. It is appropriate for either non-hierarchical taxonomies or a few entries of a hierarchical one.', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'You can also enter entries hierarchically into a hierarchical taxonomy. For this, you need to indent the entries to identify the hierarchy of terms wanted.', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'In particular, you may want to add terms into an existing hierarchy. So to identify where the entries are to go, you need to enter the existing term (either name or slug) in the list with no indent - and then to follow it by the sub-tree of terms to be added.', 'simple-taxonomy-refreshed' ) . '</p><p>' .
				__( 'An implication of this is that it tries to identify if the term entered already exists and does not try to enter it again.', 'simple-taxonomy-refreshed' ) . '</p>' .
				__( 'Thus the count of terms added displayed will not be the number of lines present.', 'simple-taxonomy-refreshed' ) . '</p><p>',
			__( 'Terms migrate', 'simple-taxonomy-refreshed' ) =>
				'<p>' . __( 'The migrate tool allows you to copy existing terms of a taxonomy to pre-populate this screen for populating a taxonomy.', 'simple-taxonomy-refreshed' ) . '</p>',
		);

		// loop through each tab in the help array and add.
		foreach ( $help as $title => $content ) {
			$screen->add_help_tab(
				array(
					'title'   => $title,
					'id'      => str_replace( ' ', '_', $title ),
					'content' => $content,
				)
			);
		}

		// add help sidebar.
		SimpleTaxonomyRefreshed_Admin::add_help_sidebar();
	}
}
