<?php header('Content-Type: text/html; charset=utf-8');
include 'bootstrap.php';
include APP_ROOT.'/vendor/autoload.php';

use App\Classes\ZoomAPI as ZoomAPI;

$api = new ZoomAPI(TOKEN_FILENAME, CLIENT_ID, CLIENT_SECRET, REDIRECT_URI);

$api->setDebugLevel(ZoomAPI::DEBUG_VERBOSE);

if(getRequestParam('act', '') == '' && getRequestParam('code', '') == '') {
    include_once APP_ROOT.'/page/index.php';
}

if(($code = getRequestParam('code', '')) != '') {
    if($api->requestToken($code)) {
        header("Location: ?");
        exit;
    }
}

$act = str_replace('..', '.', getRequestParam('act', ''));

if(file_exists("page/{$act}.php")) {
    if(in_array($act, ['list_users', 'user_edit', 'create_new_user', 'user_update_picture', ])) {
        include_once APP_ROOT."/page/{$act}.php";
    }
}
else {

    switch(getRequestParam('act', '')) {
        default:
            include_once APP_ROOT.'/page/index.php';
            break;

        case 'refresh_token':
            if($api->refreshToken()) {
                header("Location: ?");
                exit;
            }
            break;

        case 'update_user':
            $userId = getRequestParam('userId', 'me');
            $api->updateUser($userId, getRequestParam('user', []));
            header("Location: ?act=list_users");
            break;

        case 'update_user_picture':
            $userId = getRequestParam('userId', 'me');
            preg_match('/^([a-z0-9]+)\\/([0-9a-z]+)$/i', $_FILES['picture']['type'], $m);
            $api->updateUserPicture($userId, $_FILES['picture']['tmp_name'], ZoomAPI::PICTURE_FILENAME, $m[1]);
            header("Location: ?act=list_users");
            break;


        case 'create_user':
            $user = getRequestParam('user', []);
            if($api->createUser($user['email'], $user['first_name'], $user['last_name'], $user['password'], $user['action'], $user['type'])) {
                echo "Success. <a href='?act=list_users'>Open users list</a>";
            }
            else {
                echo "User creation failed. <a href='?act=list_users'>Open users list</a> / <a href='?'>Open Home page</a>";
            }
            break;

    }
}