<?php
/**
 * WordPress Administration for Widget Areas
 * Interface functions
 *
 * @version 2.0.0
 *
 * @package WordPress
 * @subpackage Administration
 */

/** Load WordPress Administration Bootstrap */
// require_once( './admin.php' );
require_once( ABSPATH . 'wp-admin/includes/widgets.php' );
// Load all the nav menu interface functions
require_once( ABSPATH . 'wp-admin/includes/nav-menu.php' );

$this_page = add_query_arg( array( 'page' => 'wordpress-widgets-refresh' ), admin_url( 'themes.php' ) );

if ( ! current_theme_supports( 'menus' ) && ! current_theme_supports( 'widgets' ) )
	wp_die( __( 'Your theme does not support navigation menus or widgets.' ) );

// Permissions Check
if ( ! current_user_can('edit_theme_options') )
	wp_die( __( 'Cheatin&#8217; uh?' ) );

wp_enqueue_script( 'nav-menu' );

if ( wp_is_mobile() )
	wp_enqueue_script( 'jquery-touch-punch' );

// Container for any messages displayed to the user
$messages = array();

// Container that stores the name of the active menu
$nav_menu_selected_title = '';

// The menu id of the current menu being edited
$nav_menu_selected_id = 0;
$widget_area_selected_id = isset( $_REQUEST['widget-area'] ) ? $_REQUEST['widget-area'] : 0;

// Get existing menu locations assignments
$locations = get_registered_nav_menus();
$menu_locations = get_nav_menu_locations();
global $wp_registered_sidebars;
$num_locations = count( $wp_registered_sidebars );

$widget_area_selected_id = $widget_area_selected_id ? $widget_area_selected_id : array_shift( array_keys( $wp_registered_sidebars ) );

// Get all nav menus
$nav_menus = wp_get_nav_menus( array('orderby' => 'name') );
$menu_count = count( $nav_menus );

// Are we on the add new screen?
$add_new_screen = ( isset( $_GET['widget-area'] ) && '0' == $_GET['widget-area'] ) ? true : false;

$locations_screen = ( isset( $_GET['action'] ) && 'locations' == $_GET['action'] ) ? true : false;

// If we have one theme location, and zero menus, we take them right into editing their first menu
$page_count = wp_count_posts( 'page' );
$one_theme_location_no_menus = ( 1 == count( get_registered_nav_menus() ) && ! $add_new_screen && empty( $nav_menus ) && ! empty( $page_count->publish ) ) ? true : false;

$nav_menus_l10n = array(
	'oneThemeLocationNoMenus' => $one_theme_location_no_menus,
	'moveUp'       => __( 'Move up one' ),
	'moveDown'     => __( 'Move down one' ),
	'moveToTop'    => __( 'Move to the top' ),
	/* translators: %s: previous item name */
	'moveUnder'    => __( 'Move under %s' ),
	/* translators: %s: previous item name */
	'moveOutFrom'  => __( 'Move out from under %s' ),
	/* translators: %s: previous item name */
	'under'        => __( 'Under %s' ),
	/* translators: %s: previous item name */
	'outFrom'      => __( 'Out from under %s' ),
	/* translators: 1: item name, 2: item position, 3: total number of items */
	'menuFocus'    => __( '%1$s. Widget Area item %2$d of %3$d.' ),
	/* translators: 1: item name, 2: item position, 3: parent item name */
	'subMenuFocus' => __( '%1$s. Sub item number %2$d under %3$s.' ),
);
wp_localize_script( 'nav-menu', 'menus', $nav_menus_l10n );

// Redirect to add screen if there are no menus and this users has either zero, or more than 1 theme locations
// if ( 0 == $menu_count && ! $add_new_screen && ! $one_theme_location_no_menus )
// 	wp_redirect( admin_url( 'nav-menus.php?action=edit&menu=0' ) );

// Get recently edited nav menu
$recently_edited = absint( get_user_option( 'nav_menu_recently_edited' ) );
if ( empty( $recently_edited ) && is_nav_menu( $nav_menu_selected_id ) )
	$recently_edited = $nav_menu_selected_id;

