<?php
declare(strict_types=1);

namespace APIs;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat as TF;

final class Loader extends PluginBase {

	public function onEnable(): void 
	{
		$this->getLogger()->info(TF::GREEN . 'The APIs and libraries were loaded correctly.');
	}
}