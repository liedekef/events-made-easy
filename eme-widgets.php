<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// enable shortcodes in widgets, if wanted
if ( get_option( 'eme_shortcodes_in_widgets' ) ) {
	add_filter( 'widget_text', 'do_shortcode', 11 );
}

class WP_Widget_eme_list extends WP_Widget {

	public function __construct() {
		parent::__construct(
		    'eme_list', // Base ID
			__( 'Events Made Easy List of events', 'events-made-easy' ), // Name
			[ 'description' => __( 'Events Made Easy List of events', 'events-made-easy' ) ] // Args
		);
	}

	public function widget( $args, $instance ) {
		eme_enqueue_frontend();
		//$title = apply_filters('widget_title', empty( $instance['title'] ) ? __( 'Events','eme' ) : $instance['title'], $instance, $this->id_base);
		//$title = apply_filters('widget_title', $instance['title'], $instance, $this->id_base);
		$title                = apply_filters( 'widget_title', $instance['title'] );
		$limit                = isset( $instance['limit'] ) ? intval( $instance['limit'] ) : 5;
		$scope                = empty( $instance['scope'] ) ? 'future' : $instance['scope'];
		$showperiod           = empty( $instance['showperiod'] ) ? '' : $instance['showperiod'];
		$show_ongoing         = empty( $instance['show_ongoing'] ) ? false : true;
		$order                = empty( $instance['order'] ) ? 'ASC' : $instance['order'];
		$header               = empty( $instance['header'] ) ? '<ul>' : $instance['header'];
		$footer               = empty( $instance['footer'] ) ? '</ul>' : $instance['footer'];
		$category             = empty( $instance['category'] ) ? '' : $instance['category'];
		$notcategory          = empty( $instance['notcategory'] ) ? '' : $instance['notcategory'];
		$recurrence_only_once = empty( $instance['recurrence_only_once'] ) ? false : $instance['recurrence_only_once'];
		if ( eme_is_empty_string( $instance['format'] ) && empty( $instance['format_tpl'] ) ) {
			$format = DEFAULT_WIDGET_EVENT_LIST_ITEM_FORMAT;
		} elseif ( eme_is_empty_string( $instance['format'] ) ) {
			$format = eme_get_template_format( $instance['format_tpl'] );
		} else {
			$format = $instance['format'];
		}
		$format_tpl = isset( $instance['format_tpl'] ) ? intval( $instance['format_tpl'] ) : 0;

		if ( $instance['authorid'] == -1 ) {
			$author = '';
		} else {
			$authinfo = get_userdata( $instance['authorid'] );
			$author   = $authinfo ? $authinfo->user_login : '';
		}
		echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WP core widget arg
		if ( $title ) {
			echo $args['before_title'] . $title . $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WP core widget args
		}

		if ( is_array( $category ) ) {
			$category = implode( ',', $category );
		}
		if ( is_array( $notcategory ) ) {
			$notcategory = implode( '+', $notcategory );
		}

		$events_list = eme_get_events_list( limit: $limit, scope: $scope, order: $order, format: $format, category: $category, showperiod: $showperiod, author: $author, show_ongoing: $show_ongoing, show_recurrent_events_once: $recurrence_only_once, notcategory: $notcategory, template_id: $format_tpl );
		if ( strstr( $events_list, 'events-no-events' ) ) {
			echo $events_list; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted plugin HTML
		} else {
			echo $header . $events_list . $footer; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted plugin HTML
		}
		echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WP core widget arg
	}

	public function update( $new_instance, $old_instance ) {
		// before the merge, let's set the values of those elements that are checkboxes or multiselects (not returned in the POST if not selected)
		if ( ! isset( $new_instance['recurrence_only_once'] ) ) {
			$new_instance['recurrence_only_once'] = false;
		}
		if ( ! isset( $new_instance['show_ongoing'] ) ) {
			$new_instance['show_ongoing'] = false;
		}
		if ( ! isset( $new_instance['category'] ) ) {
			$new_instance['category'] = '';
		}
		if ( ! isset( $new_instance['notcategory'] ) ) {
			$new_instance['notcategory'] = '';
		}

		$instance          = array_merge( $old_instance, $new_instance );
		$instance['title'] = wp_strip_all_tags( $instance['title'] );
		$instance['limit'] = intval( $instance['limit'] );
		if ( ! in_array( $instance['showperiod'], [ 'daily', 'monthly', 'yearly' ] ) ) {
			$instance['showperiod'] = '';
		}
		if ( ! in_array( $instance['order'], [ 'ASC', 'DESC' ] ) ) {
			$instance['order'] = 'ASC';
		}
		return $instance;
	}

