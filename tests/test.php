<?php

use WordPressPsr\Psr17\Psr17Factory;
use WordPressPsr\Psr17\Psr17FactoryProvider;

require __DIR__ . '/../vendor/autoload.php';

$request_handler = \WordPressPsr\RequestHandlerFactory::create( '../wordpress' );

$psr17FactoryProvider = new Psr17FactoryProvider();

/** @var Psr17Factory $psr17factory */
foreach ($psr17FactoryProvider->getFactories() as $psr17factory) {
	if ($psr17factory::isServerRequestCreatorAvailable()) {
		$request_creator =  $psr17factory::getServerRequestCreator();
	}
}

$request = $request_creator->createServerRequestFromGlobals();
$response = $request_handler->handle( $request );
echo $response->getBody()->getContents();