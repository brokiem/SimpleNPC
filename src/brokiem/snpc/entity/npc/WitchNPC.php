<?php

declare(strict_types=1);

namespace brokiem\snpc\entity\npc;

use brokiem\snpc\entity\BaseNPC;
use pocketmine\entity\Entity;

class WitchNPC extends BaseNPC {

    public const NETWORK_ID = Entity::WITCH;

    public $height = 1.95;
}
