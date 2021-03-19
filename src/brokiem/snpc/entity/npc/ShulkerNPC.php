<?php

declare(strict_types=1);

namespace brokiem\snpc\entity\npc;

use brokiem\snpc\entity\BaseNPC;
use pocketmine\entity\Entity;

class ShulkerNPC extends BaseNPC {

    public const NETWORK_ID = Entity::SHULKER;

    public $height = 1;
    public $width = 1;
}
