<?php
if (! empty($_GET['q']))
{
    if (preg_match('/^(http\:\/\/|https\:\/\/|www\.|youtube\.com|youtu\.be)/', $_GET['q']))
    {
        $data = array(
            'status' => 200,
            'type' => 'embed'
        );
    }
    else
    {
        $api_url = 'http://gdata.youtube.com/feeds/api/videos?q=' . urlencode($_GET['q']) . '&max-results=30&orderby=relevance&alt=json&format=5&v=2';
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
        
        if (! is_array($api_content_array['feed']['entry']))
        {
            $conn->close();
            exit();
        }
        
        foreach ($api_content_array['feed']['entry'] as $k => $v)
        {
            $themeData['youtube_id'] = $v['media$group']['yt$videoid']['$t'];
            $themeData['youtube_title'] = $v['title']['$t'];
            $themeData['youtube_category'] = $v['category'][1]['label'];

            if (! empty($v['media$group']['media$thumbnail'][1]['url']))
            {
                $themeData['youtube_thumbnail_url'] = $v['media$group']['media$thumbnail'][1]['url'];
            }

            $html .= \SocialKit\UI::view('story/publisher-box/youtube-search');
        }
        
        if (! empty($html))
        {
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