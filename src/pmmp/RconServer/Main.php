<?php

declare(strict_types=1);

namespace pmmp\RconServer;

use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginException;
use Webmozart\PathUtil\Path;
use function base64_encode;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function inet_pton;
use function is_array;
use function is_float;
use function is_int;
use function is_string;
use function random_bytes;
use function yaml_emit;
use function yaml_parse;

class Main {

	private Plugin $plugin;

	public function onEnable(Plugin $plugin) : void{
		$this->plugin = $plugin;

		$configPath = Path::join($this->getDataFolder(), 'rcon/rcon.yml');
		try{
			$config = $this->loadConfig($configPath);
		}catch(PluginException $e){
			$plugin->getLogger()->alert('Invalid config file ' . $configPath . ': ' . $e->getMessage());
			$plugin->getLogger()->alert('Please fix the errors and restart the server.');
			$plugin->getServer()->getPluginManager()->disablePlugin($this);
			return;
		}

		$plugin->getLogger()->info('Starting RCON on ' . $config->getIp() . ':' . $config->getPort());
		try{
			$plugin->getServer()->getNetwork()->registerInterface(new Rcon(
				$config,
				function(string $commandLine) : string{
					$response = new RconCommandSender($plugin->getServer(), $plugin->getServer()->getLanguage());
					$response->recalculatePermissions();
					$plugin->getServer()->dispatchCommand($response, $commandLine);
					return $response->getMessage();
				},
				$plugin->getServer()->getLogger(),
				$plugin->getServer()->getTickSleeper()
			));
		}catch(RconException $e){
			$plugin->getLogger()->alert('Failed to start RCON: ' . $e->getMessage());
			$plugin->getLogger()->logException($e);
			$plugin->getServer()->getPluginManager()->disablePlugin($plugin);
			return;
		}
	}

	/**
	 * @throws PluginException
	 */
	private function loadConfig(string $fileLocation) : RconConfig{
		if(!file_exists($fileLocation)){
			$config = [
				'ip' => $this->plugin->getServer()->getIp(),
				'port' => $this->plugin->getServer()->getPort(),
				'max-connections' => 50,
				'password' => base64_encode(random_bytes(8))
			];
			file_put_contents($fileLocation, yaml_emit($config));
			$this->plugin->getLogger()->notice('RCON config file generated at ' . $fileLocation . '. Please customize it.');
		}else{
			$rawConfig = @file_get_contents($fileLocation);
			if($rawConfig === false){
				throw new PluginException('Failed to read config file (permission denied)');
			}
			try{
				$config = yaml_parse($rawConfig);
			}catch(\ErrorException $e){
				throw new PluginException($e->getMessage());
			}
		}

		if(!is_array($config)){
			throw new PluginException('Failed to parse config file');
		}

		$ip = null;
		$port = null;
		$maxConnections = null;
		$password = null;
		foreach($config as $key => $value){
			match($key){
				'ip' => is_string($value) && inet_pton($value) !== false ? $ip = $value : throw new PluginException("Invalid IP address"),
				'port' => is_int($value) && $value > 0 && $value < 65535 ? $port = $value : throw new PluginException("Invalid port, must be a port in range 0-65535"),
				'max-connections' => is_int($value) && $value > 0 ? $maxConnections = $value : throw new PluginException("Invalid max connections, must be a number greater than 0"),
				'password' => is_string($value) || is_int($value) || is_float($value) ? $password = (string) $value : throw new PluginException("Invalid password, must be a string"),
				default => throw new PluginException("Unexpected config key \"$key\"")
			};
		}
		if($ip === null){
			throw new PluginException("Missing IP address");
		}
		if($port === null){
			throw new PluginException("Missing port");
		}
		if($maxConnections === null){
			throw new PluginException("Missing max connections");
		}
		if($password === null){
			throw new PluginException("Missing password");
		}

		return new RconConfig($ip, $port, $maxConnections, $password);
	}
}
