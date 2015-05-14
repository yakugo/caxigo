<?php
$storyObj->putLike();

$data = array(
    'status' => 200,
    'button_html' => $storyObj->getLikeButtonTemplate(),
    'activity_html' => $storyObj->getLikeActivityTemplate()
);

header("Content-type: application/json; charset=utf-8");
echo json_encode($data);
$conn->close();
exit();