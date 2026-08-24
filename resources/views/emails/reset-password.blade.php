<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Reset Your Password</title>
</head>

<body style="font-family: Arial, sans-serif; color: #333;">
    <h2>Reset Your Password</h2>

    <p>Hello {{ $user->name }},</p>

    <p>We received a request to reset your password for the APEL Management System.</p>

    <p>
        Click the link below to create a new password:
    </p>

    <p>
        <a href="{{ $resetLink }}">Reset Password</a>
    </p>

    <p>This link will expire in 60 minutes.</p>

    <p>If you did not request this, you can ignore this email.</p>
</body>

</html>
