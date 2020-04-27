<!DOCTYPE html>
<html lang="ru">
<head>
    <title>Update user picture - [Zoom Assistant]</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/popper.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <a class="navbar-brand" href="#">Zoom Assistant - Update user picture</a>
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
    <div class="container">
        <div class="row">
            <form method="POST" action="?" enctype="multipart/form-data">
                <legend>Select new user profile picture</legend>
                <input type="hidden" name="act" value="update_user_picture" />
                    <?php
                    $userId = getRequestParam('userId');

                ?>
                <label>
                    Pick JPEG/PNG file:
                    <input type="file" name="picture" size="50" />
                </label>
                <button type="submit" class="btn btn-success">Update user picture</button>
            </form>
        </div>
    </div>
</body>
</html>