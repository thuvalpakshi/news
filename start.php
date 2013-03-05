<?php
/**
 * Newss
 *
 * @package News
 *
 * @todo
 * - Either drop support for "publish date" or duplicate more entity getter
 * functions to work with a non-standard time_created.
 * - Pingbacks
 * - Notifications
 * - River entry for posts saved as drafts and later published
 */

elgg_register_event_handler('init', 'system', 'news_init');

/**
 * Init news plugin.
 */
function news_init() {

	elgg_register_library('elgg:news', elgg_get_plugins_path() . 'news/lib/news.php');

	// add a site navigation item
	$item = new ElggMenuItem('news', elgg_echo('news:newss'), 'news/all');
	elgg_register_menu_item('site', $item);

	elgg_register_event_handler('upgrade', 'upgrade', 'news_run_upgrades');

	// add to the main css
	elgg_extend_view('css/elgg', 'news/css');

	// register the news's JavaScript
	$news_js = elgg_get_simplecache_url('js', 'news/save_draft');
	elgg_register_simplecache_view('js/news/save_draft');
	elgg_register_js('elgg.news', $news_js);

	// routing of urls
	elgg_register_page_handler('news', 'news_page_handler');

	// override the default url to view a news object
	elgg_register_entity_url_handler('object', 'news', 'news_url_handler');

	// notifications
	register_notification_object('object', 'news', elgg_echo('news:newpost'));
	elgg_register_plugin_hook_handler('notify:entity:message', 'object', 'news_notify_message');

	// add news link to
	elgg_register_plugin_hook_handler('register', 'menu:owner_block', 'news_owner_block_menu');

	// pingbacks
	//elgg_register_event_handler('create', 'object', 'news_incoming_ping');
	//elgg_register_plugin_hook_handler('pingback:object:subtypes', 'object', 'news_pingback_subtypes');

	// Register for search.
	elgg_register_entity_type('object', 'news');

	// Add group option
	add_group_tool_option('news', elgg_echo('news:enablenews'), true);
	elgg_extend_view('groups/tool_latest', 'news/group_module');

	// add a news widget
	elgg_register_widget_type('news', elgg_echo('news'), elgg_echo('news:widget:description'), 'profile');

	// register actions
	$action_path = elgg_get_plugins_path() . 'news/actions/news';
	elgg_register_action('news/save', "$action_path/save.php");
	elgg_register_action('news/auto_save_revision', "$action_path/auto_save_revision.php");
	elgg_register_action('news/delete', "$action_path/delete.php");

	// entity menu
	elgg_register_plugin_hook_handler('register', 'menu:entity', 'news_entity_menu_setup');

	// ecml
	elgg_register_plugin_hook_handler('get_views', 'ecml', 'news_ecml_views_hook');
}

/**
 * Dispatches news pages.
 * URLs take the form of
 *  All newss:       news/all
 *  User's newss:    news/owner/<username>
 *  Friends' news:   news/friends/<username>
 *  User's archives: news/archives/<username>/<time_start>/<time_stop>
 *  News post:       news/view/<guid>/<title>
 *  New post:        news/add/<guid>
 *  Edit post:       news/edit/<guid>/<revision>
 *  Preview post:    news/preview/<guid>
 *  Group news:      news/group/<guid>/all
 *
 * Title is ignored
 *
 * @todo no archives for all newss or friends
 *
 * @param array $page
 * @return bool
 */
