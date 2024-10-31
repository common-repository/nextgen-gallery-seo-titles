<?php
/*
	Plugin Name: NextGen Gallery SEO titles
	Plugin URI: http://mariokostelac.com/nextgen-seo/
	Description: Simple plugin improves SEO by modifying page titles to become more Google friendly
	Version: 0.2.2
	Author: Mario Kostelac
	Author URI: http://mariokostelac.com
	License: GPL2
*/
/*  Copyright 2010  Mario Kostelac  (email : mario.kostelac@gmail.com)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as 
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
	
	License included in license.txt
*/

	/* Check if Gallery is loaded */
	if ( class_exists('nggRewrite') ) {

		/* removes default nextgen rewrite_title */
		global $nggRewrite;
		remove_filter('wp_title', array(&$nggRewrite, 'rewrite_title'));

		add_filter('wp_title' , 'ngg_titles_rewrite');

		function ngg_titles_rewrite($title) {

			global $nggdb, $wp_query;

			// $_GET from wp_query
			$pid     = get_query_var('pid');
			$pageid  = get_query_var('pageid');
			$nggpage = get_query_var('nggpage');
			$gallery = get_query_var('gallery');
			$album   = get_query_var('album');
			$tag  	 = get_query_var('gallerytag');
			$show    = get_query_var('show');

			// check if gallery page is opened
			if ( !($pid || $pageid || $nggpage || $gallery || $album || $tag || $show) ) {
				return $title;
			}

			$options = get_option('ngg_titles');
			if ( empty($options) ) {
				ngg_titles_activate();
			}

			$new_title = '';
			// the separataor
			$sep = $options['separator'];

			if ( $show == 'slide' )
				$new_title .= __('Slideshow', 'nggallery') . $sep ;
			elseif ( $show == 'show' )
				$new_title .= __('Gallery', 'nggallery') . $sep ;	

			// loop through sortable elements
			if ( is_array($options['pagetitle_order']) ) {
				foreach( $options['pagetitle_order'] as $el ) {
					switch($el){
						case 'picture':
							$action = 'find_image';
							$arg = $pid;
							$field = 'alttext';
							$options_field = 'picture_include';
						break;		
						case 'gallery':
							$action = 'find_gallery';
							$arg = $gallery;
							$field = 'title';
							$options_field = 'gallery_include';
						break;
						case 'album':
							$action = 'find_album';
							$arg = $album;
							$field = 'name';
							$options_field = 'album_include';
						break;
					}
					
					if ( method_exists($nggdb, $action) && $arg && $options[$options_field] ) {
						$obj = $nggdb->$action($arg);
						if ( $obj->$field ) {
							$new_title .= $obj->$field . $sep ;
						}
					}
					
				}
			}

			if ( !empty($nggpage) )
				$new_title .= __('Page', 'nggallery') . ' ' . intval($nggpage) . $sep ;

			//esc_attr should avoid XSS like http://domain/?gallerytag=%3C/title%3E%3Cscript%3Ealert(document.cookie)%3C/script%3E
			if ( !empty($tag) )
				$new_title .= esc_attr($tag) . $sep;

			return $new_title . $title;

		}

		add_action('admin_menu', 'ngg_titles_menu_init');
		function ngg_titles_menu_init() {
			$page = add_submenu_page( NGGFOLDER , 'NextGen Gallery SEO titles', 'Page titles', 'NextGEN Change options', __FILE__, 'ngg_titles_menu');
			add_action('admin_print_scripts-' . $page, 'ngg_titles_admin_scripts');
			add_action('admin_print_styles-'. $page, 'ngg_titles_admin_styles');
		}

		function ngg_titles_admin_scripts() {
			wp_enqueue_script('jquery-ui-core'); 
			wp_enqueue_script('jquery-ui-sortable');
			wp_enqueue_script('nextgen-seo-js', WP_PLUGIN_URL.'/'.basename(dirname(__FILE__)).'/js/main.js' ); 
		}

		function ngg_titles_admin_styles() {
			wp_enqueue_style('nextgen-seo-css', WP_PLUGIN_URL.'/'.basename(dirname(__FILE__)).'/css/style.css' ); 
		}

		function ngg_titles_menu() {

			$options = get_option('ngg_titles');
			if ( empty($options) ) {
				$first_view = true;
				$options = get_default_options();
			}
			if ( $_POST ) {
				$options_new = array(
					'album_include' => $_POST['album_include'] ? 1 : 0,
					'gallery_include' => $_POST['gallery_include'] ? 1 : 0,
					'picture_include' => $_POST['picture_include'] ? 1 : 0,
					'separator' => $_POST['separator'],
					'pagetitle_order' => explode(',', $_POST['pagetitle_order'])
				);
				if ( !empty($options) && !$first_view ) {
					update_option('ngg_titles', $options_new);
					$options = $options_new;
				}
				else  {
					add_option('ngg_titles', $options_new);
					$first_view = false;
				} 

			}

		?>
			<div id="ngg-titles" class="wrap">
				<h2>Page titles configuration</h2>

				<p>
					Choose which parts you want to include in page titles and define the separator between them.<br />
					If you are not satisfied with the order, drag those parts to desired one.
				</p>

				<? if ( $first_view == true ): ?>
				<p style="color: red">It looks like something has gone wrong with plugin settings. Default options are here, just hit the blue button to save them.</p>
				<?php endif; ?>

				<form name="pagetitles" id="pagetitles" method="POST" accept-charset="utf-8" >
					<div class="option">
						<label for="separator">Separator<input type="text" id="separator" name="separator" value="<?= $options['separator']; ?>" size="6" /></label>
					</div>
					<ul class="sortable">
					<?php
					if ( $options['pagetitle_order'] ) {
						foreach ( $options['pagetitle_order'] as $el ) {
							$li = '';
							switch ( $el ){
								case 'album':
									$li = '<li id="album"><label for="album_include">Include album name<input';
									if ( $options['album_include'] ) 
										$li .= ' checked="checked"';
									$li .= ' type="checkbox" name="album_include" id="album_include" value="1" /></label></li>';
								break;
								case 'gallery':
									$li = '<li id="gallery"><label for="gallery_include">Include gallery name<input';
									if ( $options['gallery_include'] )
										$li .= ' checked="checked"'; 
									$li .= ' type="checkbox" name="gallery_include" id="gallery_include" value="1" /></label></li>';
								break;
								case 'picture':
									$li = '<li id="picture"><label for="picture_include">Include picture name<input';
									if ( $options['picture_include'] ) 
										$li .= ' checked="checked"';
									$li .= ' type="checkbox" name="picture_include" id="picture_include" value="1" /></label></li>';
								break;
							}
							echo $li . "\n";
						}
					}
					?>
					</ul>
					<input type="hidden" name="pagetitle_order" id="pagetitle_order" value="<?= implode(',', $options['pagetitle_order']); ?>" />
					<div class="submit"><input type="submit" class="button-primary" name= "update_titles" value="Save changes"/></div>
				</form>

			</div>
		<?php
		}

	}
	else {
		// if gallery is not loaded, id doesn't mean that it won't be; maybe this plugin is loaded before NGG 
		ngg_titles_activate();
	}

	register_uninstall_hook(__FILE__, 'ngg_titles_uninstall');
	function ngg_titles_uninstall() {
		delete_option('ngg_titles');
		return true;
	}

	register_activation_hook( __FILE__, 'ngg_titles_activate' );
	function ngg_titles_activate() {
		$options = get_option('ngg_titles');
		if ( empty($options) ) {
			$options = get_default_options();
			add_option('ngg_titles', $options);
		}
		elseif ( empty($options['pagetitle_order']) ) {
			$options['pagetitle_order'] = array('picture', 'gallery', 'album');
			update_option('ngg_titles', $options);
		}

		/* move plugin to the bottom in order to be activated after nextgen gallery */
		$this_plugin = plugin_basename(trim(__FILE__));
		$active_plugins = get_option('active_plugins');
		unset( $active_plugins[array_search($this_plugin, $active_plugins)] );
		array_push($active_plugins, $this_plugin);
		update_option('active_plugins', $active_plugins);

		return true;
	}
	
	function get_default_options() {
		return array(
				'album_include' => 1,
				'gallery_include' => 1,
				'picture_include' => 1,
				'separator' => ' | ',
				'pagetitle_order' => array('picture', 'gallery', 'album')
			);
	}

?>