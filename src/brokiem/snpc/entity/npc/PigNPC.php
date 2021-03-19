<?php

declare(strict_types=1);

namespace brokiem\snpc\entity\npc;

use brokiem\snpc\entity\BaseNPC;
use pocketmine\entity\Entity;

class PigNPC extends BaseNPC {

    public const NETWORK_ID = Entity::PIG;

    public $height = 0.9;
    public $width = 1;
}
