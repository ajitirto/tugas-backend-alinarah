<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Artikel Baru</title>
</head>

<body>
    <h1>Artikel baru berhasil dibuat</h1>

    <p>Halo {{ $post->user->first_name }},</p>

    <p>
        Artikel kamu berhasil dibuat.
    </p>

    <h2>{{ $post->title }}</h2>

    <p>
        {{ $post->body }}
    </p>
</body>

</html>
