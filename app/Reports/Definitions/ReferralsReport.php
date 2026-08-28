<?php

namespace App\Reports\Definitions;

use App\Models\Referral;
use App\Reports\BaseReport;
use Illuminate\Support\Collection;

class ReferralsReport extends BaseReport
{
    public function __construct()
    {
        parent::__construct('referrals', 'Referidos', 'referidos');
    }

    public function headings(): array
    {
        return ['Referente', 'Beneficio referente', 'Referido', 'Beneficio referido', 'Fecha'];
    }

    public function rows(array $filters): Collection
    {
        return Referral::query()->with(['referrer:id,name', 'referred:id,name'])->orderByDesc('created_at')->get();
    }

    public function toRow($row): array
    {
        return [$row->referrer->name, $row->referrer_discount, $row->referred->name, $row->referred_discount, $row->created_at->toDateString()];
    }
}
