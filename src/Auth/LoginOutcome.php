<?php

declare(strict_types=1);

namespace TechNest\Auth;

enum LoginOutcome
{
    case Success;
    case InvalidCredentials;
    case Disabled;
    case Throttled;
}
