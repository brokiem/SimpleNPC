<?php

declare(strict_types=1);

namespace brokiem\snpc\entity\npc;

use brokiem\snpc\entity\BaseNPC;

class SheepNPC extends BaseNPC {

    public const SNPC_ENTITY_ID = "minecraft:sheep";

    public $height = 1.3;
    public $width = 1;
}
