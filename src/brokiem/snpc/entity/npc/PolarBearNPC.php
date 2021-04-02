<?php
declare(strict_types=1);

namespace brokiem\snpc\entity\npc;

use brokiem\snpc\entity\BaseNPC;
use pocketmine\entity\Entity;

class PolarBearNPC extends BaseNPC {

    public const SNPC_ENTITY_ID = Entity::POLAR_BEAR;

    public $height = 1.4;
    public $width = 1;
}
