<?php
userOnly();

$array = array();
$parameters = array(
    'recipient_id',
    'text'
);

foreach ($parameters as $v)
{
    if (! empty($_POST[$v]))
    {
        $array[$v] = $_POST[$v];
    }
}

$messageId = registerChatMessage($array);

if (! empty($messageId))
{

    $html = '';
    $message = getMessages(array('message_id' => $messageId, 'recipient_id' => $_POST['recipient_id']));
    
    foreach ($message as $k => $v)
    {
        $themeData['message_text'] = $v['text'];
        $themeData['message_time'] = getTimeAgo($v['time'], true);

        $themeData['message_user_url'] = $v['timeline']['url'];
        $themeData['message_user_avatar_url'] = $v['timeline']['avatar_url'];
        $themeData['message_user_name'] = $v['timeline']['name'];

        if (isset($v['media']['id']))
        {
            $themeData['message_media_url'] = $v['media']['complete_url'];
            $themeData['message_media'] = \SocialKit\UI::view('chat/text-media');
        }

        $html .= \SocialKit\UI::view('chat/outgoing-text');
    }
    
    $data = array(
        'status' => 200,
        'html' => $html
    );
}

header("Content-type: application/json; charset=utf-8");
echo json_encode($data);
$conn->close();
exit();