	public function form( $instance ) {
		//Defaults
		$instance   = wp_parse_args(
		    (array) $instance,
		    [
				'limit'        => 5,
				'scope'        => 'future',
				'order'        => 'ASC',
				'format'       => '',
				'format_tpl'   => 0,
				'authorid'     => '',
				'show_ongoing' => 1,
			]
		);
		$title      = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
		$format_tpl = isset( $instance['format_tpl'] ) ? intval( $instance['format_tpl'] ) : 0;
		$limit      = isset( $instance['limit'] ) ? intval( $instance['limit'] ) : 5;
		$scope      = empty( $instance['scope'] ) ? 'future' : eme_esc_html( $instance['scope'] );
		$showperiod = empty( $instance['showperiod'] ) ? '' : eme_esc_html( $instance['showperiod'] );
		if ( isset( $instance['show_ongoing'] ) && ( $instance['show_ongoing'] != false ) ) {
			$show_ongoing = true;
		} else {
			$show_ongoing = false;
		}
		$order                = empty( $instance['order'] ) ? 'ASC' : eme_esc_html( $instance['order'] );
		$header               = empty( $instance['header'] ) ? '<ul>' : eme_esc_html( $instance['header'] );
		$footer               = empty( $instance['footer'] ) ? '</ul>' : eme_esc_html( $instance['footer'] );
		$category             = empty( $instance['category'] ) ? '' : eme_esc_html( $instance['category'] );
		$notcategory          = empty( $instance['notcategory'] ) ? '' : eme_esc_html( $instance['notcategory'] );
		$recurrence_only_once = empty( $instance['recurrence_only_once'] ) ? '' : eme_esc_html( $instance['recurrence_only_once'] );
		$authorid             = empty( $instance['authorid'] ) ? '' : eme_esc_html( $instance['authorid'] );
		$categories           = eme_get_categories();
		$option_categories    = [];
		foreach ( $categories as $cat ) {
			$id                       = $cat['category_id'];
			$option_categories[ $id ] = $cat['category_name'];
		}
		if ( empty( $instance['format_tpl'] ) && eme_is_empty_string( $instance['format'] ) ) {
			$format = eme_esc_html( DEFAULT_WIDGET_EVENT_LIST_ITEM_FORMAT );
		} elseif ( empty( $instance['format_tpl'] ) && ! eme_is_empty_string( $instance['format'] ) ) {
			$format = eme_esc_html( $instance['format'] );
		} else {
			$format = '';
		}

		$templates_array = eme_get_templates_array_by_id( 'event' );
		?>
	<p>
	<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'events-made-easy' ); ?></label>
	<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
	</p>
	<p>
	<label for="<?php echo esc_attr( $this->get_field_id( 'limit' ) ); ?>"><?php esc_html_e( 'Number of events', 'events-made-easy' ); ?>: </label>
	<input type="text" id="<?php echo esc_attr( $this->get_field_id( 'limit' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'limit' ) ); ?>" value="<?php echo $limit; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- already intval() on line 116 ?>">
	</p>
	<p>
	<label for="<?php echo esc_attr( $this->get_field_id( 'scope' ) ); ?>"><?php esc_html_e( 'Scope of the events', 'events-made-easy' ); ?><br><?php esc_html_e( '(See the doc for &#91;eme_events] for all possible values)', 'events-made-easy' ); ?>:</label><br>
	<input type="text" id="<?php echo esc_attr( $this->get_field_id( 'scope' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'scope' ) ); ?>" value="<?php echo $scope; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- already eme_esc_html() on line 117 ?>">
	</p>
	<p>
	<label for="<?php echo esc_attr( $this->get_field_id( 'showperiod' ) ); ?>"><?php esc_html_e( 'Show events per period', 'events-made-easy' ); ?>:</label><br>
	<select id="<?php echo esc_attr( $this->get_field_id( 'showperiod' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'showperiod' ) ); ?>">
		<option value="" <?php selected( $showperiod, '' ); ?>><?php esc_html_e( 'Select...', 'events-made-easy' ); ?></option>
		<option value="daily" <?php selected( $showperiod, 'daily' ); ?>><?php esc_html_e( 'Daily', 'events-made-easy' ); ?></option>
		<option value="monthly" <?php selected( $showperiod, 'monthly' ); ?>><?php esc_html_e( 'Monthly', 'events-made-easy' ); ?></option>
		<option value="yearly" <?php selected( $showperiod, 'yearly' ); ?>><?php esc_html_e( 'Yearly', 'events-made-easy' ); ?></option>
	</select>
	</p>
	<p>
	<label for="<?php echo esc_attr( $this->get_field_id( 'order' ) ); ?>"><?php esc_html_e( 'Order of the events', 'events-made-easy' ); ?>:</label><br>
	<select id="<?php echo esc_attr( $this->get_field_id( 'order' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'order' ) ); ?>">
		<option value="ASC" <?php selected( $order, 'ASC' ); ?>><?php esc_html_e( 'Ascendant', 'events-made-easy' ); ?></option>
		<option value="DESC" <?php selected( $order, 'DESC' ); ?>><?php esc_html_e( 'Descendant', 'events-made-easy' ); ?></option>
	</select>
	</p>
		<?php
		if ( get_option( 'eme_categories_enabled' ) ) {
			?>
	<p>
	<label for="<?php echo esc_attr( $this->get_field_id( 'category' ) ); ?>"><?php esc_html_e( 'Category', 'events-made-easy' ); ?>:</label><br>
	<select id="<?php echo esc_attr( $this->get_field_id( 'category' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'category' ) ); ?>[]" multiple="multiple">
			<?php
					eme_option_items( $option_categories, $category );
			?>
	</select>
	</p>
	<p>
	<label for="<?php echo esc_attr( $this->get_field_id( 'notcategory' ) ); ?>"><?php esc_html_e( 'Exclude Category', 'events-made-easy' ); ?>:</label><br>
	<select id="<?php echo esc_attr( $this->get_field_id( 'notcategory' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'notcategory' ) ); ?>[]" multiple="multiple">
					<?php
					eme_option_items( $option_categories, $notcategory );
					?>
	</select>
	</p>
			<?php
		}
		?>
	<p>
	<label for="<?php echo esc_attr( $this->get_field_id( 'show_ongoing' ) ); ?>"><?php esc_html_e( 'Show Ongoing Events?', 'events-made-easy' ); ?>:</label>
	<input type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'show_ongoing' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'show_ongoing' ) ); ?>" value="1" <?php echo ( $show_ongoing ) ? 'checked="checked"' : ''; ?>>
	</p>
	<p>
	<label for="<?php echo esc_attr( $this->get_field_id( 'recurrence_only_once' ) ); ?>"><?php esc_html_e( 'Show Recurrent Events Only Once?', 'events-made-easy' ); ?>:</label>
	<input type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'recurrence_only_once' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'recurrence_only_once' ) ); ?>" value="1" <?php echo ( $recurrence_only_once ) ? 'checked="checked"' : ''; ?>>
	</p>
	<p>
	<label for="<?php echo esc_attr( $this->get_field_id( 'authorid' ) ); ?>"><?php esc_html_e( 'Author', 'events-made-easy' ); ?>:</label><br>
		<?php
		wp_dropdown_users(
		    [
				'id'               => esc_attr( $this->get_field_id( 'authorid' ) ),
				'name'             => esc_attr( $this->get_field_name( 'authorid' ) ),
				'show_option_none' => __( 'Select...', 'events-made-easy' ),
				'selected'         => $authorid,
			]
		);
		?>
	</p>
	<p>
	<label for="<?php echo esc_attr( $this->get_field_id( 'header' ) ); ?>"><?php esc_html_e( 'List header format<br>(if empty &lt;ul&gt; is used)', 'events-made-easy' ); ?>: </label>
	<input type="text" id="<?php echo esc_attr( $this->get_field_id( 'header' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'header' ) ); ?>" value="<?php echo $header; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- already eme_esc_html() on line 125 ?>">
	</p>
	<p>
	<label for="<?php echo esc_attr( $this->get_field_id( 'format_tpl' ) ); ?>"><?php esc_html_e( 'List item format', 'events-made-easy' ); ?>:</label>
		<?php
		esc_html_e( 'Either choose from a template: ', 'events-made-easy' );
		echo eme_ui_select( $format_tpl, $this->get_field_name( 'format_tpl' ), $templates_array ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted HTML from eme_ui_select()
		?>
	</p> 
	<p>
	<label for="<?php echo esc_attr( $this->get_field_id( 'format' ) ); ?>"><?php esc_html_e( 'Or enter your own (if anything is entered here, it takes precedence over the selected template): ', 'events-made-easy' ); ?>:</label>
	<textarea id="<?php echo esc_attr( $this->get_field_id( 'format' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'format' ) ); ?>" rows="5" cols="24"><?php echo $format; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- already eme_esc_html() on lines 138/140 ?></textarea>
	</p> 
	<p>
	<label for="<?php echo esc_attr( $this->get_field_id( 'footer' ) ); ?>"><?php esc_html_e( 'List footer format<br>(if empty &lt;/ul&gt; is used)', 'events-made-easy' ); ?>: </label>
	<input type="text" id="<?php echo esc_attr( $this->get_field_id( 'footer' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'footer' ) ); ?>" value="<?php echo $footer; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- already eme_esc_html() on line 126 ?>">
	</p>
		<?php
	}
}

