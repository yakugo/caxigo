<?php

$themeData['terms_tos_url'] = smoothLink('index.php?tab1=terms&tab2=tos');
$themeData['terms_privacy_url'] = smoothLink('index.php?tab1=terms&tab2=privacy');
$themeData['terms_disclaimer_url'] = smoothLink('index.php?tab1=terms&tab2=disclaimer');
$themeData['terms_contact_url'] = smoothLink('index.php?tab1=terms&tab2=contact');
$themeData['terms_about_url'] = smoothLink('index.php?tab1=terms&tab2=about');

$termType = null;
$termsContent = '';

if (isset($_GET['tab2']))
{
    $termType = $_GET['tab2'];
}

switch($termType)
{
    case 'privacy':
        $termsContent = \SocialKit\UI::view('terms/privacy');
    break;
    
    case 'disclaimer':
        $termsContent = \SocialKit\UI::view('terms/disclaimer');
    break;
    
    case 'contact':
        $termsContent = \SocialKit\UI::view('terms/contact');
    break;
    
    case 'about':
        $termsContent = \SocialKit\UI::view('terms/about');
    break;
    
    default:
        $termsContent = \SocialKit\UI::view('terms/tos');
}

$themeData['terms_content'] = $termsContent;
$themeData['page_content'] = \SocialKit\UI::view('terms/content');
