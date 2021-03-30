<?php

declare(strict_types=1);

namespace brokiem\snpc\entity\npc;

use brokiem\snpc\entity\BaseNPC;
use pocketmine\entity\Entity;

class SheepNPC extends BaseNPC {

    public const SNPC_ENTITY_ID = Entity::SHEEP;

    public $height = 1.3;
    public $width = 1;
}
