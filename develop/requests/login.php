<?php
$data['error_message'] = $lang['error_empty_login'];

$loginId = $escapeObj->stringEscape($_POST['login_id']);
$loginPassword = trim($_POST['login_password']);
$loginPasswordMd5 = md5($loginPassword);

$userId = getUserId($conn, $loginId);

if ($userId)
{
    $query = $conn->query("SELECT id,username,email_verified FROM " . DB_ACCOUNTS . " WHERE id=$userId AND password='$loginPasswordMd5' AND type='user' AND active=1");
    $data['error_message'] = $lang['error_bad_login'];
    
    if ($query->num_rows == 1)
    {
        $fetch = $query->fetch_array(MYSQLI_ASSOC);
        $continue = true;
        
        if ($config['email_verification'] == 1 && $fetch['email_verified'] == 0)
        {
            $continue = false;
            $data['error_message'] = $lang['error_verify_email'];
        }
        
        if ($continue == true)
        {
            $_SESSION['user_id'] = $fetch['id'];
            $_SESSION['user_pass'] = $loginPasswordMd5;
            
            if (isset($_POST['keep_logged_in']) && $_POST['keep_logged_in'] == true)
            {
                setcookie('sk_u_i', $_SESSION['user_id'], time() + (60 * 60 * 24 * 7));
                setcookie('sk_u_p', $_SESSION['user_pass'], time() + (60 * 60 * 24 * 7));
            }
            
            $data['status'] = 200;
            $data['redirect_url'] = smoothLink('index.php?tab1=home');
        }
    }
    else
    {
        $data['error_message'] = $lang['incorrect_password'];
    }
}
else
{
    $data['error_message'] = $lang['no_user_found'];
}

header("Content-type: application/json; charset=utf-8");
echo json_encode($data);
$conn->close();
exit();