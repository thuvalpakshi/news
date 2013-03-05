<?php
/**
 * News English language file.
 *
 */

$english = array(
	'news' => 'News',
	'news:newss' => 'News',
	'news:revisions' => 'Revisions',
	'news:archives' => 'Archives',
	'news:news' => 'News',
	'item:object:news' => 'News',

	'news:title:user_newss' => '%s\'s news',
	'news:title:all_newss' => 'All site news',
	'news:title:friends' => 'Friends\' news',

	'news:group' => 'Group news',
	'news:enablenews' => 'Enable group news',
	'news:write' => 'Write a news post',

	// Editing
	'news:add' => 'Add news post',
	'news:edit' => 'Edit news post',
	'news:excerpt' => 'Excerpt',
	'news:body' => 'Body',
	'news:save_status' => 'Last saved: ',
	'news:never' => 'Never',

	// Statuses
	'news:status' => 'Status',
	'news:status:draft' => 'Draft',
	'news:status:published' => 'Published',
	'news:status:unsaved_draft' => 'Unsaved Draft',

	'news:revision' => 'Revision',
	'news:auto_saved_revision' => 'Auto Saved Revision',

	// messages
	'news:message:saved' => 'News post saved.',
	'news:error:cannot_save' => 'Cannot save news post.',
	'news:error:cannot_write_to_container' => 'Insufficient access to save news to group.',
	'news:error:post_not_found' => 'This post has been removed, is invalid, or you do not have permission to view it.',
	'news:messages:warning:draft' => 'There is an unsaved draft of this post!',
	'news:edit_revision_notice' => '(Old version)',
	'news:message:deleted_post' => 'News post deleted.',
	'news:error:cannot_delete_post' => 'Cannot delete news post.',
	'news:none' => 'No news posts',
	'news:error:missing:title' => 'Please enter a news title!',
	'news:error:missing:description' => 'Please enter the body of your news!',
	'news:error:cannot_edit_post' => 'This post may not exist or you may not have permissions to edit it.',
	'news:error:revision_not_found' => 'Cannot find this revision.',

	// river
	'river:create:object:news' => '%s published a news post %s',
	'river:comment:object:news' => '%s commented on the news %s',

	// notifications
	'news:newpost' => 'A new news post',

	// widget
	'news:widget:description' => 'Display your latest news posts',
	'news:morenewss' => 'More news posts',
	'news:numbertodisplay' => 'Number of news posts to display',
	'news:nonewss' => 'No news posts'
);

add_translation('en', $english);
