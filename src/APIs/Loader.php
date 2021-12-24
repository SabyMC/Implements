<?php
declare(strict_types=1);

namespace APIs;

use pmmp\RconServer\Main;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat as TF;

final class Loader extends PluginBase {

	public function onEnable(): void 
	{
		(new Main())->onEnable($this);
		$this->getLogger()->info(TF::GREEN . 'The APIs and libraries were loaded correctly.');
	}
}