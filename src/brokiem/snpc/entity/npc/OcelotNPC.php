<?php

declare(strict_types=1);

namespace brokiem\snpc\entity\npc;

use brokiem\snpc\entity\BaseNPC;
use pocketmine\entity\Entity;

class OcelotNPC extends BaseNPC {

    public const NETWORK_ID = Entity::OCELOT;

    public $height = 0.7;
    public $width = 1;
}
