<?php

require('../vendor/autoload.php');

$app = new Silex\Application();
$app['debug'] = true;

// Register the monolog logging service
$app->register(
    new Silex\Provider\MonologServiceProvider(),
    array(
        'monolog.logfile' => 'php://stderr',
    )
);

// Register the Twig templating engine
$app->register(
    new Silex\Provider\TwigServiceProvider(),
    array(
        'twig.path' => __DIR__.'/../views',
    )
);

// and the db
$dbopts = parse_url(getenv('DATABASE_URL'));
$app->register(
    new PdoServiceProvider(),
    array(
        'pdo.dsn' => 'pgsql:dbname='.ltrim($dbopts["path"], '/').';host='.$dbopts["host"],
        'pdo.username' => $dbopts['user'],
        'pdo.password' => $dbopts['pass'],
        'pdo.port' => $dbopts['port'],
    )
);

// Our web handlers

$app->get('/', function () use ($app) {
    $app['monolog']->addDebug('logging output.');
    return str_repeat('Hello', getenv('TIMES'));
});

$app->get('/twig/{name}', function ($name) use ($app) {
    return $app['twig']->render('index.twig', array(
        'name' => $name,
    ));
});

$app->get('/db/', function () use ($app) {
    $statement = $app['pdo']->prepare('SELECT name FROM test_table');
    $statement->execute();

    $names = [];
    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        $app['monolog']->addDebug('Row ' . $row['name']);
        $names[] = $row;
    }
    return $app['twig']->render('database.twig', compact('names'));
});

$app->run();
