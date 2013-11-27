<?php
/*
	Plugin Name: RichList Widget
	Plugin URL: http://richkenmedia.com
	Author: Shine Sudarsanan
	Author URI: http://richkenmedia.com
	Description: A plugin to list the categories and their posts on sidebar or content area.
	Version: 1.0
*/
?>
<?php
/**
	Starts Scripts includes Here
*/


function rich_list_includes() {
	$rich_plugin_path = get_bloginfo('wpurl');
	$template_path = get_template_directory();
	if(file_exists($template_path.'/richlist/richstyle.css'))
		wp_enqueue_style( 'richstyle', $template_path.'/richlist/richstyle.css' );
	else
		wp_enqueue_style( 'richstyle', $rich_plugin_path.'/wp-content/plugins/category-list-widget/css/richstyle.css' );
	//wp_enqueue_style( 'style-name', get_stylesheet_uri() );
	wp_dequeue_script('jquery');
	wp_enqueue_script('jquery');
	wp_enqueue_script('jquery-ui-accordion');
	wp_enqueue_script('script-name', $rich_plugin_path . '/wp-content/plugins/category-list-widget/js/richlist.js');
}
add_action( 'wp_enqueue_scripts', 'rich_list_includes' );
/**
	Ends Scripts includes Here
*/

/**
	Starts Settings Page
*/
add_action( 'admin_menu', 'rich_category_list_menu' );

function rich_category_list_menu() {
	add_options_page( 'Rich List Widget', 'Rich List', 'manage_options', 'my-unique-identifier', 'rich_settings_page' );
}

function rich_settings_page() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	echo '<div class="wrap">';
	echo '<p>I will be adding more options here :)</p>';
	echo '</div>';
}
/**
	Ends Settings Page Here
*/

/**
	Starts Widget Functions Here
*/
class Rich_List_Widget extends WP_Widget {

	/**
	 * Register widget with WordPress.
	 */
	function __construct() {
		parent::__construct(
			'rich_list_widget', // Base ID
			__('RichList Widget', 'text_domain'), // Name
			array( 'description' => __( 'A widget to display categories or taxonomy list', 'text_domain' ), ) // Args
		);
	}

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {
		$title = apply_filters( 'widget_title', $instance['title'] );
		$categories_to_exclude = apply_filters( 'categories_to_exclude', $instance['categories_to_exclude'] );
		$number_of_post = apply_filters( 'number_of_post', $instance['number_of_post'] );
		$rich_query = array();
		if ( ! empty( $title ) )
			echo $args['before_title'] . $title . $args['after_title'];
		if( ! empty( $number_of_post ) )
			$rich_query['posts_per_page'] = $number_of_post;
		if( ! empty( $categories_to_exclude ) )
			$cat_query['exclude'] = $categories_to_exclude;
		$categories = get_categories($cat_query);
		echo "<div id='richlist_container'>";
			if($categories){
				foreach($categories as $category) {
					echo '<h3 class="rich_titles"><a href="#">' . $category->cat_name . '</a></h3>';
					global $post;
					$rich_query['category'] = $category->cat_ID;
					$posts = get_posts($rich_query);
					echo '<div>';
					foreach($posts as $post): setup_postdata( $post );
						echo "<p class='rich_links'><a href='".get_permalink()."'>" . get_the_title() . "</a></p>";
					endforeach;
					wp_reset_postdata();
					echo '</div>';
				}
			}
		echo "</div>";
	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {
		if ( isset( $instance[ 'title' ] ) ) {
			$title = $instance[ 'title' ];
		}
		else {
			$title = __( '', 'text_domain' );
		}
		if ( isset( $instance[ 'categories_to_exclude' ] ) ) {
			$categories_to_exclude = $instance[ 'categories_to_exclude' ];
		}
		else {
			$categories_to_exclude = __( '', 'text_domain' );
		}
		if ( isset( $instance[ 'number_of_post' ] ) ) {
			$number_of_post = $instance[ 'number_of_post' ];
		}
		else {
			$number_of_post = __( '', 'text_domain' );
		}
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'categories_to_exclude' ); ?>"><?php _e( 'Categories to exclude:' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'categories_to_exclude' ); ?>" name="<?php echo $this->get_field_name( 'categories_to_exclude' ); ?>" type="text" value="<?php echo esc_attr( $categories_to_exclude ); ?>" />
			<br>
			<small>Please put the comma seperated list of category ID's here</small>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'number_of_post' ); ?>"><?php _e( 'Number of posts to show:' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'number_of_post' ); ?>" name="<?php echo $this->get_field_name( 'number_of_post' ); ?>" type="text" value="<?php echo esc_attr( $number_of_post ); ?>" />
			<br>
			<small>Insert number of posts to show under each category</small>
		</p>
		<?php 
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['categories_to_exclude'] = ( ! empty( $new_instance['categories_to_exclude'] ) ) ? strip_tags( $new_instance['categories_to_exclude'] ) : '';
		$instance['number_of_post'] = ( ! empty( $new_instance['number_of_post'] ) ) ? strip_tags( $new_instance['number_of_post'] ) : '';
		return $instance;
	}

}
function register_rich_list_widget() {
    register_widget( 'Rich_List_Widget' );
}
add_action( 'widgets_init', 'register_rich_list_widget' );
/**
	Ends Widget Functions Here
*/
?>