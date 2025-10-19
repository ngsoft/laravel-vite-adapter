# PHP Vite Adapter

Package that makes possible to integrate Vite into your PHP Application
using [laravel-vite-plugin](https://laravel.com/docs/12.x/vite) under the hood

## Steps

### 1. Install this package

```shell
composer require ngsoft/php-vite-adapter
```

### 2. Install vite and dependencies

```shell

npm -D install vite@^7 typescript@^5 tslib@^2 @types/node@* laravel-vite-plugin@^2

```

### 3. Edit `package.json`

Add prerequisites to use .js files as modules

```json
{
    "private": true,
    "type": "module",
    "devDependencies": {
        "@types/node": "^24.8.1",
        "laravel-vite-plugin": "^2.0.1",
        "tslib": "^2.8.1",
        "typescript": "^5.9.3",
        "vite": "^7.1.10"
    }
}
```

### 4. Edit your `vite.config.ts`

```ts
import {defineConfig} from 'vite';
import {fileURLToPath, URL} from 'node:url';
import laravel from 'laravel-vite-plugin';

// https://vite.dev/config/
export default defineConfig({
    plugins: [
        laravel({
            // those are the endpoints to use with the adapter
            input: ['src/app.ts'],
            // public directory relative to the project root
            publicDirectory: 'public',
            // build directory name relative to public
            buildDirectory: 'build',
            refresh: true,
        }),
    ],
    resolve: {
        alias: {
            // replace src to your js/ts libs (do the same for tsconfig mappings)
            '@': fileURLToPath(new URL('./src', import.meta.url)),
            // $lib is required by many modern frameworks (jsrepo)
            $lib: fileURLToPath(new URL('./src/lib', import.meta.url)),
        },
        conditions: ['browser'],
    },
    server: {cors: true},
});

```

### 5. edit `tsconfig.json` (optional)

This is a generic configuration that many frameworks (svelte, vue, ...) use

```json
{
    "$schema": "https://json.schemastore.org/tsconfig",
    "_version": "5.0.0",
    "compilerOptions": {
        "importHelpers": true,
        "allowJs": true,
        "checkJs": true,
        "esModuleInterop": true,
        "forceConsistentCasingInFileNames": true,
        "resolveJsonModule": true,
        "skipLibCheck": true,
        "sourceMap": true,
        "strict": true,
        "moduleResolution": "bundler",
        "verbatimModuleSyntax": true,
        "isolatedModules": true,
        "module": "esnext",
        "noEmit": true,
        "target": "esnext",
        "lib": [
            "esnext",
            "DOM",
            "DOM.Iterable"
        ],
        "paths": {
            "@": [
                "./src"
            ],
            "@/*": [
                "./src/*"
            ],
            "$lib": [
                "./src/lib"
            ],
            "$lib/*": [
                "./src/lib/*"
            ]
        }
    },
    "include": [
        "vite.config.js",
        "vite.config.ts",
        "src/**/*.ts",
        "src/**/*.js"
    ],
    "exclude": [
        "node_modules/**"
    ]
}
```

### 6. Edit `.gitignore`

`hot` file is created when using `vite` to develop using hot module reload
`build` dir contains the `manifest.json` and the bundle you built using `vite build`

```.gitignore
public/build
public/hot
```

### 7. Edit `.env` (optional)

The laravel plugin uses an `APP_URL` to link to the php application when running `vite` command.

```dotenv
# Supply the port you wish to use for your PHP application
APP_URL=http://127.0.0.1:8000
```

Then to start your PHP application development

```shell
# start vite in a terminal
vite

# then in another terminal
# using the php development server
php -S 127.0.0.1:8000 -t public
# or if using symfony cli
symfony server:start --no-tls --port=8000
```

## Load Vite application from the PHP view

In your PHP view when rendering the `<head>` element, add the following code

```php
<?php

use NGSOFT\Vite\Adapter\ViteAdapter;

$projectRoot = '/path/to/project/root';
$publicPath = "$projectRoot/public";
$buildDir = "build";

$adapter = new ViteAdapter($projectRoot, $publicPath);

// put your base path there (if public is not /)
// $adapter->setBasePath('/');
// csp policies for some servers
// $adapter->setNonce(sha1(uniqid()));
// if you customized your hot file to not be in public (eg: "$projectRoot/var/cache/hot")
// $adapter->setHotFile($publicPath . '/hot');
// put the endpoint you wish to load (cf: vite.config.ts)
echo $adapter(['src/app.ts'], $buildDir);
?>

```

Then your php application will serve your node application