// Use $recently_edited if none are selected
if ( empty( $nav_menu_selected_id ) && ! isset( $_GET['widget-area'] ) && is_nav_menu( $recently_edited ) )
	$nav_menu_selected_id = $recently_edited;

// On deletion of menu, if another menu exists, show it
if ( ! $add_new_screen && 0 < $menu_count && isset( $_GET['action'] ) && 'delete' == $_GET['action'] )
	$nav_menu_selected_id = $nav_menus[0]->term_id;

// Set $nav_menu_selected_id to 0 if no menus
if ( $one_theme_location_no_menus ) {
	$nav_menu_selected_id = 0;
} elseif ( empty( $nav_menu_selected_id ) && ! empty( $nav_menus ) && ! $add_new_screen ) {
	// if we have no selection yet, and we have menus, set to the first one in the list
	$nav_menu_selected_id = $nav_menus[0]->term_id;
}

// Update the user's setting
if ( $nav_menu_selected_id != $recently_edited && is_nav_menu( $nav_menu_selected_id ) )
	update_user_meta( $current_user->ID, 'nav_menu_recently_edited', $nav_menu_selected_id );

// If there's a menu, get its name.
if ( ! $nav_menu_selected_title && is_nav_menu( $nav_menu_selected_id ) ) {
	$_menu_object = wp_get_nav_menu_object( $nav_menu_selected_id );
	$nav_menu_selected_title = ! is_wp_error( $_menu_object ) ? $_menu_object->name : '';
}

// Generate truncated menu names
foreach( (array) $nav_menus as $key => $_nav_menu ) {
	$nav_menus[$key]->truncated_name = wp_html_excerpt( $_nav_menu->name, 40, '&hellip;' );
}

// Retrieve menu locations
if ( current_theme_supports( 'menus' ) ) {
	$locations = get_registered_nav_menus();
	$menu_locations = get_nav_menu_locations();
}

// Ensure the user will be able to scroll horizontally
// by adding a class for the max menu depth.
global $_wp_nav_menu_max_depth;
$_wp_nav_menu_max_depth = 0;

// Calling wp_get_nav_menu_to_edit generates $_wp_nav_menu_max_depth
if ( is_nav_menu( $nav_menu_selected_id ) ) {
	$menu_items = wp_get_nav_menu_items( $nav_menu_selected_id, array( 'post_status' => 'any' ) );
	// $edit_markup = wp_get_nav_menu_to_edit( $nav_menu_selected_id );
	// $edit_markup = wp_get_widget_area_to_edit( $widget_area_selected_id );
}

function wp_nav_menu_max_depth($classes) {
	global $_wp_nav_menu_max_depth;
	return "$classes menu-max-depth-$_wp_nav_menu_max_depth";
}

add_filter('admin_body_class', 'wp_nav_menu_max_depth');

wp_nav_menu_setup();
wp_initial_nav_menu_meta_boxes();

if ( ! current_theme_supports( 'menus' ) && ! $num_locations )
	$messages[] = '<div id="message" class="updated"><p>' . sprintf( __( 'Your theme does not natively support menus, but you can use them in sidebars by adding a &#8220;Custom Menu&#8221; widget on the <a href="%s">Widgets</a> screen.' ), admin_url( 'widgets.php' ) ) . '</p></div>';

