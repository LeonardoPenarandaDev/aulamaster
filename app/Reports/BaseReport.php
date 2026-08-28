<?php

namespace App\Reports;

abstract class BaseReport implements ReportDefinition
{
    public function __construct(
        protected string $reportKey,
        protected string $reportLabel,
        protected string $reportModule,
    ) {}

    public function key(): string
    {
        return $this->reportKey;
    }

    public function label(): string
    {
        return $this->reportLabel;
    }

    public function module(): string
    {
        return $this->reportModule;
    }
}
