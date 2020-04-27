<!DOCTYPE html>
<html lang="ru">
<head>
    <title>Edit user - [Zoom Assistant]</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/popper.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <a class="navbar-brand" href="#">Zoom Assistant - Edit user</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav mr-auto">
                <li class="nav-item active">
                    <a class="nav-link" href="?">Home</a>
                </li>
            </ul>
        </div>
    </nav>
    <form method="POST" action="?">
        <input type="hidden" name="act" value="update_user" />
        <table class="table" style="font-size: 9pt;">
            <?php
                $userId = getRequestParam('userId');

                $userInfo = $api->getUserInfo($userId);
            ?>
            <input type="hidden" name="userId" value="<?=$userId?>" />
            <tr>
                <th>First Name</th>
                <td><input type="text" class="form-control" name="user[first_name]" value="<?=$userInfo['first_name']?>" style="width: 100%" /></td>
            </tr>
            <tr>
                <th>Last Name</th>
                <td><input type="text" class="form-control" name="user[last_name]" value="<?=$userInfo['last_name']?>" style="width: 100%" /></td>
            </tr>
            <tr>
                <th>Timezone</th>
                <td><input type="text" class="form-control" name="user[timezone]" value="<?=$userInfo['timezone']?>" style="width: 100%" /></td>
            </tr>
            <tr>
                <th>Dept</th>
                <td><input type="text" class="form-control" name="user[dept]" value="<?=$userInfo['dept']?>" style="width: 100%" /></td>
            </tr>
            <tr>
                <th>Job Title</th>
                <td><input type="text" class="form-control" name="user[job_title]" value="<?=$userInfo['job_title']?>" style="width: 100%" /></td>
            </tr>
            <tr>
                <th>Company</th>
                <td><input type="text" class="form-control" name="user[company]" value="<?=$userInfo['company']?>" style="width: 100%" /></td>
            </tr>
            <tr>
                <th>Location</th>
                <td><input type="text" class="form-control" name="user[location]" value="<?=$userInfo['location']?>" style="width: 100%" /></td>
            </tr>
            <tr>
                <th>Phone country</th>
                <td><input type="text" class="form-control" name="user[phone_country]" value="<?=$userInfo['phone_country']?>" style="width: 100%" /></td>
            </tr>
            <tr>
                <th>Phone number</th>
                <td><input type="text" class="form-control" name="user[phone_number]" value="<?=$userInfo['phone_number']?>" style="width: 100%" /></td>
            </tr>
            <tr>
                <td></td>
                <td>
                    <button type="submit" class="btn btn-success">Update user</button>
                </td>
            </tr>
        </table>
    </form>
</body>
</html>