if ( ! $locations_screen ) : // Main tab
	$overview  = '<p>' . __( 'This screen is used for managing your custom navigation menus.' ) . '</p>';
	$overview .= '<p>' . sprintf( __( 'Widget Areas can be displayed in locations defined by your theme, even used in sidebars by adding a &#8220;Custom Widget Area&#8221; widget on the <a href="%1$s">Widgets</a> screen. If your theme does not support the custom menus feature (the default themes, %2$s and %3$s, do), you can learn about adding this support by following the Documentation link to the side.' ), admin_url( 'widgets.php' ), 'Twenty Thirteen', 'Twenty Twelve' ) . '</p>';
	$overview .= '<p>' . __( 'From this screen you can:' ) . '</p>';
	$overview .= '<ul><li>' . __( 'Create, edit, and delete menus' ) . '</li>';
	$overview .= '<li>' . __( 'Add, organize, and modify individual menu items' ) . '</li></ul>';

	get_current_screen()->add_help_tab( array(
		'id'      => 'overview',
		'title'   => __( 'Overview' ),
		'content' => $overview
	) );

	$menu_management  = '<p>' . __( 'The menu management box at the top of the screen is used to control which menu is opened in the editor below.' ) . '</p>';
	$menu_management .= '<ul><li>' . __( 'To edit an existing menu, <strong>choose a menu from the drop down and click Select</strong>' ) . '</li>';
	$menu_management .= '<li>' . __( 'If you haven&#8217;t yet created any menus, <strong>click the &#8217;create a new menu&#8217; link</strong> to get started' ) . '</li></ul>';
	$menu_management .= '<p>' . __( 'You can assign theme locations to individual menus by <strong>selecting the desired settings</strong> at the bottom of the menu editor. To assign menus to all theme locations at once, <strong>visit the Manage Locations tab</strong> at the top of the screen.' ) . '</p>';

	get_current_screen()->add_help_tab( array(
		'id'      => 'menu-management',
		'title'   => __( 'Widget Area Management' ),
		'content' => $menu_management
	) );

	$editing_menus  = '<p>' . __( 'Each custom menu may contain a mix of links to pages, categories, custom URLs or other content types. Widget Area links are added by selecting items from the expanding boxes in the left-hand column below.' ) . '</p>';
	$editing_menus .= '<p>' . __( '<strong>Clicking the arrow to the right of any menu item</strong> in the editor will reveal a standard group of settings. Additional settings such as link target, CSS classes, link relationships, and link descriptions can be enabled and disabled via the Screen Options tab.' ) . '</p>';
	$editing_menus .= '<ul><li>' . __( 'Add one or several items at once by <strong>selecting the checkbox next to each item and clicking Add to Widget Area</strong>' ) . '</li>';
	$editing_menus .= '<li>' . __( 'To add a custom link, <strong>expand the Links section, enter a URL and link text, and click Add to Widget Area</strong>' ) .'</li>';
	$editing_menus .= '<li>' . __( 'To reorganize menu items, <strong>drag and drop items with your mouse or use your keyboard</strong>. Drag or move a menu item a little to the right to make it a submenu' ) . '</li>';
	$editing_menus .= '<li>' . __( 'Delete a menu item by <strong>expanding it and clicking the Remove link</strong>' ) . '</li></ul>';

	get_current_screen()->add_help_tab( array(
		'id'      => 'editing-menus',
		'title'   => __( 'Editing Widget Areas' ),
		'content' => $editing_menus
	) );
else : // Locations Tab
	$locations_overview  = '<p>' . __( 'This screen is used for globally assigning menus to locations defined by your theme.' ) . '</p>';
	$locations_overview .= '<ul><li>' . __( 'To assign menus to one or more theme locations, <strong>select a menu from each location&#8217;s drop down.</strong> When you&#8217;re finished, <strong>click Save Changes</strong>' ) . '</li>';
	$locations_overview .= '<li>' . __( 'To edit a menu currently assigned to a theme location, <strong>click the adjacent &#8217;Edit&#8217; link</strong>' ) . '</li>';
	$locations_overview .= '<li>' . __( 'To add a new menu instead of assigning an existing one, <strong>click the &#8217;Use new menu&#8217; link</strong>. Your new menu will be automatically assigned to that theme location' ) . '</li></ul>';

	get_current_screen()->add_help_tab( array(
		'id'      => 'locations-overview',
		'title'   => __( 'Overview' ),
		'content' => $locations_overview
	) );
endif;

get_current_screen()->set_help_sidebar(
	'<p><strong>' . __('For more information:') . '</strong></p>' .
	'<p>' . __('<a href="http://codex.wordpress.org/Appearance_Menus_Screen" target="_blank">Documentation on Widget Areas</a>') . '</p>' .
	'<p>' . __('<a href="http://wordpress.org/support/" target="_blank">Support Forums</a>') . '</p>'
);

