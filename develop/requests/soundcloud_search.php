<?php
if (! empty($_GET['q']))
{
    if (preg_match('/^(soundcloud\.com)/', $_GET['q']))
    {
        $data = array(
            'status' => 200,
            'type' => 'embed',
            'sc_uri' => $_GET['q']
        );
        
        if (!preg_match('/^(http\:\/\/|https\:\/\/)/', $_GET['q']))
        {
            $data['sc_uri'] = 'https://' . $data['sc_uri'];
        }
    }
    else
    {
        $api_url = 'http://api.soundcloud.com/tracks.json?client_id=4346c8125f4f5c40ad666bacd8e96498&q=' . urlencode($_GET['q']);
        $api_content = @file_get_contents($api_url);
        $html = '';
        
        if (! $api_content)
        {
            $conn->close();
            exit();
        }
        
        $api_content_array = json_decode($api_content, true);
        
        if (! is_array($api_content_array))
        {
            $conn->close();
            exit();
        }
        
        foreach ($api_content_array as $k => $v)
        {
            $themeData['soundcloud_title'] = $v['title'];
            $themeData['soundcloud_uri'] = $v['uri'];
            $themeData['soundcloud_thumbnail'] = $v['artwork_url'];
            $themeData['soundcloud_genre'] = $v['genre'];

            $html .= \SocialKit\UI::view('story/publisher-box/soundcloud-search');
        }
        
        if (! empty($html)) {
            $data = array(
                'status' => 200,
                'type' => 'api',
                'html' => $html
            );
        }
    }
}

header("Content-type: application/json; charset=utf-8");
echo json_encode($data);
$conn->close();
exit();