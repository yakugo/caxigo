<?php

$config = array();
$confQuery = $conn->query("SELECT * FROM " . DB_CONFIGURATIONS);
$config = $confQuery->fetch_array(MYSQLI_ASSOC);

$config['site_url'] = $site_url;
$config['theme_url'] = $site_url . '/themes/' . $config['theme'];
$config['script_path'] = str_replace('index.php', '', $_SERVER['PHP_SELF']);
$config['ajax_path'] = $config['script_path'] . 'request.php';
$config['page_path'] = $config['script_path'] . 'page.php';

if (! isset($_SESSION['language']))
{
    $_SESSION['language'] = $config['language'];
}

include_once('themes/' . $config['theme'] . '/emoticons/process.php');

foreach ($config as $cnm => $cfg)
{
    define(strtoupper($cnm), $cfg);
    $themeData['config_' . $cnm] = $cfg;
}

// Login verification and user stats update
$logged = false;
$user = null;

if (isLogged())
{
    $userObj = new \SocialKit\User();
    $userObj->setId($_SESSION['user_id']);
    $user = $userObj->getRows();

    if (isset($user['id']) && $user['type'] == "user")
    {
        $logged = true;
        
        $conn->query("UPDATE " . DB_ACCOUNTS . " SET last_logged=" . time() . " WHERE id=" . $user['id']);
        
        if (! empty($user['language']))
        {
            $_SESSION['language'] = $user['language'];
        }
        
        if (! isset($_SESSION['tempche_user_ownfollowing']))
        {
            $conn->query("DELETE FROM " . DB_FOLLOWERS . " WHERE follower_id=" . $user['id'] . " AND following_id=" . $user['id']);
            $_SESSION['tempche_user_ownfollowing'] = true;
        }

        foreach ($user as $key => $value)
        {
            if (! is_array($value))
            {
                $key = str_replace('current_city', 'location', $key);
                $themeData['user_' . $key] = $value;
            }
        }
    }
}

$sk['logged'] = $logged;

// Fetch preferred language
if (! empty($_GET['lang']))
{
    if (file_exists('languages/' . $_GET['lang'] . '.php'))
    {
        $config['language'] = $_GET['lang'];
        $_SESSION['language'] = $_GET['lang'];
        
        if ($logged == true)
        {
            $conn->query("UPDATE " . DB_ACCOUNTS . " SET language='" . $_GET['lang'] . "' WHERE id=" . $user['id']);
        }

        header("Location: " . $config['site_url']);
    }
}

require_once('languages/' . $_SESSION['language'] . '.php');

// Removes session and unnecessary variables if user verification fails
if ($logged == false)
{
    unset($_SESSION['user_id']);
    unset($user);
}