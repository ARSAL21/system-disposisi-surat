<?php

namespace App\Auditing\Enums;

enum PositionAssignmentRequirement: string
{
    case Required = 'required';
    case Forbidden = 'forbidden';
    case Optional = 'optional';
}
