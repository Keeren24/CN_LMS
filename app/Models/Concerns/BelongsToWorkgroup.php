<?php

namespace App\Models\Concerns;

// Workgroup scoping is enforced by CN_ADMIN. In CN_LMS, all data is read via
// student_id relationships, so the global scope is intentionally omitted.
trait BelongsToWorkgroup
{
}
