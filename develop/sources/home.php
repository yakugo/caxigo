<?php
if (! isLogged())
{
    header('Location: ' . smoothLink('index.php?tab1=logout'));
}

/* */
$themeData['announcements'] = getAnnouncements();

if (isLogged())
{
    $themeData['story_postbox'] = getStoryPostBox();
}

/* Stories */
$feedObj = new \SocialKit\Feed($conn);
$themeData['stories'] = $feedObj->getTemplate();

/* Post Filters */
$themeData['story_postfilters'] = \SocialKit\UI::view('home/sidebar-post-filters');

/* Suggestions */
$themeData['suggestions'] = getFollowSuggestions();

/* Trending */
$themeData['trendings'] = getTrendings('popular');

/* Sidebar */
$themeData['sidebar'] = \SocialKit\UI::view('story-page/sidebar');
/* */

$themeData['page_content'] = \SocialKit\UI::view('home/content');
