<?php
userOnly();

$regObj = new \SocialKit\registerStory();

$regObj->setTimeline($_POST['timeline_id']);
$regObj->setRecipient($_POST['recipient_id']);
$regObj->setText($_POST['text']);
$regObj->setSoundcloudTitle($_POST['soundcloud_title']);
$regObj->setSoundcloudUri($_POST['soundcloud_uri']);
$regObj->setYoutubeTitle($_POST['youtube_title']);
$regObj->setYoutubeId($_POST['youtube_video_id']);
$regObj->setMapName($_POST['google_map_name']);

if (isset($_FILES['photos']['name']))
{
    $regObj->setPhotos($_FILES['photos']);
}

if ($storyId = $regObj->register())
{
    $storyObj = new \SocialKit\Story();
    $storyObj->setId($storyId);
    
    $data = array(
        'status' => 200,
        'html' => $storyObj->getTemplate()
    );
}

header("Content-type: application/json; charset=utf-8");
echo json_encode($data);
$conn->close();
exit();