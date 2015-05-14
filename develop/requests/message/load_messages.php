<?php
$html = '';

$recipientId = (int) $_GET['recipient_id'];
$recipientObj = new \SocialKit\User($conn);
$recipientObj->setId($recipientId);
$recipient = $recipientObj->getRows();

$timelineId = $user['id'];

if (! empty($_GET['timeline_id']))
{
    $timelineId = (int) $_GET['timeline_id'];
}

$reply_ability = true;
$messages = getMessages(
    array(
        'recipient_id' => $recipientId,
        'timeline_id' => $timelineId
    )
);

if (!empty($recipient['id']) && $recipient['type'] == "user" && $recipient['message_privacy'] == "following")
{
    if (! $recipientObj->isFollowing($timelineId))
    {
        $reply_ability = false;
    }
}
elseif ($recipient['type'] == "page" && $recipient['message_privacy'] != "everyone")
{
    $reply_ability = false;
}

if (is_array($messages))
{
    foreach ($messages as $msg)
    {
        $themeData['list_message_id'] = $msg['id'];
        $themeData['list_message_text'] = $msg['text'];
        $themeData['list_message_time'] = date('c', $msg['time']);
        $themeData['list_message_owner'] = $msg['owner'];

        foreach ($msg['timeline'] as $key => $value)
        {
            if (! is_array($value))
            {
                $themeData['list_message_timeline_' . $key] = $value;
            }
        }

        foreach ($msg['media'] as $key => $value)
        {
            if (! is_array($value))
            {
                $themeData['list_message_media_' . $key] = $value;
            }
        }

        $themeData['list_message_media_html'] = '';
            
        if (! empty($msg['media']['id']))
        {
            $themeData['list_message_media_complete_url'] = $msg['media']['each'][0]['complete_url'];
            $themeData['list_message_media_html'] = \SocialKit\UI::view('messages/list-message-each-media');
        }

        if ($msg['owner'] == true)
        {
            $themeData['list_message_buttons'] = \SocialKit\UI::view('messages/list-message-each-buttons');
        }

        $html .= \SocialKit\UI::view('messages/list-message-each');
    }
}

$data = array(
    'status' => 200,
    'html' => $html,
    'reply_ability_status' => $reply_ability
);

header("Content-type: application/json; charset=utf-8");
echo json_encode($data);
$conn->close();
exit();