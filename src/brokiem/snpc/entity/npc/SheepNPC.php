<?php

/**
 * Copyright (c) 2021 brokiem
 * SimpleNPC is licensed under the GNU Lesser General Public License v3.0
 */

declare(strict_types=1);

namespace brokiem\snpc\entity\npc;

use brokiem\snpc\entity\BaseNPC;

class SheepNPC extends BaseNPC {

    public const SNPC_ENTITY_ID = "minecraft:sheep";

    public $height = 1.3;
    public $width = 1;
}