// Get the admin header
require_once( './admin-header.php' );
?>
<div class="wrap widget-areas-php nav-menus-php">
	<?php screen_icon(); ?>
	<h2 class="nav-tab-wrapper">
		<a href="<?php echo $this_page ?>" class="nav-tab<?php if ( ! isset( $_GET['action'] ) || isset( $_GET['action'] ) && 'locations' != $_GET['action'] ) echo ' nav-tab-active'; ?>"><?php esc_html_e( 'Edit Widget Areas' ); ?></a>
		<?php if ( $num_locations && $menu_count && 1 == 2 /* disable for now */ ) : ?>
			<a href="<?php echo esc_url( add_query_arg( array( 'action' => 'locations' ), admin_url( 'nav-menus.php' ) ) ); ?>" class="nav-tab<?php if ( $locations_screen ) echo ' nav-tab-active'; ?>"><?php esc_html_e( 'Manage Locations' ); ?></a>
		<?php endif; ?>
	</h2>
	<?php
	foreach( $messages as $message ) :
		echo $message . "\n";
	endforeach;
	?>
	<?php
	if ( $locations_screen ) :
		echo '<p>' . sprintf( _n( 'Your theme supports %s menu. Select which menu you would like to use.', 'Your theme supports %s menus. Select which menu appears in each location.', $num_locations ), number_format_i18n( $num_locations ) ) . '</p>';
	?>
	<div id="menu-locations-wrap">
		<form method="post" action="<?php echo esc_url( add_query_arg( array( 'action' => 'locations' ), admin_url( 'nav-menus.php' ) ) ); ?>">
			<table class="widefat fixed" cellspacing="0" id="menu-locations-table">
				<thead>
				<tr>
					<th scope="col" class="manage-column column-locations"><?php _e( 'Theme Location' ); ?></th>
					<th scope="col" class="manage-column column-menus"><?php _e( 'Assigned Widget Area' ); ?></th>
				</tr>
				</thead>
				<!--<tfoot>
				<tr>
					<th scope="col" class="manage-column column-locations"><?php _e( 'Theme Location' ); ?></th>
					<th scope="col" class="manage-column column-menus"><?php _e( 'Assigned Widget Area' ); ?></th>
				</tr>
				</tfoot>-->
				<tbody class="menu-locations">
				<?php foreach ( $wp_registered_sidebars as $_location => $_name ) { ?>
					<tr id="menu-locations-row">
						<td class="menu-location-title"><strong><?php echo $_name; ?></strong></td>
						<td class="menu-location-menus">
							<select name="menu-locations[<?php echo $_location; ?>]" id="locations-<?php echo $_location; ?>">
								<option value="0"><?php printf( '&mdash; %s &mdash;', esc_html__( 'Select a Widget Area' ) ); ?></option>
								<?php foreach ( $nav_menus as $menu ) : ?>
									<?php $selected = isset( $menu_locations[$_location] ) && $menu_locations[$_location] == $menu->term_id; ?>
									<option <?php if ( $selected ) echo 'data-orig="true"'; ?> <?php selected( $selected ); ?> value="<?php echo $menu->term_id; ?>">
										<?php echo wp_html_excerpt( $menu->name, 40, '&hellip;' ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<div class="locations-row-links">
								<?php if ( isset( $menu_locations[ $_location ] ) && 0 != $menu_locations[ $_location ] ) : ?>
								<span class="locations-edit-menu-link">
									<a href="<?php echo esc_url( add_query_arg( array( 'action' => 'edit', 'widget-area' => $menu_locations[$_location] ), admin_url( 'nav-menus.php' ) ) ); ?>">
										<?php _ex( 'Edit', 'widget-area' ); ?>
									</a>
								</span>
								<?php endif; ?>
								<span class="locations-add-menu-link">
									<a href="<?php echo esc_url( add_query_arg( array( 'action' => 'edit', 'widget-area' => 0, 'use-location' => $_location ), admin_url( 'nav-menus.php' ) ) ); ?>">
										<?php _ex( 'Use new menu', 'widget-area' ); ?>
									</a>
								</span>
							</div><!-- #locations-row-links -->
						</td><!-- .menu-location-menus -->
					</tr><!-- #menu-locations-row -->
				<?php } // foreach ?>
				</tbody>
			</table>
			<p class="button-controls"><?php submit_button( __( 'Save Changes' ), 'primary left', 'nav-menu-locations', false ); ?></p>
			<?php wp_nonce_field( 'save-menu-locations' ); ?>
			<input type="hidden" name="menu" id="nav-menu-meta-object-id" value="<?php echo esc_attr( $nav_menu_selected_id ); ?>" />
		</form>
	</div><!-- #menu-locations-wrap -->
	<?php do_action( 'after_menu_locations_table' ); ?>
	<?php else : ?>
	<div class="manage-menus">
 		<?php if ( empty( $wp_registered_sidebars ) ) : ?>
		<span class="add-edit-menu-action">
			<?php printf( __( 'Edit your widget-area below, or <a href="%s">create a new widget-area</a>.' ), esc_url( add_query_arg( array( 'action' => 'edit', 'widget-area' => 0 ), admin_url( 'nav-menus.php' ) ) ) ); ?>
		</span><!-- /add-edit-menu-action -->
		<?php else : ?>
			<form method="get" action="<?php echo $this_page ?>">
			<input type="hidden" name="action" value="edit" />
			<input type="hidden" name="page" value="wordpress-widgets-refresh" />
			<label for="menu" class="selected-menu"><?php _e( 'Select a menu to edit:' ); ?></label>
			<select name="widget-area" id="menu">
				<!-- <option value=""><?php _e( 'Select Widget Area', 'wds' ); ?></option> -->
				<?php
				foreach ( $wp_registered_sidebars as $id => $sidebar ) {
					// if (
					// 	$sidebar['id'] == 'wp_inactive_widgets'
					// 	|| ( false !== strpos( $sidebar['id'], 'orphaned_widgets' ) )
					// )
					// 	continue;
					echo '<option value="'. $sidebar['id'] .'" ',
					selected( $widget_area_selected_id, $id ),
					'>'. $sidebar['name'] .'</option>';
				}
				?>
			</select>
			<span class="submit-btn"><input type="submit" class="button-secondary" value="<?php _e( 'Select' ); ?>"></span>
			<span class="add-new-menu-action">
				<?php printf( __( 'or <a href="%s">create a new menu</a>.' ), esc_url( add_query_arg( array( 'action' => 'edit', 'widget-area' => 0 ), admin_url( 'nav-menus.php' ) ) ) ); ?>
			</span><!-- /add-new-menu-action -->
		</form>
	<?php endif; ?>
	</div><!-- /manage-menus -->
	<div id="nav-menus-frame">
	<div id="menu-settings-column" class="metabox-holder<?php if ( isset( $_GET['widget-area'] ) && '0' == $_GET['widget-area'] ) { echo ' metabox-holder-disabled'; } ?>">

		<div class="clear"></div>

		<form id="nav-menu-meta" action="" class="nav-menu-meta" method="post" enctype="multipart/form-data">
			<input type="hidden" name="menu" id="nav-menu-meta-object-id" value="<?php echo esc_attr( $nav_menu_selected_id ); ?>" />
			<input type="hidden" name="action" value="add-menu-item" />
			<?php wp_nonce_field( 'add-menu_item', 'menu-settings-column-nonce' ); ?>
			<?php // do_accordion_sections( 'nav-menus', 'side', null ); ?>
		</form>

	</div><!-- /#menu-settings-column -->
	<div id="menu-management-liquid">
		<div id="menu-management">
			<form id="update-nav-menu" action="" method="post" enctype="multipart/form-data">
				<div class="menu-edit <?php if ( $add_new_screen ) echo 'blank-slate'; ?>">
					<?php
					wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
					wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );
					wp_nonce_field( 'update-nav_menu', 'update-nav-menu-nonce' );

					if ( $one_theme_location_no_menus ) { ?>
						<input type="hidden" name="zero-menu-state" value="true" />
					<?php } ?>
 					<input type="hidden" name="action" value="update" />
					<input type="hidden" name="menu" id="menu" value="<?php echo esc_attr( $nav_menu_selected_id ); ?>" />
					<div id="nav-menu-header">
						<div class="major-publishing-actions">
							<label class="menu-name-label howto open-label" for="menu-name">
								<span><?php _e( 'Widget Area Name' ); ?></span>
								<input name="menu-name" id="menu-name" type="text" class="menu-name regular-text menu-item-textbox input-with-default-title" title="<?php esc_attr_e( 'Enter menu name here' ); ?>" value="<?php if ( $one_theme_location_no_menus ) _e( 'Widget Area 1' ); else echo esc_attr( $nav_menu_selected_title ); ?>" />
							</label>
							<div class="publishing-action">
								<?php submit_button( empty( $nav_menu_selected_id ) ? __( 'Create Widget Area' ) : __( 'Save Widget Area' ), 'button-primary menu-save', 'save_menu', false, array( 'id' => 'save_menu_header' ) ); ?>
							</div><!-- END .publishing-action -->
						</div><!-- END .major-publishing-actions -->
					</div><!-- END .nav-menu-header -->
					<div id="post-body">
						<div id="post-body-content">
							<?php if ( ! $add_new_screen ) : ?>
							<h3><?php _e( 'Widget Area Order' ); ?></h3>
							<?php $starter_copy = ( $one_theme_location_no_menus ) ? __( 'Edit your default menu by adding or removing items. Drag each item into the order you prefer. Click Create Widget Area to save your changes.' ) : __( 'Drag widgets into the order you prefer. Click the settings cog on the right to access its settings.' ); ?>
							<div class="drag-instructions post-body-plain" <?php if ( isset( $menu_items ) && 0 == count( $menu_items ) ) { ?>style="display: none;"<?php } ?>>
								<p><?php echo $starter_copy; ?></p>
							</div>
							<?php
							if ( 1 == 1 /*isset( $edit_markup ) && ! is_wp_error( $edit_markup )*/ ) {
								// echo $edit_markup;
								?>
								<div id="menu-instructions" class="post-body-plain menu-instructions-inactive"><p>Add menu items from the column on the left.</p></div>
								<?php
									// wp_list_widgets_();
									wp_list_widget_controls_( $widget_area_selected_id );
							} else {
							?>
							<ul class="menu" id="menu-to-edit"></ul>
							<?php } ?>
							<?php endif; ?>
							<?php if ( $add_new_screen ) : ?>
								<p class="post-body-plain"><?php _e( 'Give your menu a name above, then click Create Widget Area.' ); ?></p>
								<?php if ( isset( $_GET['use-location'] ) ) : ?>
									<input type="hidden" name="use-location" value="<?php echo esc_attr( $_GET['use-location'] ); ?>" />
								<?php endif; ?>
							<?php endif; ?>
							<div class="menu-settings" <?php if ( $one_theme_location_no_menus || 1 == 1 ) { ?>style="display: none;"<?php } ?>>
								<h3><?php _e( 'Widget Area Settings' ); ?></h3>
								<?php
								if ( ! isset( $auto_add ) ) {
									$auto_add = get_option( 'nav_menu_options' );
									if ( ! isset( $auto_add['auto_add'] ) )
										$auto_add = false;
									elseif ( false !== array_search( $nav_menu_selected_id, $auto_add['auto_add'] ) )
										$auto_add = true;
									else
										$auto_add = false;
								} ?>

								<?php if ( current_theme_supports( 'menus' ) && 1 == 2 ) : ?>

									<dl class="menu-theme-locations">
										<dt class="howto"><?php _e( 'Theme locations' ); ?></dt>
										<?php foreach ( $locations as $location => $description ) : ?>
										<dd class="checkbox-input">
											<input type="checkbox"<?php checked( isset( $menu_locations[ $location ] ) && $menu_locations[ $location ] == $nav_menu_selected_id ); ?> name="menu-locations[<?php echo esc_attr( $location ); ?>]" id="locations-<?php echo esc_attr( $location ); ?>" value="<?php echo esc_attr( $nav_menu_selected_id ); ?>" /> <label for="locations-<?php echo esc_attr( $location ); ?>"><?php echo $description; ?></label>
											<?php if ( ! empty( $menu_locations[ $location ] ) && $menu_locations[ $location ] != $nav_menu_selected_id ) : ?>
											<span class="theme-location-set"> <?php printf( __( "(Currently set to: %s)" ), wp_get_nav_menu_object( $menu_locations[ $location ] )->name ); ?> </span>
											<?php endif; ?>
										</dd>
										<?php endforeach; ?>
									</dl>

								<?php endif; ?>

							</div>
						</div><!-- /#post-body-content -->
					</div><!-- /#post-body -->
					<div id="nav-menu-footer">
						<div class="major-publishing-actions">
							<?php if ( 0 != $menu_count && ! $add_new_screen ) : ?>
							<span class="delete-action">
								<a class="submitdelete deletion menu-delete" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'delete', 'widget-area' => $nav_menu_selected_id, admin_url() ) ), 'delete-nav_menu-' . $nav_menu_selected_id) ); ?>"><?php _e('Delete Widget Area'); ?></a>
							</span><!-- END .delete-action -->
							<?php endif; ?>
							<div class="publishing-action">
								<?php submit_button( empty( $nav_menu_selected_id ) ? __( 'Create Widget Area' ) : __( 'Save Widget Area' ), 'button-primary menu-save', 'save_menu', false, array( 'id' => 'save_menu_header' ) ); ?>
							</div><!-- END .publishing-action -->
						</div><!-- END .major-publishing-actions -->
					</div><!-- /#nav-menu-footer -->
				</div><!-- /.menu-edit -->
			</form><!-- /#update-nav-menu -->
		</div><!-- /#menu-management -->
	</div><!-- /#menu-management-liquid -->
	</div><!-- /#nav-menus-frame -->
	<?php endif; ?>
