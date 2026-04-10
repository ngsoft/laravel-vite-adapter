<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Test Import Vite</title>
    <?php

    use NGSOFT\Vite\Adapter\ViteAdapter;
    use NGSOFT\Vite\Adapter\ViteAdapterOptions;
    use NGSOFT\Vite\Adapter\ViteException;

    require_once __DIR__ . '/../vendor/autoload.php';
    $isError = false;

    try {
        echo (new ViteAdapter(dirname(__DIR__), __DIR__, new ViteAdapterOptions()))('example/app.ts');
    } catch (ViteException $err) {
        $isError = $err->getMessage();
    } ?>
</head>
<body>

<?php if ($isError): ?>
    <div class="flex flex-col gap-2 justify-center items-center w-full min-h-[100vh]">
        <h1 class="text-2xl my-3 text-red-500">Error !!!</h1>
        <p>The development server is not running, and you did not build anything.</p>
        <p>Here is your error message:</p>
        <pre><code><?= $isError; ?></code></pre>
    </div>

<?php endif; ?>

</body>
</html>
