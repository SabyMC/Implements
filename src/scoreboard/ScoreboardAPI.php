<?php

namespace scoreboard;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\TextFormat as TF;
use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;

final class ScoreboardAPI {
	use SingletonTrait;
	
	const DISPLAY_SLOT = SetDisplayObjectivePacket::DISPLAY_SLOT_SIDEBAR;
	const CRITERIA_NAME = 'dummy';
	const SORT_ORDER = SetDisplayObjectivePacket::SORT_ORDER_ASCENDING;
	
	private $scoreboards = [];
    
    public function sendNew(Player $player, string $title): void
    {
        if($this->hasScoreboard($player)) {
            $this->remove($player);
        }
        
        $pk = SetDisplayObjectivePacket::create(
            self::DISPLAY_SLOT,
            $player->getName(),
            TF::colorize($title),
            self::CRITERIA_NAME,
            self::SORT_ORDER
        );
        
		$player->getNetworkSession()->sendDataPacket($pk);
		$this->scoreboards[$player->getName()] = $objectiveName;
    }
    
    public function remove(Player $player): void 
    {
        if($this->hasScoreboard($player)) {
            $objectiveName = $this->getObjectiveName($player);
            $pk = RemoveObjectivePacket::create($objectiveName);
            
            $player->getNetworkSession()->sendDataPacket($pk);
            unset($this->scoreboards[$player->getName()]);
        }
    }
    
    
    public function setLines(Player $player, array $lines): void
    {
        foreach($lines as $score => $line) {
            $this->setLine($player, $score + 1, $line); // TODO: setLine()
        }
    }
    
    public function setLine(Player $player, int $score, string $message): void
    {
        if(!$this->hasScoreboard($player)) return;
        if($score > 15 || $score < 1) return;
        $objectiveName = $this->getObjectiveName($player);
        
        $entry = new ScorePacketEntry();
		$entry->objectiveName = $objectiveName;
		$entry->type = $entry::TYPE_FAKE_PLAYER;
		$entry->customName = $message;
		$entry->score = $score;
		$entry->scoreboardId = $score;
		
		$pk = new SetScorePacket(
		    SetScorePacket::TYPE_CHANGE,
		    [$entry]
		);
		
		$player->getNetworkSession()->sendDataPacket($pk);
    }
    
    private function hasScoreboard(Player $player): bool
    {
		return isset($this->scoreboards[$player->getName()]);
    }
    
    public function getObjectiveName(Player $player) : null|string {
		return $this->scoreboards[$player->getName()];
	}
}
