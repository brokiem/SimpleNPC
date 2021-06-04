<?php

declare(strict_types=1);

namespace brokiem\snpc\entity\npc;

use brokiem\snpc\entity\BaseNPC;
use pocketmine\entity\Entity;

class BatNPC extends BaseNPC {

    public const SNPC_ENTITY_ID = Entity::BAT;

    public $height = 0.9;
    public $width = 0.5;
}
