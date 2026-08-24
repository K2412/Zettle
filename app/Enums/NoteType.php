<?php

namespace App\Enums;

enum NoteType: string
{
    case Fleeting = 'fleeting';
    case Literature = 'literature';
    case Permanent = 'permanent';
    case Structure = 'structure';
    case Project = 'project';
}
