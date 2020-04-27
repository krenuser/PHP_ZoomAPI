<!DOCTYPE html>
<html lang="ru">
    <head>
        <title>Main page - [Zoom Assistant]</title>
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
                    <li class="nav-item">
                        <a class="nav-link" href="?">Home</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            Token
                        </a>
                        <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                            <a class="dropdown-item" href="https://zoom.us/oauth/authorize?response_type=code&client_id=<?=$api->getClientId()?>&redirect_uri=<?=urlencode($api->getRedirectURI())?>">Request token</a>
                            <a class="dropdown-item" href="?act=refresh_token">Refresh expired token</a>
                        </div>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            Actions
                        </a>
                        <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                            <a class="dropdown-item" href="?act=create_new_user">Create user</a>
                            <a class="dropdown-item" href="?act=list_users">List users</a>
                            <a class="dropdown-item" href="?act=get_all_meetings">List all meetings</a>
                        </div>
                    </li>
                </ul>
            </div>
        </nav>

        <?php
        $tokenData = $api->getTokenInfo();
        if(isset($tokenData['data'])) {
        ?>
        <div class="container">
            <div class="row">
                <div class="col-md-3">
                    <table class="table table-hover">
                        <tr style="white-space: nowrap; ">
                            <th>Token created</th>
                            <td><?=date('d.m.Y H:i:s', strtotime($tokenData['created']))?></td>
                        </tr>
                        <tr style="white-space: nowrap; ">
                            <th>Token expires</th>
                            <td><?=date('d.m.Y H:i:s', $tokenData['expires_ts']).($tokenData['expires_ts'] < time() ? ' (expired)' : '')?></td>
                        </tr>
                        <tr>
                            <th>Token scopes</th>
                            <td><?=$tokenData['data']['scope']?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        <?php
        }
        else {
            ?>
        <table class="table">
            <tr>
                <td>No token</td>
            </tr>
        </table>
        <?php
        }
        ?>
    </body>
</html>