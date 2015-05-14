<?php

$postId = (int) $_GET['id'];
$storyObj = new \SocialKit\Story($conn);
$storyObj->setId($postId);

/* */
$available = true;
$template = $storyObj->getTemplate();

if (empty($template))
{
	$available = false;
}


if ($available)
{
    $themeData['tab_content'] = $storyObj->getTemplate();

} else {
    $themeData['tab_content'] = \SocialKit\UI::view('story-page/no-post');
}

if (isLogged())
{
	/* Suggestions */
	$themeData['suggestions'] = getFollowSuggestions();

	/* Trending */
	$themeData['trendings'] = getTrendings('popular');
	
    $themeData['sidebar'] = \SocialKit\UI::view('story-page/sidebar');
    $themeData['end'] = \SocialKit\UI::view('story-page/end');
}
/* */

$themeData['page_content'] = \SocialKit\UI::view('story-page/content');