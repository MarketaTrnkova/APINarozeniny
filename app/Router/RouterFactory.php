<?php

declare(strict_types=1);

namespace App\Router;

use Nette;
use Nette\Application\Routers\RouteList;
use Nette\Application\Routers\Route;

final class RouterFactory
{
	use Nette\StaticClass;

	public static function createRouter(): RouteList
	{
		$router = new RouteList;
		
		$router[] = new Route('', 'Api:default'); //vychozi adresar pro zpracovani API
        $router[] = new Route('chat', 'Api:chat');
		$router[] = new Route('<url .*>', 'Api:odkazNaDokumentaci'); //vztahuje se na vsechny routy, ktere nebyly vyse specifikovane - zachyti vse.
		

		return $router;
	}
}
