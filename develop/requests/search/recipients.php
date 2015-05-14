<?php
userOnly();

$search_query = $escapeObj->stringEscape($_GET['q']);
$html = '';

foreach (getMessageRecipients(0, $search_query) as $eachRecipient)
{
    $themeData['list_recipient_id'] = $eachRecipient['id'];
    $themeData['list_recipient_name'] = $eachRecipient['name'];
    $themeData['list_recipient_thumbnail_url'] = $eachRecipient['thumbnail_url'];

    if ($eachRecipient['online'] == true) {
        $themeData['list_recipient_online_class'] = 'active';
    }

    $themeData['list_recipient_message_num'] = countMessages(0, $eachRecipient['id'], true);

    $html .= \SocialKit\UI::view('messages/recipient-list');
}

$data = array(
    'status' => 200,
    'html' => $html
);

header("Content-type: application/json; charset=utf-8");
echo json_encode($data);
$conn->close();
exit();