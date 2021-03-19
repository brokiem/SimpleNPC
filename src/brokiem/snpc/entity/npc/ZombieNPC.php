<?php

declare(strict_types=1);

namespace brokiem\snpc\entity\npc;

use brokiem\snpc\entity\BaseNPC;
use pocketmine\entity\Entity;

class ZombieNPC extends BaseNPC {

    public const NETWORK_ID = Entity::ZOMBIE;

    public $height = 1.95;
    public $width = 1;
}