function news_page_handler($page) {

	elgg_load_library('elgg:news');

	// @todo remove the forwarder in 1.9
	// forward to correct URL for news pages pre-1.7.5
	news_url_forwarder($page);

	// push all newss breadcrumb
	elgg_push_breadcrumb(elgg_echo('news:newss'), "news/all");

	if (!isset($page[0])) {
		$page[0] = 'all';
	}

	$page_type = $page[0];
	switch ($page_type) {
		case 'owner':
			$user = get_user_by_username($page[1]);
			$params = news_get_page_content_list($user->guid);
			break;
		case 'friends':
			$user = get_user_by_username($page[1]);
			$params = news_get_page_content_friends($user->guid);
			break;
		case 'archive':
			$user = get_user_by_username($page[1]);
			$params = news_get_page_content_archive($user->guid, $page[2], $page[3]);
			break;
		case 'view':
		case 'read': // Elgg 1.7 compatibility
			$params = news_get_page_content_read($page[1]);
			break;
		case 'add':
			gatekeeper();
			$params = news_get_page_content_edit($page_type, $page[1]);
			break;
		case 'edit':
			gatekeeper();
			$params = news_get_page_content_edit($page_type, $page[1], $page[2]);
			break;
		case 'group':
			if ($page[2] == 'all') {
				$params = news_get_page_content_list($page[1]);
			} else {
				$params = news_get_page_content_archive($page[1], $page[3], $page[4]);
			}
			break;
		case 'all':
			$params = news_get_page_content_list();
			break;
		default:
			return false;
	}

	if (isset($params['sidebar'])) {
		$params['sidebar'] .= elgg_view('news/sidebar', array('page' => $page_type));
	} else {
		$params['sidebar'] = elgg_view('news/sidebar', array('page' => $page_type));
	}

	$body = elgg_view_layout('content', $params);

	echo elgg_view_page($params['title'], $body);
	return true;
}

/**
 * Format and return the URL for newss.
 *
 * @param ElggObject $entity News object
 * @return string URL of news.
 */
function news_url_handler($entity) {
	if (!$entity->getOwnerEntity()) {
		// default to a standard view if no owner.
		return FALSE;
	}

	$friendly_title = elgg_get_friendly_title($entity->title);

	return "news/view/{$entity->guid}/$friendly_title";
}

/**
 * Add a menu item to an ownerblock
 */
function news_owner_block_menu($hook, $type, $return, $params) {
	if (elgg_instanceof($params['entity'], 'user')) {
		$url = "news/owner/{$params['entity']->username}";
		$item = new ElggMenuItem('news', elgg_echo('news'), $url);
		$return[] = $item;
	} else {
		if ($params['entity']->news_enable != "no") {
			$url = "news/group/{$params['entity']->guid}/all";
			$item = new ElggMenuItem('news', elgg_echo('news:group'), $url);
			$return[] = $item;
		}
	}

	return $return;
}

/**
 * Add particular news links/info to entity menu
 */
function news_entity_menu_setup($hook, $type, $return, $params) {
	if (elgg_in_context('widgets')) {
		return $return;
	}

	$entity = $params['entity'];
	$handler = elgg_extract('handler', $params, false);
	if ($handler != 'news') {
		return $return;
	}

	if ($entity->canEdit() && $entity->status != 'published') {
		$status_text = elgg_echo("news:status:{$entity->status}");
		$options = array(
			'name' => 'published_status',
			'text' => "<span>$status_text</span>",
			'href' => false,
			'priority' => 150,
		);
		$return[] = ElggMenuItem::factory($options);
	}

	return $return;
}

/**
 * Register newss with ECML.
 */
function news_ecml_views_hook($hook, $entity_type, $return_value, $params) {
	$return_value['object/news'] = elgg_echo('news:newss');

	return $return_value;
}

/**
 * Upgrade from 1.7 to 1.8.
 */
function news_run_upgrades($event, $type, $details) {
	$news_upgrade_version = elgg_get_plugin_setting('upgrade_version', 'newss');

	if (!$news_upgrade_version) {
		 // When upgrading, check if the ElggNews class has been registered as this
		 // was added in Elgg 1.8
		if (!update_subtype('object', 'news', 'ElggNews')) {
			add_subtype('object', 'news', 'ElggNews');
		}

		elgg_set_plugin_setting('upgrade_version', 1, 'newss');
	}
}
