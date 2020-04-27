<!DOCTYPE html>
<html lang="ru">
<head>
    <title>Create user - [Zoom Assistant]</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/popper.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <a class="navbar-brand" href="#">Zoom Assistant - Create user</a>
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
    <input type="hidden" name="act" value="create_user" />
    <table class="table" style="font-size: 9pt;">
        <tr>
            <th>EMail</th>
            <td><input type="text" class="form-control" name="user[email]" style="width: 100%" /></td>
        </tr>
        <tr>
            <th>Create type</th>
            <td>
                <select name="user[action]">
                    <option value="create">Standart way (using activation EMail)</option>
                    <option value="autoCreate">Explicit password (no EMail sent)</option>
                    <option value="custCreate">No password & ability to log in Zoom Portal/Client</option>
                    <option value="ssoCreate">Pre-provisioning SSO user</option>
                </select>
            </td>
        </tr>
        <tr>
            <th>User type</th>
            <td>
                <select name="user[type]">
                    <option value="1">Basic</option>
                    <option value="1">Licensed</option>
                    <option value="1">On-Prem</option>
                </select>
            </td>
        </tr>
        <tr>
            <th>First Name</th>
            <td><input type="text" class="form-control" name="user[first_name]" style="width: 100%" /></td>
        </tr>
        <tr>
            <th>Last Name</th>
            <td><input type="text" class="form-control" name="user[last_name]" style="width: 100%" /></td>
        </tr>
        <tr>
            <th>Password (for "Explicit password" only)</th>
            <td><input type="text" class="form-control" name="user[password]" style="width: 100%" /></td>
        </tr>
        <tr>
            <td></td>
            <td>
                <button type="submit" class="btn btn-success">Create user</button>
            </td>
        </tr>
    </table>
</form>
</body>
</html>