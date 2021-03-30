<?php

declare(strict_types=1);

namespace brokiem\snpc\entity\npc;

use brokiem\snpc\entity\BaseNPC;
use pocketmine\entity\Entity;

class SpiderNPC extends BaseNPC {

    public const SNPC_ENTITY_ID = Entity::SPIDER;

    public $height = 0.9;
    public $width = 1;
}
