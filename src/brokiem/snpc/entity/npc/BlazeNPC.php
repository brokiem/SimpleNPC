<?php

declare(strict_types=1);

namespace brokiem\snpc\entity\npc;

use brokiem\snpc\entity\BaseNPC;
use pocketmine\entity\Entity;

class BlazeNPC extends BaseNPC {

    public const NETWORK_ID = Entity::BLAZE;

    public $height = 1.8;
}
