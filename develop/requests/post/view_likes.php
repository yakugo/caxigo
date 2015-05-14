<?php
$data = array(
    'status' => 200,
    'html' => $storyObj->getLikesTemplate()
);

header("Content-type: application/json; charset=utf-8");
echo json_encode($data);
$conn->close();
exit();