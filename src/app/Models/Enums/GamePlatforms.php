<?php

namespace App\Models\Enums;

enum GamePlatforms:int {
    case PC = 0;
    case NintendoSwitch = 1;
    case XboxOne = 2;
    case XboxSeriesXOrS = 3;
    case Playstation4 = 4;
    case Playstation5 = 5;
}