<?php

/*
| Debugbar shows every visitor the SQL queries, session and request data of the
| page. It ships inside the committed vendor/ folder, so it exists on the server
| too, and by default it switches on whenever APP_DEBUG=true.
|
| Here it stays OFF unless .env explicitly says DEBUGBAR_ENABLED=true, which
| only the local development .env should ever do. The rest of the package's
| settings keep their defaults (Laravel merges them in).
*/

return [
    'enabled' => (bool) env('DEBUGBAR_ENABLED', false),
];
