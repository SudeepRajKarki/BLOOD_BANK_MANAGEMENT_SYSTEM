<!DOCTYPE html>
<html>
<head>
    <title>Reset Your Password</title>
</head>
<body>
    <h2>Hello, {{ $user->name }}</h2>
    <p>You recently requested to reset your password for your Blood Management account.</p>
    <p>Click the link below to set a new password:</p>
    <a href="{{ $resetUrl }}">{{ $resetUrl }}</a>
    <p>This link will expire in 30 minutes.</p>
    <p>If you didn’t request this, you can safely ignore this email.</p>
    <br>
    <p>Thank you,<br>The Blood Management Team</p>
</body>
</html>
