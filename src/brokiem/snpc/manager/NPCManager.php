<?php

declare(strict_types=1);

namespace brokiem\snpc\manager;

use pocketmine\entity\Entity;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class NPCManager
{
    public static function createNPC(string $type, Player $player, ?string $nametag = null): bool
    {
        $nbt = Entity::createBaseNBT($player, null, $player->getYaw(), $player->getPitch());
        $nbt->setShort("Walk", 0);

        $entity = Entity::createEntity($type, $player->getLevel(), $nbt);

        if ($entity === null) {
            $player->sendMessage(TextFormat::RED . "Entity is null or entity $type is invalid");
            return false;
        }

        if ($nametag !== null) {
            $entity->setNameTag($nametag);
            $entity->setNameTagAlwaysVisible();
        }

        $entity->spawnToAll();
        return true;
    }
}