<?php

/*
 * This file is part of the "DemoBundle" for Kimai.
 * All rights reserved by Kevin Papst (www.kevinpapst.de).
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KimaiPlugin\RunningBalanceBundle\Report;
use App\Entity\Project;
use App\Entity\User;

final class RunningBalanceReportFormQuery
{
    private ?\App\Entity\Project $project = null;
    private ?int $timeResolution = 0;

    public function __construct(User $user)
    {
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function getProject(): ?\App\Entity\Project
    {
        return $this->project;
    }

    public function setProject(?\App\Entity\Project $project): void
    {
        $this->project = $project;
    }

    public function getTimeResolution(): ?int
    {
        return $this->timeResolution;
    }

    public function setTimeResolution(?int $timeResolution): void
    {
        $this->timeResolution = $timeResolution;
    }
}
