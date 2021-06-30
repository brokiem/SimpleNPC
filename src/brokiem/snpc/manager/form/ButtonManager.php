<?php

/**
 * Copyright (c) 2021 brokiem
 * SimpleNPC is licensed under the GNU Lesser General Public License v3.0
 */

declare(strict_types=1);

namespace brokiem\snpc\manager\form;

use EasyUI\element\Input;
use pocketmine\utils\SingletonTrait;

class ButtonManager {
    use SingletonTrait;

    public function getUIButtons(): array {
        return [
            "Reload Config" => [
                "text" => "Reload Config",
                "icon" => null,
                "command" => "snpc reload",
                "function" => null
            ], "Spawn NPC" => [
                "text" => "Spawn NPC",
                "icon" => null,
                "command" => null,
                "function" => "spawnNPC"
            ], "Edit NPC" => [
                "text" => "Edit NPC",
                "icon" => null,
                "command" => null,
                "function" => "editNPC"
            ],
            "Get NPC ID" => [
                "text" => "Get NPC ID",
                "icon" => null,
                "command" =>
                    "snpc id",
                "function" => null
            ], "Migrate NPC" => [
                "text" => "Migrate NPC",
                "icon" => null,
                "command" => "snpc migrate",
                "function" => null
            ], "Remove NPC" => [
                "text" => "Remove NPC",
                "icon" => null,
                "command" => "snpc remove",
                "function" => null
            ], "List NPC" => [
                "text" => "List NPC",
                "icon" => null,
                "command" => null,
                "function" => "npcList"
            ]
        ];
    }

    public function getEditButtons(): array {
        return [
            "Add Command" => [
                "text" => "Add Command",
                "icon" => null,
                "element" => [
                    "id" => "addcmd",
                    "element" => new Input("Use {player} for player name, and don't use slash [/]\nYou can also use rca command\n\nEnter the command here. (Command executed by console)")
                ], "additional" => []
            ], "Remove Command" => [
                "text" => "Remove Command",
                "icon" => null,
                "element" => [
                    "id" => "removecmd",
                    "element" => new Input("Enter the command here")
                ], "additional" => []
            ], "Change Nametag" => [
                "text" => "Change Nametag",
                "icon" => null,
                "element" => [
                    "id" => "changenametag", "element" => new Input("Enter the new nametag here")
                ], "additional" => []
            ], "Change Skin" => [
                "text" => "Change Skin\n(Only Human NPC)",
                "icon" => null, "element" => [
                    "id" => "changeskin",
                    "element" => new Input("Enter the skin URL or online player name")
                ], "additional" => []
            ], "Change Cape" => [
                "text" => "Change Cape\n(Only Human NPC)",
                "icon" => null,
                "element" => [
                    "id" => "changecape",
                    "element" => new Input("Enter the Cape URL or online player name")
                ], "additional" => []
            ], "Change Scale/Size" => [
                "text" => "Change Scale/Size",
                "icon" => null,
                "element" => [
                    "id" => "changescale",
                    "element" => new Input("Enter the new scale number (min=0.01")
                ],
                "additional" => []
            ], "Set Armor" => [
                "text" => "Set Armor\n(You have to wear the armor)",
                "icon" => null,
                "element" => [],
                "additional" => [
                    "form" => "editUI",
                    "button" => [
                        "text" => "Set Armor\n(You have to wear the armor)",
                        "icon" => null,
                        "function" => "setArmor",
                        "force" => true
                    ]
                ]
            ], "Set Held Item" => [
                "text" => "Set Held Item\n(You have to hold the item)",
                "icon" => null,
                "element" => [],
                "additional" => [
                    "form" => "editUI",
                    "button" => [
                        "text" => "Set Held Item\n(You have to hold the item)",
                        "icon" => null,
                        "function" => "setHeld",
                        "force" => true
                    ]
                ]
            ], "Disable Rotate" => [
                "text" => "Disable Rotate\n(Look to player)",
                "icon" => null,
                "element" => [],
                "additional" => [
                    "form" => "editUI",
                    "button" => [
                        "text" => "Disable Rotate\n(Look to player)",
                        "icon" => null,
                        "function" => "disableRotate",
                        "force" => true
                    ]
                ]
            ], "Enable Rotate" => [
                "text" => "Enable Rotate\n(Look to player)",
                "icon" => null,
                "element" => [],
                "additional" => [
                    "form" => "editUI",
                    "button" => [
                        "text" => "Enable Rotate\n(Look to player)",
                        "icon" => null,
                        "function" => "enableRotate",
                        "force" => true
                    ]
                ]
            ], "Show Nametag" => [
                "text" => "Show Nametag",
                "icon" => null,
                "element" => [],
                "additional" => [
                    "form" => "editUI",
                    "button" => [
                        "text" => "Show Nametag",
                        "icon" => null,
                        "function" => "showNametag",
                        "force" => true
                    ]
                ]
            ], "Hide Nametag" => [
                "text" => "Hide Nametag",
                "icon" => null,
                "element" => [],
                "additional" => [
                    "form" => "editUI",
                    "button" => [
                        "text" => "Hide Nametag",
                        "icon" => null,
                        "function" => "hideNametag",
                        "force" => true
                    ]
                ]
            ], "Command List" => [
                "text" => "Command List",
                "icon" => null,
                "element" => [],
                "additional" => [
                    "form" => "",
                    "button" => [
                        "text" => null,
                        "icon" => null,
                        "function" => "commandList",
                        "force" => false
                    ]
                ]
            ], "Teleport" => [
                "text" => "Teleport",
                "icon" => null,
                "element" => [],
                "additional" => [
                    "form" => "",
                    "button" => [
                        "text" => null,
                        "icon" => null,
                        "function" => "teleport",
                        "force" => false
                    ]
                ]
            ]
        ];
    }
}