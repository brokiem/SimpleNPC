<?php

declare(strict_types=1);

namespace brokiem\snpc\entity\npc;

use brokiem\snpc\entity\BaseNPC;

class HorseNPC extends BaseNPC {

    public const SNPC_ENTITY_ID = "minecraft:horse";

    public $height = 1.6;
    public $width = 1;
}
