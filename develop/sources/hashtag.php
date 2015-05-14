<?php

if (! empty($_GET['query']))
{
    $searchQuery = str_replace('#', '', $escapeObj->stringEscape($_GET['query']));
    $hashdata = getHashtag($searchQuery);
    
    if (is_array($hashdata) && count($hashdata) > 0)
    {
        $search_string = "#[" . $hashdata['id'] . "]";

        $query = $conn->query("SELECT id FROM " . DB_POSTS . " WHERE text LIKE '%$search_string%' AND hidden=0 AND active=1");
        $storiesHtml = '';
        
        while ($fetch = $query->fetch_array(MYSQLI_ASSOC))
        {
            $storyObj = new \SocialKit\Story();
            $storyObj->setId($fetch['id']);
            $story = $storyObj->getRows();

            if (isset($story['id']))
            {
                $storiesHtml .= $storyObj->getTemplate();
            }
        }

        $themeData['stories'] = $storiesHtml;
    }
}

/* */
/* Suggestions */
$themeData['suggestions'] = getFollowSuggestions();

/* Trending */
$themeData['trendings'] = getTrendings('popular');


if (isLogged())
{
    $themeData['sidebar'] = \SocialKit\UI::view('hashtag/sidebar');
}

if ($config['smooth_links'] == 1)
{
    $themeData['end'] = \SocialKit\UI::view('hashtag/smooth-end');
}
else
{
    $themeData['end'] = \SocialKit\UI::view('hashtag/default-end');
}
/* */

$themeData['page_content'] = \SocialKit\UI::view('hashtag/content');
