<!DOCTYPE html>
<html lang="ru">
<head>
    <title>Users list - [Zoom Assistant]</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/popper.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <a class="navbar-brand" href="#">Zoom Assistant</a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarSupportedContent">
        <ul class="navbar-nav mr-auto">
            <li class="nav-item active">
                <a class="nav-link" href="?act=list_users">List users</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="?">Home</a>
            </li>
        </ul>
    </div>
</nav>

<table class="table" style="font-size: 9pt;">
    <?php
        $userlist = $api->getAllUsers();
        foreach($userlist as $user) {
            ?>
            <tr>
                <td style="text-align: center;">
                    <div style="width: 51px; height: 51px; display: inline-block; ">
                        <?=$user['pic_url'] ? "<img src='{$user['pic_url']}' alt='user photo' style='width: 50px; ' />" : ''?>
                    </div>
                    <div style="text-align: center;">
                        <a href="?act=user_update_picture&userId=<?=getRequestParam('userId', 'me')?>" style="font-size: 8pt;">Update</a>
                    </div>
                </td>
                <td><?=$user['last_name']?> <?=$user['first_name']?></td>
                <td><?=$user['id']?></td>
                <td><?=$user['email']?></td>
                <td><?=$api->translateZoomUserType($user['type'])?></td>
                <td><?=$user['pmi']?></td>
                <td><?=$user['timezone']?></td>
                <td><?=$user['dept']?></td>
                <td style="white-space: nowrap; "><?=$user['verified'] ? 'Verified' : "Awaiting activation"?></td>
                <td>
                    <a href="?act=user_edit&userId=<?=$user['id']?>">Edit user info</a>
                </td>
            </tr>
            <?php
        }
    ?>
</table>
</body>
</html>