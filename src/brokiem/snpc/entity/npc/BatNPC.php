<?php

/**
 * Copyright (c) 2021 brokiem
 * SimpleNPC is licensed under the GNU Lesser General Public License v3.0
 */

declare(strict_types=1);

namespace brokiem\snpc\entity\npc;

use brokiem\snpc\entity\BaseNPC;

class BatNPC extends BaseNPC {

    public const SNPC_ENTITY_ID = "minecraft:bat";

    public $height = 0.9;
    public $width = 0.5;
}
