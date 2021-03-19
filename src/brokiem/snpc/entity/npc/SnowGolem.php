<?php

declare(strict_types=1);

namespace brokiem\snpc\entity\npc;

use brokiem\snpc\entity\BaseNPC;
use pocketmine\entity\Entity;

class SnowGolem extends BaseNPC {

    public const NETWORK_ID = Entity::SNOW_GOLEM;

    public $height = 1.9;
}