</div><!-- /.wrap-->


<?php

/**
 * Display list of the available widgets.
 *
 * @since 2.5.0
 */
function wp_list_widgets_() {
	global $wp_registered_widgets, $sidebars_widgets, $wp_registered_widget_controls;

	$sort = $wp_registered_widgets;
	usort( $sort, '_sort_name_callback' );
	$done = array();

	foreach ( $sort as $widget ) {
		if ( in_array( $widget['callback'], $done, true ) ) // We already showed this multi-widget
			continue;

		$sidebar = is_active_widget( $widget['callback'], $widget['id'], false, false );
		$done[] = $widget['callback'];

		if ( ! isset( $widget['params'][0] ) )
			$widget['params'][0] = array();

		$args = array( 'widget_id' => $widget['id'], 'widget_name' => $widget['name'], '_display' => 'template' );

		if ( isset($wp_registered_widget_controls[$widget['id']]['id_base']) && isset($widget['params'][0]['number']) ) {
			$id_base = $wp_registered_widget_controls[$widget['id']]['id_base'];
			$args['_temp_id'] = "$id_base-__i__";
			$args['_multi_num'] = next_widget_id_number($id_base);
			$args['_add'] = 'multi';
		} else {
			$args['_add'] = 'single';
			if ( $sidebar )
				$args['_hide'] = '1';
		}

		$args = wp_list_widget_controls_dynamic_sidebar( array( 0 => $args, 1 => $widget['params'][0] ) );
		call_user_func_array( 'wp_widget_control_', $args );
	}
}

