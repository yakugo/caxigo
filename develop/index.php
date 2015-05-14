<?php
/**
 * @package Social Kit - Social Networking Platform v1.3.3
 * @author Rehan Adil (ThemePhysics) http://codecanyon.net/user/ThemePhysics
 * @copyright 2015 Rehan Adil. All rights reserved.
 */

require_once('assets/includes/core.php');

foreach ($_GET as $key => $value)
{
    $themeData['get_' . $escapeObj->stringEscape(strtolower($key))] = $escapeObj->stringEscape($value);
}

if ($config['smooth_links'] == 1)
{
    $themeData['js_sources'] = '<script>
    function SK_source(){
        return \'' . $config['ajax_path'] . '\';
    }
    function SK_pageSource() {
        return \'' . $config['page_path'] . '\';
    }
    function reqSource(){
        return \'' . $config['ajax_path'] . '\';
    }
    function pageSource() {
        return \'' . $config['page_path'] . '\';
    }
</script>';
$themeData['body_rewrite_attr'] = 1;
}
else
{
    $themeData['js_sources'] = '<script>
    function SK_source(){
        return \'request.php\';
    }
    function SK_pageSource() {
        return \'page.php\';
    }
    function reqSource(){
        return \'request.php\';
    }
    function pageSource() {
        return \'page.php\';
    }
</script>';
$themeData['body_rewrite_attr'] = 0;
}

if ($sk['logged'] == true)
{
$themeData['js_script'] = '<script src="' . $config['theme_url'] . '/javascript/script.js"></script>';
}
else
{
$themeData['js_script'] = '<script src="' . $config['theme_url'] . '/javascript/welcome.js"></script>';
}

if ($config['chat'] == 1)
{
    if ($chat_recipient = SK_getChat())
    {
        $themeData['chat_initiate_js'] = '<script>$(function() { SK_getChat(' . $chat_recipient['id'] . ',\'' . $chat_recipient['name'] . '\'); });</script>';
    }
}

if (isLogged())
{
    $themeData['welcome_page_url'] = smoothLink('index.php?tab1=welcome');
    $themeData['home_page_url'] = smoothLink('index.php?tab1=home');
    $themeData['messages_page_url'] = smoothLink('index.php?tab1=messages');
    $themeData['following_page_url'] = 'index.php?tab1=timeline&tab2=requests&id=' . $user['username'];
    $themeData['more_page_url'] = smoothLink('index.php?tab1=more');
    $themeData['settings_page_url'] = smoothLink('index.php?tab1=settings');
    $themeData['logout_url'] = smoothLink('index.php?tab1=logout');

    $themeData['notif_num'] = countNotifications(0, true);
    $themeData['messages_num'] = countMessages();
    $themeData['follow_requests_num'] = countFollowRequests();

    if ($themeData['follow_requests_num'] == 0)
    {
        $themeData['following_page_url'] = 'index.php?tab1=timeline&tab2=following&id=' . $user['username'];
    }

    if ($config['friends'] == true)
    {
        $themeData['following_label'] = $lang['header_friends_label'];
    }
    else
    {
        $themeData['following_label'] = $lang['header_following_label'];
    }

    $themeData['following_page_url_smoothless'] = str_replace('index.php', '', $themeData['following_page_url']);
    $themeData['following_page_url'] = smoothLink($themeData['following_page_url']);
    $themeData['header_dropdowns'] = \SocialKit\UI::view('header/user-dropdowns');
    $themeData['header_navigation'] = \SocialKit\UI::view('header/user-navigation');
    $themeData['header_end'] = \SocialKit\UI::view('header/user-end');
}
else
{
    $themeData['header_navigation'] = \SocialKit\UI::view('header/guest-navigation');
    $themeData['header_end'] = \SocialKit\UI::view('header/default-end');
}

$themeData['header'] = \SocialKit\UI::view('header/content');

if (! isset($_GET['tab1']))
{
    $_GET['tab1'] = 'welcome';
}

