<?php

namespace Database\Seeders\Concerns;

use App\Models\Branch;
use Illuminate\Support\Collection;

trait SeedsBranches
{
    private ?Branch $onlyBranch = null;

    private ?string $emailTag = null;

    public function forBranch(Branch $branch, ?string $emailTag = null): static
    {
        $this->onlyBranch = $branch;
        $this->emailTag = $emailTag;

        return $this;
    }

    protected function seedBranches(): Collection
    {
        return $this->onlyBranch
            ? collect([$this->onlyBranch])
            : Branch::orderBy('branch_id')->get();
    }

    protected function staffEmail(string $local, string $branchTag): string
    {
        return $this->emailTag
            ? "{$local}.{$this->emailTag}@demo.amuma.test"
            : "{$local}{$branchTag}@gmail.com";
    }
}