/**
 * Meta widget used to display the control form for a widget.
 *
 * Called from dynamic_sidebar().
 *
 * @since 2.5.0
 *
 * @param array $sidebar_args
 * @return array
 */
function wp_widget_control_( $sidebar_args ) {
	global $wp_registered_widgets, $wp_registered_widget_controls, $sidebars_widgets;

	$widget_id = $sidebar_args['widget_id'];
	$sidebar_id = isset($sidebar_args['id']) ? $sidebar_args['id'] : false;
	$key = $sidebar_id ? array_search( $widget_id, $sidebars_widgets[$sidebar_id] ) : '-1'; // position of widget in sidebar
	$control = isset($wp_registered_widget_controls[$widget_id]) ? $wp_registered_widget_controls[$widget_id] : array();
	$widget = $wp_registered_widgets[$widget_id];

	$id_format = $widget['id'];
	$widget_number = isset($control['params'][0]['number']) ? $control['params'][0]['number'] : '';
	$id_base = isset($control['id_base']) ? $control['id_base'] : $widget_id;
	$multi_number = isset($sidebar_args['_multi_num']) ? $sidebar_args['_multi_num'] : '';
	$add_new = isset($sidebar_args['_add']) ? $sidebar_args['_add'] : '';

	$query_arg = array( 'editwidget' => $widget['id'] );
	if ( $add_new ) {
		$query_arg['addnew'] = 1;
		if ( $multi_number ) {
			$query_arg['num'] = $multi_number;
			$query_arg['base'] = $id_base;
		}
	} else {
		$query_arg['sidebar'] = $sidebar_id;
		$query_arg['key'] = $key;
	}

	// We aren't showing a widget control, we're outputting a template for a multi-widget control
	if ( isset($sidebar_args['_display']) && 'template' == $sidebar_args['_display'] && $widget_number ) {
		// number == -1 implies a template where id numbers are replaced by a generic '__i__'
		$control['params'][0]['number'] = -1;
		// with id_base widget id's are constructed like {$id_base}-{$id_number}
		if ( isset($control['id_base']) )
			$id_format = $control['id_base'] . '-__i__';
	}

	$wp_registered_widgets[$widget_id]['callback'] = $wp_registered_widgets[$widget_id]['_callback'];
	unset($wp_registered_widgets[$widget_id]['_callback']);

	$widget_title = esc_html( strip_tags( $sidebar_args['widget_name'] ) );
	$has_form = 'noform';

	$sidebar_args['before_widget'] = str_ireplace( array( 'div', 'widget' ), array( 'li', 'widget- menu-item menu-item-edit-inactive' ), $sidebar_args['before_widget'] );
	$sidebar_args['after_widget'] = str_ireplace( 'div', 'li', $sidebar_args['after_widget'] );

	echo $sidebar_args['before_widget']; ?>
	<dl class="menu-item-bar">
		<dt class="menu-item-handle">
			<span class="item-title"><span class="menu-item-title"><?php echo $widget_title ?></span> <span class="is-submenu" style="display: none;">sub item</span></span>
			<span class="item-controls">
				<a class="item-edit" id="edit-3913" title="Edit Widget" href="#">Edit Widget</a>
			</span>
		</dt>
	</dl>
	<ul class="menu-item-transport"></ul>

<?php
	echo $sidebar_args['after_widget'];
	return $sidebar_args;
}


