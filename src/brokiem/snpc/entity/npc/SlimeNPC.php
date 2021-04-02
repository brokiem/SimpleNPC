<?php
declare(strict_types=1);

namespace brokiem\snpc\entity\npc;

use brokiem\snpc\entity\BaseNPC;
use pocketmine\entity\Entity;

class SlimeNPC extends BaseNPC {

    public const SNPC_ENTITY_ID = Entity::SLIME;

    public $height = 0.51;
    public $width = 1;
}
