<?php
declare(strict_types=1);

namespace brokiem\snpc\manager;

use pocketmine\entity\Entity;
use pocketmine\Player;

class NPCManager
{
    public static function createNPC(string $type, Player $player, ?string $nametag = null, bool $canWalk = false): bool
    {
        $nbt = Entity::createBaseNBT($player, null, $player->getYaw(), $player->getPitch());
        if ($canWalk) {
            $nbt->setShort("Walk", 1);
        }

        $entity = Entity::createEntity($type, $player->getLevel(), $nbt);

        if ($entity === null) {
            return false;
        }

        if (!$nametag) {
            $entity->setNameTag($nametag);
            $entity->setNameTagAlwaysVisible();
        }

        $entity->spawnToAll();
        return true;
    }
}