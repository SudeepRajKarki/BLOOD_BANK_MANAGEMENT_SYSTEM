<html>
<body>
    <h2>Hello, {{ $user->name }}</h2>
    <p>Thank you for registering. Please verify your email address by clicking the link below:</p>
    <a href="{{ $verificationUrl }}">Verify Email</a>
</body>
</html>