/**
 * Show the widgets and their settings for a sidebar.
 * Used in the admin widget config screen.
 *
 * @since 2.5.0
 *
 * @param string $sidebar id slug of the sidebar
 */
function wp_list_widget_controls_( $sidebar ) {
	add_filter( 'dynamic_sidebar_params', 'wp_list_widget_controls_dynamic_sidebar_' );

	echo "<div id='$sidebar' class='widgets-sortables'>\n";

	$description = wp_sidebar_description( $sidebar );

	if ( !empty( $description ) ) {
		echo "<div class='sidebar-description'>\n";
		echo "\t<p class='description'>$description</p>";
		echo "</div>\n";
	}
	echo '<ul class="menu widget-area ui-sortable" id="menu-to-edit">'."\n";
	dynamic_sidebar( $sidebar );
	echo "</ul>\n";
	echo "</div>\n";
}

/**
 * {@internal Missing Short Description}}
 *
 * @since 2.5.0
 *
 * @param array $params
 * @return array
 */
function wp_list_widget_controls_dynamic_sidebar_( $params ) {
	global $wp_registered_widgets;
	static $i = 0;
	$i++;

	$widget_id = $params[0]['widget_id'];
	$id = isset($params[0]['_temp_id']) ? $params[0]['_temp_id'] : $widget_id;
	$hidden = isset($params[0]['_hide']) ? ' style="display:none;"' : '';

	$params[0]['before_widget'] = "<li id='widget-{$i}_{$id}' class='widget'$hidden>";
	$params[0]['after_widget'] = "</li>";
	$params[0]['before_title'] = "%BEG_OF_TITLE%"; // deprecated
	$params[0]['after_title'] = "%END_OF_TITLE%"; // deprecated
	if ( is_callable( $wp_registered_widgets[$widget_id]['callback'] ) ) {
		$wp_registered_widgets[$widget_id]['_callback'] = $wp_registered_widgets[$widget_id]['callback'];
		$wp_registered_widgets[$widget_id]['callback'] = 'wp_widget_control_';
	}

	return $params;
}