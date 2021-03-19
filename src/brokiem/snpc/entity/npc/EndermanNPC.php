<?php

declare(strict_types=1);

namespace brokiem\snpc\entity\npc;

use brokiem\snpc\entity\BaseNPC;
use pocketmine\entity\Entity;

class EndermanNPC extends BaseNPC {

    public const NETWORK_ID = Entity::ENDERMAN;

    public $height = 2.9;
}
