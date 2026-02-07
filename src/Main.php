<?php

namespace wSticks;

use pocketmine\data\bedrock\EffectIdMap;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\item\StringToItemParser;

class Main extends PluginBase implements Listener
{
    private array $time = [];

    public function onEnable(): void
    {
        if (!file_exists($this->getDataFolder() . "config.yml")) {
            new Config($this->getDataFolder() . "config.yml", Config::YAML, [
                "minecraft:ghast_tear" => [
                    "effects" => [
                        "speed" => [1, 20, 3, false]
                    ],
                    "time" => 10,
                    "use" => 5
                ],
                "popup" => "§bCooldown: §c{time}",
                "use" => "§bUtilisation: §e{use}"
            ]);
        }

        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onInteract(PlayerItemUseEvent $event): void
    {
        $item = $event->getItem();
        $player = $event->getPlayer();
        $playerName = $player->getName();

        foreach ($this->getConfig()->getAll() as $itemString => $data) {
            if (!is_array($data) || !isset($data["effects"])) {
                continue;
            }

            $parsedItem = StringToItemParser::getInstance()->parse($itemString);
            if ($parsedItem === null) {
                continue;
            }

            if (!$item->equals($parsedItem, false, false)) {
                continue;
            }

            if (!isset($this->time[$itemString][$playerName]) || $this->time[$itemString][$playerName] < time()) {

                foreach ($data["effects"] as $effect) {
                    $player->getEffects()->add(new EffectInstance(
                        EffectIdMap::getInstance()->fromId($effect[0]),
                        20 * $effect[1],
                        $effect[2],
                        $effect[3]
                    ));
                }

                $this->time[$itemString][$playerName] = time() + $data["time"];

                $tag = $item->getNamedTag();

                if ($tag->getTag("use") !== null) {
                    $use = $tag->getInt("use") - 1;

                    if ($use <= 0) {
                        $item->setCount($item->getCount() - 1);
                    } else {
                        $tag->setInt("use", $use);
                        $item->setLore([
                            str_replace("{use}", (string)$use, $this->getConfig()->get("use"))
                        ]);
                    }
                } else {
                    $maxUses = $data["use"] - 1;
                    $tag->setInt("use", $maxUses);
                    $item->setLore([
                        str_replace("{use}", (string)$maxUses, $this->getConfig()->get("use"))
                    ]);
                }

                $player->getInventory()->setItemInHand($item);
                return;
            }

            $timeLeft = $this->time[$itemString][$playerName] - time();
            $player->sendPopup(
                str_replace("{time}", (string)$timeLeft, $this->getConfig()->get("popup"))
            );
            return;
        }
    }
}