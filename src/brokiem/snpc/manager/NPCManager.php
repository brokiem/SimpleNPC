<?php

declare(strict_types=1);

namespace brokiem\snpc\manager;

use pocketmine\entity\Entity;
use pocketmine\level\Location;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class NPCManager
{
    public static function createNPC(string $type, Player $player, ?string $nametag = null, CompoundTag $commands = null, Location $customPos = null): bool
    {
        $nbt = Entity::createBaseNBT($player, null, $player->getYaw(), $player->getPitch());
        $nbt->setTag($commands ?? new CompoundTag("Commands", []));
        $nbt->setShort("Walk", 0);

        if ($customPos !== null) {
            $nbt = Entity::createBaseNBT($customPos, null, $customPos->getYaw(), $customPos->getPitch());
        }

        $entity = Entity::createEntity($type, $player->getLevel(), $nbt);

        if ($entity === null) {
            $player->sendMessage(TextFormat::RED . "Entity is null or entity $type is invalid, make sure you register the entity first!");
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