switch ($_GET['tab1'])
{
    // Welcome page source
    case 'welcome':
        include('sources/welcome.php');
    break;
    
    // Email verification source
    case 'email-verification':
        include('sources/email_verification.php');
    break;
    
    // Home page source
    case 'home':
        include('sources/home.php');
    break;
    
    // Messages page source
    case 'messages':
        include('sources/messages.php');
    break;
    
    // Timeline page source
    case 'timeline':
        include('sources/timeline.php');
    break;
    
    // Story page source
    case 'story':
        include('sources/story.php');
    break;

    // Album page source
    case 'album':
        include('sources/album.php');
    break;
    
    // Create page source
    case 'create_page':
        include('sources/create_page.php');
    break;
    
    // Create group page source
    case 'create_group':
        include('sources/create_group.php');
    break;
    
    // Hashtag page source
    case 'hashtag':
        include('sources/hashtag.php');
    break;
    
    // Search page source
    case 'search':
        include('sources/search.php');
    break;
    
    // User settings page source
    case 'settings':
        include('sources/user_settings.php');
    break;
    
    // More features page source
    case 'more':
        include('sources/more.php');
    break;
    
    // Terms page source
    case 'terms':
        include('sources/terms.php');
    break;
    
    // Logout source
    case 'logout':
        include('sources/logout.php');
    break;
    
}

// If no sources found
if (empty($themeData['page_content']))
{
    $themeData['page_content'] = \SocialKit\UI::view('welcome/error');
}

$themeData['about_page_url'] = smoothLink('index.php?tab1=terms&tab2=about');
$themeData['create_page_url'] = smoothLink('index.php?tab1=create_page');
$themeData['create_group_url'] = smoothLink('index.php?tab1=create_group');
$themeData['contact_page_url'] = smoothLink('index.php?tab1=terms&tab2=contact');
$themeData['privacy_page_url'] = smoothLink('index.php?tab1=terms&tab2=privacy');
$themeData['tos_page_url'] = smoothLink('index.php?tab1=terms&tab2=tos');
$themeData['disclaimer_page_url'] = smoothLink('index.php?tab1=terms&tab2=disclaimer');
$themeData['admin_url'] = $config['site_url'] . '/admin/';
$themeData['languages'] = getLanguages();
$themeData['year'] = date('Y');

if (isLogged())
{
    $themeData['footer'] = \SocialKit\UI::view('footer/content');
}
else
{
    $themeData['footer'] = \SocialKit\UI::view('footer/guest-content');
}

if (isLogged() && $config['chat'] == 1)
{
    $themeData['num_onlines'] = countOnlines();

    if ($themeData['num_onlines'] == 0)
    {
        if ($config['friends'] == true)
        {
            $themeData['no_onlines'] = $lang['no_friends_online'];
        }
        else
        {
            $themeData['no_onlines'] = $lang['no_followers_online'];
        }

        $themeData['list_onlines'] = \SocialKit\UI::view('chat/no-onlines');
    }
    else
    {
        $listOnlines = '';

        foreach (getOnlines() as $k => $v)
        {
            $themeData['list_online_id'] = $v['id'];
            $themeData['list_online_name'] = $v['name'];
            $themeData['list_online_thumbnail_url'] = $v['thumbnail_url'];
            $themeData['list_online_name_short'] = substr($v['name'], 0, 15);

            if ($v['online'] == true)
            {
                $themeData['list_online_class'] = 'active';
            }

            if (($themeData['list_online_num_messages'] = countMessages(0, $v['id'], true)) > 0)
            {
                $themeData['list_online_num_messages_html'] = \SocialKit\UI::view('chat/list-num-messages-each');
            }

            $listOnlines .= \SocialKit\UI::view('chat/online-column');
        }

        $themeData['list_onlines'] = $listOnlines;
    }

    $themeData['chat'] = \SocialKit\UI::view('chat/container');
}

if ($_GET['tab1'] == "welcome")
{
    $themeData['welcome_css_html'] = '<link href="' . $config['theme_url'] . '/css/welcome.css" rel="stylesheet">';
}

echo \SocialKit\UI::view('container');
$conn->close();