class WP_Widget_eme_calendar extends WP_Widget {

	public function __construct() {
		parent::__construct(
		    'eme_calendar', // Base ID
			__( 'Events Made Easy Calendar', 'events-made-easy' ), // Name
			[ 'description' => __( 'Events Made Easy Calendar', 'events-made-easy' ) ] // Args
		);
	}

	public function widget( $args, $instance ) {
		eme_enqueue_frontend();
		//$title = apply_filters('widget_title', empty( $instance['title'] ) ? __( 'Calendar','eme' ) : $instance['title'], $instance, $this->id_base);
		//$title = apply_filters('widget_title', $instance['title'], $instance, $this->id_base);
		if ( ! isset( $instance['title'] ) ) {
			$instance['title'] = __( 'Calendar', 'events-made-easy' );
		}
		if ( ! isset( $instance['authorid'] ) ) {
			$instance['authorid'] = -1;
		}
		$title       = apply_filters( 'widget_title', $instance['title'] );
		$long_events = empty( $instance['long_events'] ) ? 0 : 1;
		$category    = empty( $instance['category'] ) ? '' : $instance['category'];
		$notcategory = empty( $instance['notcategory'] ) ? '' : $instance['notcategory'];
		$holiday_id  = empty( $instance['holiday_id'] ) ? 0 : $instance['holiday_id'];
		if ( $instance['authorid'] == -1 ) {
			$author = '';
		} else {
			$authinfo = get_userdata( $instance['authorid'] );
			$author   = $authinfo ? $authinfo->user_login : '';
		}

		if ( is_array( $category ) ) {
			$category = implode( ',', $category );
		}
		if ( is_array( $notcategory ) ) {
			$notcategory = implode( '+', $notcategory );
		}

		// the month shown depends on the calendar day clicked
		// make sure it is a valid date though ...
		if ( get_query_var( 'calendar_day' ) && eme_is_date( get_query_var( 'calendar_day' ) ) ) {
			$eme_date_obj = new emeExpressiveDate( get_query_var( 'calendar_day' ), EME_TIMEZONE );
		} else {
			$eme_date_obj = new emeExpressiveDate( 'now', EME_TIMEZONE );
		}
		$month = $eme_date_obj->format( 'm' );
		$year  = $eme_date_obj->format( 'Y' );

		echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WP core widget arg
		if ( $title ) {
			echo $args['before_title'] . $title . $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WP core widget args
		}
		echo eme_get_calendar( long_events: $long_events, category: $category, notcategory: $notcategory, month: $month, year: $year, author: $author, holiday_id: $holiday_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted plugin HTML
		echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WP core widget arg
	}

	public function update( $new_instance, $old_instance ) {
		// before the merge, let's set the values of those elements that are checkboxes or multiselects (not returned in the POST if not selected)
		if ( ! isset( $new_instance['long_events'] ) ) {
			$new_instance['long_events'] = false;
		}
		if ( ! isset( $new_instance['category'] ) ) {
			$new_instance['category'] = '';
		}
		if ( ! isset( $new_instance['notcategory'] ) ) {
			$new_instance['notcategory'] = '';
		}
		$instance          = array_merge( $old_instance, $new_instance );
		$instance['title'] = wp_strip_all_tags( $instance['title'] );
		return $instance;
	}

	public function form( $instance ) {
		//Defaults
		$instance             = wp_parse_args( (array) $instance, [ 'long_events' => 0 ] );
		$title                = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
		$category             = empty( $instance['category'] ) ? '' : eme_esc_html( $instance['category'] );
		$notcategory          = empty( $instance['notcategory'] ) ? '' : eme_esc_html( $instance['notcategory'] );
		$long_events          = isset( $instance['long_events'] ) ? eme_esc_html( $instance['long_events'] ) : false;
		$authorid             = isset( $instance['authorid'] ) ? eme_esc_html( $instance['authorid'] ) : '';
		$holiday_id           = isset( $instance['holiday_id'] ) ? intval( $instance['holiday_id'] ) : 0;
		$categories           = eme_get_categories();
		$holidays_array_by_id = eme_get_holidays_array_by_id();
		$option_categories    = [];
		foreach ( $categories as $cat ) {
			$id                       = $cat['category_id'];
			$option_categories[ $id ] = $cat['category_name'];
		}
		?>
	<p>
	<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'events-made-easy' ); ?></label>
	<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
	</p>      
	<p>
	<label for="<?php echo esc_attr( $this->get_field_id( 'long_events' ) ); ?>"><?php esc_html_e( 'Show Long Events?', 'events-made-easy' ); ?>:</label>
	<input type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'long_events' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'long_events' ) ); ?>" value="1" <?php echo ( $long_events ) ? 'checked="checked"' : ''; ?>>
	</p>
		<?php
		if ( get_option( 'eme_categories_enabled' ) ) {
			?>
	<p>
	<label for="<?php echo esc_attr( $this->get_field_id( 'category' ) ); ?>"><?php esc_html_e( 'Category', 'events-made-easy' ); ?>:</label><br>
	<select id="<?php echo esc_attr( $this->get_field_id( 'category' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'category' ) ); ?>[]" multiple="multiple">
			<?php
			eme_option_items( $option_categories, $category );
			?>
	</select>
	</p>
	<p>
	<label for="<?php echo esc_attr( $this->get_field_id( 'notcategory' ) ); ?>"><?php esc_html_e( 'Exclude Category', 'events-made-easy' ); ?>:</label><br>
	<select id="<?php echo esc_attr( $this->get_field_id( 'notcategory' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'notcategory' ) ); ?>[]" multiple="multiple">
			<?php
			eme_option_items( $option_categories, $notcategory );
			?>
	</select>
	</p>
			<?php
		}
		if ( ! empty( $holidays_array_by_id ) ) {
			?>
	<label for="<?php echo esc_attr( $this->get_field_id( 'holiday_id' ) ); ?>"><?php esc_html_e( 'Holidays', 'events-made-easy' ); ?>:</label><br>
	<select id="<?php echo esc_attr( $this->get_field_id( 'holiday_id' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'holiday_id' ) ); ?>">
			<?php
			eme_option_items( $holidays_array_by_id, $holiday_id );
			?>
	</select>
			<?php
		}
		?>
	<p>
	<label for="<?php echo esc_attr( $this->get_field_id( 'authorid' ) ); ?>"><?php esc_html_e( 'Author', 'events-made-easy' ); ?>:</label><br>
		<?php
		wp_dropdown_users(
		    [
				'id'               => esc_attr( $this->get_field_id( 'authorid' ) ),
				'name'             => esc_attr( $this->get_field_name( 'authorid' ) ),
				'show_option_none' => __( 'Select...', 'events-made-easy' ),
				'selected'         => $authorid,
			]
		);
		?>
	</p>
		<?php
	}
}

?>
